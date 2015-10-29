<?php

namespace App\Database\Services;

use Carbon\Carbon;

use Braintree_Transaction;

class OrderService extends Service {

    /**
     * find orders by mobile key
     *
     * @param $data
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     * @throws \Exception
     */
    public function find_by_user($data) {

        $szMobileKey = $data['szMobileKey'];

        $user = $this->userRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

        if ($user) {

            $queryBuilder = $this->orderRepository->getModel()->newQuery()
                ->where('iCompleted', 1)
                ->where('dtExpiry', '>=', Carbon::now())
                ->where('idUser', $user->id)
                ->where(function($query) {
                    $query->whereNotIn('szPassType', ['package pass', 'gift pass', 'gift pass', 'one time pass']);
                    $query->orWhereRaw(\DB::raw('iLimitionCount > iTotalUsedCount'));
                });

//            \Log::info($queryBuilder->toSql());

            $order = $queryBuilder->get();

            return $order;
        } else {
            throw new \Exception('Unauthorized User');
        }
    }

    /**
     * Find orders of package pass
     *
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function find_package_pass_by_user($data) {

        $szMobileKey = $data['szMobileKey'];

        $user = $this->userRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

        if (empty($user)) {
            throw new \Exception('Unauthorized User');
        }

        $queryBuilder = $this->orderRepository->getModel()->newQuery()
            ->whereHas('subscription', function($query) {
                $query->where('isDeleted', 0);
            })
            ->where('iCompleted', 1)
            ->where('dtExpiry', '>=', Carbon::now())
            ->where('idUser', $user->id)
            ->where(function($query) {
                $query->where('szPassType', 'subscription pass');
                $query->orWhereRaw(\DB::raw("(szPassType = 'package pass' && iLimitionCount > iTotalUsedCount)"));
            });

//        \Log::info($queryBuilder->toSql());

        $order = $queryBuilder->get();

        return $order;
    }

    /**
     *
     *
     * @param $idUser
     * @param $idSubscription
     * @return mixed
     * @throws \Exception
     */
    public function order_promotional_pass($idUser, $idSubscription) {

        $subscription = $this->subscriptionRepository->find($idSubscription);

        if (empty($subscription)) {
            throw new \Exception('No subscription found');
        }

        $user = $this->userRepository->find($idUser);

        if (empty($user)) {
            throw new \Exception('No user found');
        }

        $now = Carbon::now();

        $attributes = [
            'idUser'            => $user->id,
            'idSubscription'    => $subscription->id,
            'szFirstName'       => $user->szFirstName,
            'szLastName'        => $user->szLastName,
            'szEmail'           => $user->szEmail,
            'szZipCode'         => $user->szZipCode,
            'szSubscriptionPeriod'  => 'Once',
            'dtExpiry'          => '2099-12-31',
            'iPromotional'      => 1,
            'iCompleted'        => 1,
            'dtPurchased'       => $now,
            'dtAvailable'       => $now,
            'PROFILESTATUS'     => 'Active',
            'szPassType'        => 'promotional pass',
        ];

        $order = $this->orderRepository->create($attributes);

        if (empty($order)) {
            throw new \Exception('Fail to insert');
        }

        $this->uosMappingRepository->create([
            'idUser'        => $user->id,
            'idSubscription'    => $subscription->id,
            'idOrder'       => $order->id,
            'iLimitions'    => 'Once',
        ]);

        $merchant = $this->merchantRepository->find($subscription->idMerchant);
        $paymentInfoKey = \StringHelper::encryptString(date('dmYHis'));
//        $paymentInfoKey = md5(date('dmYHis'));
        $link = \Config::get('constants.__MAIN_SITE_URL__') . '/changePaymentInfo.php?szConfirmationKey=' . $paymentInfoKey;

        $user = $this->userRepository->update(['szConfirmationKey' => $paymentInfoKey], $user->id);

        # email

        $template = $this->emailTemplateRepository->findWhere(['keyname' => '__PROMO_PASS_EMAIL__'])->first();

        $subject = $template['subject'];
        $message = $template['description'];

        $subject = str_replace('szNumber', $order->id, $subject);

        $message = str_replace('szPassName', $subscription->szTilte, $message);
        $message = str_replace('szMerchantName', $merchant->szName, $message);
        $message = str_replace('http://szLink', $link, $message);

        $to = $user->szEmail;
        $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');

        \EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);

        return $order;
    }

    /**
     * Place an order
     *
     * @param $idUser
     * @param $idMerchant
     * @param $idSubscription
     * @param $payment_method_token
     * @param $paymentType
     * @param $szPaymentAmount
     * @param int $idParentOrder
     * @return mixed
     * @throws \Exception
     */
    public function placeOrder($idUser, $idMerchant, $idSubscription, $payment_method_token, $paymentType, $szPaymentAmount, $idParentOrder = 0) {

        $user = $this->userRepository->find($idUser);
        $merchant = $this->merchantRepository->find($idMerchant);
        $subscription = $this->subscriptionRepository->find($idSubscription);

        $now = Carbon::now();

        $serviceFee = 0.00;
        if ($merchant->fServiceFeePercentage > 0.00) {
            $serviceFee = (float) $szPaymentAmount * $merchant->fServiceFeePercentage * 0.01;
        }

        if ($user->fTotalCredit > 0.00 && $user->fTotalCredit >= $szPaymentAmount) {
            $fPaidAmount = 0.01;
            $fDiscountAmount = $szPaymentAmount - 0.01;
        } else if ($user->fTotalCredit > 0.00 && $user->fTotalCredit < $szPaymentAmount) {
            $fPaidAmount = $szPaymentAmount - $user->fTotalCredit;
            $fDiscountAmount = $user->fTotalCredit;
        } else {
            $fPaidAmount = $szPaymentAmount;
            $fDiscountAmount = 0.00;
        }

        if ($merchant->szBraintreeMerchantId = '') {
            $trans_result = Braintree_Transaction::sale(array(
                'paymentMethodToken' => $payment_method_token,
                'amount' => $fPaidAmount,
                'merchantAccountId' => $merchant->szBraintreeMerchantId,
                'options' => array(
                    'submitForSettlement' => true,
                    'holdInEscrow' => true,
                ),
                'serviceFeeAmount' => $serviceFee
            ));
        } else {
            $trans_result = Braintree_Transaction::sale(array(
                'paymentMethodToken' => $payment_method_token,
                'amount' => $fPaidAmount,
                'options' => array(
                    'submitForSettlement' => true
                )
            ));
        }

        if ($trans_result->success != 1) {
            throw new \Braintree_Exception('Fail in Braintree_Transaction');
        } else {
            $passType = $subscription->szPassType;
            $dtExpiry = '2099-12-31';
            $activation_count = $subscription->iActivationCount;
            $autoRenewFlag = $subscription->iAutoRenewFlag;

            $tranctionStatus = $trans_result->transaction->_attributes['status'];
            $idCart = $trans_result->transaction->_attributes['id'];

            $order = $this->orderRepository->create([
                'idUser' => $user->id,
                'idSubscription' => $subscription->id,
                'fSubscriptionAmount' => $szPaymentAmount,
                'szFirstName' => $user->szFirstName,
                'szLastName' => $user->szLastName,
                'szEmail' => $user->szEmail,
                'szZipCode' => $user->szZipCode,
                'szPaymentType' => $paymentType,
                'szSubscriptionPeriod' => '',
                'dtExpiry' => $dtExpiry,
                'iLimitionCount' => $activation_count,
                'szPassType' => $passType,
                'iAutoRenewFlag' => $autoRenewFlag,
                'iCompleted' => 1,
                'dtPurchased' => $now,
                'dtAvailable' => $now,
                'iTotalPaymentCount' => 1,
                'fPaidAmount' => $fPaidAmount,
                'fDiscountAmount' => $fDiscountAmount,
                'PROFILESTATUS' => $tranctionStatus,
                'PROFILEID' =>  '',
                'TRANSACTIONID' => $idCart,
                'szPaymentToken' => $payment_method_token,
                'fServiceFee' => $serviceFee,
                'idParentOrder' => $idParentOrder,
            ]);

            # order payout to merchant

            if ($merchant->szBraintreeMerchantId != '') {
                $this->orderRepository->update([
                    'iPayoutPaid'   => 1,
                    'dtPayoutPaid'  => $now,
                ], $order->id);
            }

            $this->uosMappingRepository->create([
                'idUser' => $user->id,
                'idOrder' => $order->id,
                'idSubscription' => $subscription->id,
                'fPrice' => $szPaymentAmount,
                'iPeriod' => '',
                'iYearlyPeriod' => '',
                'fYearlyPrice' => '',
                'iLimitions' => $activation_count,
                'iLimitionCount' => $activation_count,
                'szPassType' => $passType,
            ]);

            # insert_user_debit_history  // previous code only records for invite, but I don't think so

            if ($user->fTotalCredit > 0.00 && $fDiscountAmount > 0.00) {

                \DB::table('tblusercreditdebithistory')->insert([
                    'idUser'    => $user->id,
                    'fPrice'    => $fDiscountAmount,
                    'idDebitOrder'  => $order->id,
                    'sztransactiontype' => 'debit',
                    'datetime'  => $now,
                ]);

                $this->userRepository->update([
                    'fTotalCredit' => $user->fTotalCredit - $fDiscountAmount,
                ], $user->id);
            }

            # updatereferalcredited

            $uiMapping = $this->uiMappingRepository->findWhere([
                'idSignupUser' => $user->id,
                'dtCredited' => '0000-00-00 00:00:00'
            ])->first();

            if ($uiMapping) {

                $fReferralCredit = (float) $uiMapping->fReferralcredit;

                $queryBuilder = $this->userRepository->getModel()->newQuery()
                    ->where('id', $uiMapping->idReferUser)
                    ->update([
                        'fTotalCredit' => \DB::raw("fTotalCredit + {$fReferralCredit}")
                    ]);

                \Log::info($queryBuilder->toSql());

                \DB::table('tblusercreditdebithistory')->insert([
                    'idUser'    => $uiMapping->idReferUser,
                    'fPrice'    => $uiMapping->fReferralcredit,
                    'idinvitecodemapped'    => $uiMapping->id,
                    'szcredittype'      => 'referal',          // previous code 'referal'
                    'sztransactiontype' => 'credit',
                    'datetime'  => $now,
                ]);

                $this->uiMappingRepository->update([
                    'dtCredited' => $now
                ], $uiMapping->id);
            }

            # email

            $template = $this->emailTemplateRepository->findWhere(['keyname' => '__SUBSCRIPTION_CONFIRMATION_EMAIL__'])->first();

            $subject = $template->subject;
            $message = $template->description;

            $subject = str_replace('szNumber', $order->id, $subject);
//            $passType = $order->szPassType;
//            $szSubscriptionPeriod = 'Package Pass';

            $paymentInfoKey = \StringHelper::encryptString(date('dmYHis'));
            $this->userRepository->update([
                'szConfirmationKey' => $paymentInfoKey,
            ], $user->id);


            $link = \Config::get('constants.__MAIN_SITE_URL__')  . '/changePaymentInfo.php?szConfirmationKey=' . $paymentInfoKey;

            $szName = $user->szFirstName . ' ' . $user->szLastName;
//            $szAppUrl = \config::get('constants.__BASE_URL__') . 'app/';
//            $szPassUrl = \config::get('constants.__BASE_URL__') . 'myPasses/';

            $location = $merchant->locations()->first();
            $merchantAddress = '';
            if ($merchant->szAddress1 != '') {
                $merchantAddress = $location->szAddress;
            }
            if ($location->szAddress2 != '') {
                $merchantAddress .= ' ' . $location->szAddress2;
            }
            if ($location->szCity != '') {
                $merchantAddress .= ', ' . $location->szCity;
            }
            if ($location->szState != '') {
                $merchantAddress .= ', ' . $location->szState;
            }
            if ($location->szRegion != '') {
                $merchantAddress .= ', ' . $location->szRegion;
            }

            $message = str_replace('szStreetAddress', $merchantAddress, $message);

            $message = str_replace('szMerchantCompany', $merchant->szCompanyName, $message);
            $message = str_replace('szPassName', $subscription->szTilte, $message);
            $message = str_replace('szMerchantName', $merchant->szName, $message);

            $message = str_replace('fAmount', number_format((float) $order->fSubscriptionAmount, 2), $message);
            $message = str_replace('fDiscountAmount', number_format((float) $order->fDiscountAmount, 2), $message);
            $message = str_replace('fPaidAmount', number_format((float) $order->fPaidAmount, 2),$message);

            $message = str_replace('idOrderNumber', $order->id, $message);
            $message = str_replace('szPaymentType', $paymentType, $message);
            $message = str_replace('http://szLink', $link, $message);
            $message = str_replace('szTransactionDate', date('M d Y h:i A', strtotime($order->dtPurchased)), $message);
            $message = str_replace('CURRENT_YEAR',date('Y'), $message);

            //$email="ashish@whiz-solutions.com";
            $message = str_replace('http://szLink', $link, $message);

            $to = $user->szEmail;
            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');

            \EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);

            # email

            $template = $this->emailTemplateRepository->findWhere(['keyname' => '__PURCHASE_PASS_NOTIFICATION_TO_ADMIN__'])->first();

            $subjectAdmin = $template['subject'];
            $messageAdmin = $template['description'];

            $messageAdmin = str_replace('szPassName', $subscription->szTilte, $messageAdmin);
            $messageAdmin = str_replace('szMerchantName', $merchant->szName, $messageAdmin);
            $messageAdmin = str_replace('szName', $szName, $messageAdmin);
            $messageAdmin = str_replace('szEmail', $to, $messageAdmin);

            $messageAdmin = str_replace('fAmount',number_format((float) $order->fSubscriptionAmount, 2), $messageAdmin);
            $messageAdmin = str_replace('fDiscountAmount',number_format((float) $order->fDiscountAmount,2), $messageAdmin);
            $messageAdmin = str_replace('fPaidAmount',number_format((float) $order->fPaidAmount, 2), $messageAdmin);

            $messageAdmin = str_replace('szPassType', 'Auto Renew', $messageAdmin);

            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
            $to = \Config::get('constants.__NOTIFICATIN_EMAIL__');

            \EmailHelper::sendEmail($from, $to, $subjectAdmin, $messageAdmin, $user->id);

            if ($order->szPaymentType == 'Credit/Debit') {
                \DB::table('tblbraintreelog')->insert([
                    'idOrder'       => $order->id,
                    'idUser'        => $order->idUser,
                    'idSubscription'    => $order->idSubscription,
                    'szDate'        => $now,
                    'iActive'       => 1,
                    'dtEndDate'     => '2099-12-31',
                    'PROFILEID'     => '',
                    'PROFILESTATUS' => $tranctionStatus,
                ]);
            } else  {
                \DB::table('tblpaypallog')->insert([
                    'dtEndDate' => '2099-12-31',
                    'PROFILEID' => '',
                    'PROFILESTATUS' => $tranctionStatus,
                    'idOrder'   => $order->id,
                    'idUser'    => $order->idUser,
                    'idSubscription'    => $order->idSubscription,
                    'szAmount'  => $order->fPaidAmount,
                    'szCartID'  => $idCart,
                ]);
            }
        }

        return $order;
    }

    /**
     * Active pass
     *
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function active_pass($data) {

        # validation

        $user = $this->userRepository->findWhere([
            'isDeleted'     => 0,
            'szMobileKey'   => $data['szMobileKey'],
        ])->first();

        if (!$user) {
            throw new \Exception ('Invalid mobile key');
        }

        $order = $this->orderRepository->find($data['orderId']);

        if ($order->idUser != $user->id) {
            throw new \Exception ('Invalid user');
        }

        $now = Carbon::now();

        if ($order->dtExpiry <= $now) {
            throw new \Exception('Your pass has expired');
        }

        if ($order->dtAvailable > $now) {
            throw new \Exception('This Pass is not available at this time');
        }

        if ($order->szPassType == 'package pass' ||
            $order->szPassType == 'gift pass' ||
            $order->szPassType == 'one time pass' ||
            $order->iLimitioins == 'Activation Number') {

            if ($order->iTotalUsedCount == $order->iLimitionCount) {
                throw new \Exception('Your pass has been used');
            }
        }

        # update order & insert user_subscription_userage

        $dtAvailable = $order->dtAvailable;
        $iActivationCount = 0;

        if ($order->iLimitions == 'Daily') {
            $dtAvailable = date('Y-m-d', strtotime('+ 1 DAY'));
        } else if ($order->iLimitions == 'Weekly') {
            $dtAvailable = date('Y-m-d', strtotime('+ 1 WEEK'));
        } else if ($order->iLimitions == 'Monthly') {
            $dtAvailable = date('Y-m-d',strtotime('+ 1 MONTH'));
        } else if ($order->iLimitions == 'Unlimited') {
            $dtAvailable = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        } else if ($order->iLimitions == 'Activation Number') {
            $dtAvailable = date('Y-m-d');
            $iActivationCount = $order->iLimitionCount;
        }

        if ($order->szPassType == 'package pass') {
            $dtAvailable = date('Y-m-d');
        }

        if ($order->iPromotional == 1) {

            $order = $this->orderRepository->update([
                'iTotalUsedCount'   => $order->iTotalUsedCount + 1,
                'dtUsed'    => $now,
                'dtExpiry'  => $now,
            ], $order->id);
        } else {

            $attributes = [
                'dtAvailable'       => $dtAvailable,
                'iTotalUsedCount'   => $order->iTotalUsedCount + 1,
                'dtUsed'    => $now,
            ];

            if ($iActivationCount > 0) {
                $attributes['iLimitionCount'] = $order->iLimitionCount - 1;
            }

            $order = $this->orderRepository->update($attributes, $order->id);
        }

        \DB::table('tblusersubscriptionusage')->insert([
            'idUser'    => $order->idUser,
            'idOrder'   => $order->id,
            'idSubscription'    => $order->idSubscription,
            'dtUsed'        => $now,
            'szIPAddress'   => $_SERVER['REMOTE_ADDR'],
        ]);

        # email for redemption

        $subscription = $this->subscriptionRepository->find($order->idSubscription);
        $merchant = $this->merchantRepository->find($subscription->idMerchant);

        if ($subscription->isRedemptionEmail == 1 && $subscription->emailRedemption != '') {

            $template = $this->emailTemplateRepository->findWhere(['keyname' => '__REDEMPTION_NOTIFICATION_EMAIL__'])->first();

            $message = $template->description;

            $subject= "{$user->szFirstName} {$user->szLastName} redeemed {$subscription->szOfferHighlight}";

            $message = str_replace('szUserName', "{$user->szFirstName} {$user->szLastName}", $message);
            $message = str_replace('szPassTitle', $subscription->szTilte, $message);
            $message = str_replace('szPassHighlight',$subscription->szOfferHighlight, $message);

            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
            $to = $subscription->emailRedemption;

            \EmailHelper::sendEmail($from, $to, $subject, $message, $subscription->idMerchant);
        }

        # on demand

        $popup = \DB::table('tblondemandpopupdetails')
            ->where('iActive', 1)
            ->where('dtOnDemandFromdate', '<=', $now)
            ->where('idMerchant', $subscription->idMerhant)
            ->orderby('dtOnDemandFromdate', 'desc')
            ->first();

        if (empty($popup)) {
            $popup = \DB::table('tblondemandpopupdetails')
                ->where('iActive', 1)
                ->where('dtOnDemandFromdate', '<=', $now)
                ->where('idMerchant', 0)
                ->orderby('dtOnDemandFromdate', 'desc')
                ->first();
        }

        $result = [
            'szTitle'   => $subscription->szTilte,
            'szName'    => "{$order->szFirstName} {$order->szLastName}",
            'iAutoRenewFlag'    => $order->iAutoRenewFlag,
            'passType'  => $order->szPassType
        ];

        if ($order->iTotalUsedCount == $order->iLimitionCount) {

            if ($result['passType'] == 'package pass') {

                $sub = \DB::table('tblsubscriptions as s')
                    ->leftJoin('tblmerchant as m', 'm.id', '=', 's.idMerchant')
                    ->where('s.isDeleted', 0)
                    ->where('s.iActive', 1)
                    ->where('s.szPassType', 'package pass')
                    ->where('s.fPrice', '>', $subscription->fPrice)
                    ->where('m.isDeleted', 0)
                    ->where('m.iActive', 1)
                    ->where('m.id', $subscription->idMerchant)
                    ->orderby('s.fPrice', 'asc')
                    ->first();

                if ($sub) {
                    $result['iUpgradePopUpFlag'] = 1;
                } else {
                    $result['iUpgradePopUpFlag'] = 0;

                    if ($result['iAutoRenewFlag'] == 1 && $result['passType'] == 'package pass') {

                        $szPaymentAmount = $subscription->fPrice;
                        $payment_method_token = $order->szPaymentToken;
                        $paymentType = $order->szPaymentType;
                        $data->idPass = $order->idSubscription;
                        $result["paymentMethodToken"] = $payment_method_token;

                        if ($subscription) {

                            if ($order->idParentOrder > 0) {
                                $idOrder_old = $order->iParentOrder;
                            } else {
                                $idOrder_old = $order->id;
                            }

                            $this->placeOrder($user->id, $merchant->id, $subscription->id, $payment_method_token, $paymentType, $szPaymentAmount, $idOrder_old);
                        }
                    }
                }

                $result['iLastPassFlag'] = 1;
            } else {

                $result['iLastPassFlag'] = 0;
                $result['iUpgradePopUpFlag'] = 0;
            }

            $result['iOnDemandPopUpFlag'] = 0;
            $result['szOnDemandPopUpText'] = '';
        } else {

            if (!empty($popup) && $popup->szOnDemandPopup !='' &&
                strtotime($popup->dtOnDemandFromdate) <= $now &&
                ($popup->dtOnDemandTodate == '0000-00-00' || strtotime($popup->dtOnDemandTodate) >= $now)) {

                $result['iOnDemandPopUpFlag'] = 1;
                $result['szOnDemandPopUpText'] = $popup->szOnDemandPopup;
            } else {

                $result['iOnDemandPopUpFlag'] = 0;
                $result['szOnDemandPopUpText'] = '';
            }

            $result['iLastPassFlag'] = 0;
            $result['iUpgradePopUpFlag'] = 0;
        }

        $result['szName'] = "{$order->szFirstName} {$order->szLastName}";
        $result['szMerchant'] = $merchant->szName;
        $result['szShortDescription'] = \StringHelper::trimString($subscription->szShortDescription, 150);

        if ($subscription->szUploadImageName != '' && file_exists(\Config::get('constants.__UPLOAD_SUBSCRIPTION_IMAGE__') . $subscription->szUploadImageName)) {
            $result['szPassImg'] = \Config::get('constants.__UPLOAD_SUBSCRIPTION_IMAGE_URL__') . $subscription->szUploadImageName;
        } else {
            $result['szPassImg'] = '';
        }

        if ($user->szUploadFileName != '' && file_exists(\Config::get('constants.__MAIN_SITE_PATH__') . \Config::get('constants.__UPLOAD_USER_IMAGE_DIR__') . $user->szUploadFileName)) {
            $result['szProfileImg'] = \Config::get('constants.__MAIN_SITE_URL__') . \Config::get('constants.__UPLOAD_USER_IMAGE_DIR__') . $user->szUploadFileName;
        } else {
            $result['szProfileImg'] = \Config::get('constants.__MAIN_SITE_URL__') . \Config::get('constants.__IMAGE_DIR__') . "/user-image.jpg";
        }

        $address = \LocationHelper::getZipCode($data['szLatitude'],$data['szLongitude']);

        if ($address) {
            \DB::table('tbltrackgeolocationredemption')->insert([
                'idUser' => $order->idUser,
                'idPass' => $order->idSubscription,
                'idMerchant' => $subscription->idMerchant,
                'idOrder' => $order->id,
                'szAddress1' => $address['szAddress1'],
                'szAddress2' => $address['szAddress2'],
                'szCity' => $address['szCity'],
                'szState' => $address['szState'],
                'szZipCode' => $address['szZipCode'],
                'szCountry' => $address['szCountry'],
                'szLattitude' => $address['szLatitude'],
                'szLongitude' => $address['szLongitude'],
                'dtRedeem' => $now,
            ]);
        }

        return $result;
    }

    public function purchasedSubscriptions($idMerchant) {
        $now = Carbon::now();

        $query = \DB::table('tblorder')
            ->from('tblorder as o')
            ->join('tblsubscriptions as s', 'o.idSubscription', '=', 's.id')
            ->where('s.idMerchant', $idMerchant)
            ->where('o.iCompleted', 1)
            ->where('dtExpiry', '>', $now)
            ->groupby('o.idSubscription')
            ->select(\DB::raw('count(o.idUser) as total'), 'o.idSubscription', 's.szTilte');

//        \Log::info($query->toSql());

        $result = $query->get();

        return $result;
    }

#TODO merchant / totalPassPaymentByMerchantId()
    public function totalPaidAmountInMonth($idMerchant) {
        $first_day_of_month = new Carbon('first day of this month');

//        \Log::info('First day of month : ' . $first_day_of_month->toDateTimeString());

        $query = \DB::table('tblorder')
            ->from('tblorder as o')
            ->join('tblsubscriptions as s', 'o.idSubscription', '=', 's.id')
            ->where('s.idMerchant', $idMerchant)
            ->where('o.iCompleted', 1)
            ->where('dtPurchased', '>=', $first_day_of_month)
            ->select(\DB::raw('sum(fPaidAmount) AS fTotalAmount'));

        //        \Log::info($query->toSql());

        $result = $query->get();
        $result = $result[0];

        return $result->fTotalAmount;
    }

    /**
     * Get pass order
     *
     * @param $idUser
     * @param $idSubscription
     * @param $iType
     */
    function giftPassOrder($idUser, $idSubscription, $iType) {

        $now = Carbon::now();

        $user = $this->userRepository->findWhere([
            'id' => $idUser,
        ])->first();

        $subscription = $this->subscriptionRepository->findWhere([
            'id' => $idSubscription,
        ])->first();

        if ($subscription->iCountFinalExpiry > 0) {
            $dtExpiry = date("Y-m-d", strtotime('+'. $subscription->iCountFinalExpiry . ' MONTH'));
        } else {
            $dtExpiry = '2099-12-31';
        }

        $order = $this->orderRepository->create([
            'idUser'    => $user->id,
            'idSubscription'    => $subscription->id,
            'szFirstName'   => $user->szFirstName,
            'szLastName'    => $user->szLastName,
            'szEmail'       => $user->szEmail,
            'szZipCode'     => $user->szZipCode,
            'szSubscriptionPeriod'  => 'Gift Pass',
            'dtExpiry'      => $dtExpiry,
            'isGifted'      => 1,
            'iCompleted'    => 1,
            'dtPurchased'   => $now,
            'dtAvailable'   => $now,
            'PROFILESTATUS' => 'Active',
            'szPassType'    => 'gift pass',
            'iLimitionCount'    => $subscription->iLimitionCount,
        ]);

        $this->uosMappingRepository->create([
            'idUser'    => $user->id,
            'idOrder'   => $order->id,
            'idSubscription'    => $subscription->id,
            'iLimitions'        => $subscription->iLimitionCount,
            'iLimitionCount'    => $subscription->iLimitionCount,
            'szPassType'        => 'gift pass',
        ]);

        if ($iType == 2) {

            $merchant = $this->merchantRepository->findWhere([
                'id' => $subscription->idMerchant,
            ])->first();

            $template = $this->emailTemplateRepository->findWhere([
                'keyname' => '__GIFT_PASS_EMAIL__'
            ])->first();

            $subject = $template->subject;
            $message = $template->description;

            $message = str_replace('szPassName', $subscription->szTilte, $message);
            $message = str_replace('szMerchantName', $merchant->szName, $message);

            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
            $to = $user->szEmail;

            \EmailHelper::sendEmail($from, $to, $subject, $message, $idUser);
        }
    }
}