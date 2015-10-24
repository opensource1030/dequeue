<?php

namespace App\Http\Controllers\Api;

use App\Database\Services\PaypalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

use Illuminate\Support\Facades\Validator;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

use App\Database\Serializers\CustomSerializer;
use App\Database\Transformers\OrderTransformer;
use App\Database\Transformers\OrderDetailTransformer;
use App\Database\Services\OrderService;
use Mockery\CountValidator\Exception;

use Braintree_Exception;
use Braintree_Customer;
use Braintree_Transaction;
use Braintree_Subscription;
use Braintree_ClientToken;

class OrderController extends ApiController
{

    protected $orderService;
    protected $paypalService;

    public function __construct (
        ResponseFactory 	$response,
        Request 			$request,
        OrderService        $orderService,
        PaypalService       $paypalService
    ) {
        parent::__construct($response, $request);

        $this->orderService = $orderService;
        $this->paypalService = $paypalService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'idPass'        => 'required',
            'szPeriod'      => 'required|in:Monthly,Yearly',
            'szEmail'       => 'required|email',
            'szPayment'     => 'required|in:Braintree,PayPal',
//            'szPaymentToken'=> 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $data = $this->request->all();
        $data['idSubscription'] = $data['idPass'];
//        array_add($data, 'idSubscription', $data['idPass']);
        $now = Carbon::now();

//        try {
            if ($data['szPayment'] == 'Braintree') {
                if (!isset($data['szPaymentToken']) || $data['szPaymentToken'] == '') {
                    throw new Exception('Payment Token is required');
                }
            }

            $user = $this->orderService->userRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
            if (empty($user)) {
                throw new Exception('Invalid mobile key');
            }

            $subscription = $this->orderService->subscriptionRepository->find($data['idSubscription']);
            if (empty($subscription)) {
                throw new Exception('No subscription found');
            }

            if ($data['szPeriod'] == 'Monthly') {
                $dtExpiry = date('Y-m-d',strtotime('+ 30 DAY'));
            } else if ($data['szPeriod'] == 'Yearly') {
                $dtExpiry = date('Y-m-d',strtotime('+ 1 YEAR'));
            }

            if ($subscription->iLimition == 'Activation Number' && $subscription->iCountFinalExpiry > 0) {
                $dtExpiry=date('Y-m-d',strtotime(date('Y-m-d', strtotime($dtExpiry)) . ' +'. $subscription->iCountFinalExpiry . ' month'));
            }

            $autoRenewFlag = 0;
            $activation_count = 0;

            if ($subscription->szPassType == 'package pass') {
                $dtExpiry = '2099-12-31';
                $activation_count = $subscription->iActivationCount;
                $passAmount = $data['fPassAmount'];
                $autoRenewFlag = $subscription->iAutoRenewFlag;
                $data['szPeriod'] = 'Unlimited';
            } else {

                $passAmount = $subscription->fPrice;
                if (isset($data['iLimitionCount']) && $data['iLimitionCount'] > 0)
                    $activation_count = $data['iLimitionCount'];
            }

            $this->orderService->userRepository->update([
                'szZipCode' => $data['szZipCode']
            ], $user->id);

            if ($data['szPayment'] == 'Braintree') {
                $data['szPayment'] = 'Credit/Debit';
            }

            $order = $this->orderService->orderRepository->create([
                'idUser'        => $user->id,
                'idSubscription'        => $data['idSubscription'],
                'fSubscriptionAmount'   => $data['fPassAmount'],
                'szFirstName'   => $data['szFirstName'],
                'szLastName'    => $data['szLastName'],
                'szEmail'       => $data['szEmail'],
                'szZipCode'     => $data['szZipCode'],
                'szPaymentType' => $data['szPayment'],
                'szSubscriptionPeriod'  => $data['szPeriod'],
                'dtExpiry'          => $now,
                'iLimitionCount'    => $activation_count,
                'szPassType'        => $subscription->szPassType,
                'iAutoRenewFlag'    => $autoRenewFlag,
                'szPaymentToken'    => $data['szPaymentToken'],
            ]);

//            if (isset($data['szPormoCode']) && $data['szPromoCode'] != '') {
//                $this->applyPromoCode($data, true);
//            }

            $this->orderService->uosMappingRepository->create([
                'idUser'    => $user->id,
                'idOrder'   => $order->id,
                'idSubscription'    => $data['idSubscription'],
                'fPrice'    => $passAmount,
                'iPeriod'   => $subscription->iPeriod,
                'iYearlyPeriod' => $subscription->iYearlyPeriod,
                'fYearlyPrice'  => $subscription->fYearlyPrice,
                'iLimitions'    => $subscription->iLimitions,
                'iLimitionCount'    => $subscription->iLimitionCount,
                'szPassType'    => $subscription->szPassType,
            ]);

            if ($data['szPayment'] == 'PayPal') {
                $this->orderService->paymentResponse(
                    $order->id,
                    $data['idCart'],
                    $data['tranctionStatus'],
                    $data['profileId'],
                    '',
                    $data['amountSubscription']
                );
            } else if ($data['szPayment'] == 'PayPal') {
                $this->orderService->paymentResponseBrainTree(
                    $order->id,
                    $data['idCart'],
                    $data['tranctionStatus'],
                    $data['profileId'],
                    '',
                    $data['amountSubscription']
                );
            }

            if ($order) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $order = new Item($order, new OrderTransformer());
                $order = $fractalManager->createData($order)->toArray();

                return $this->respond($order);
            }
            return $this->respond();
//        } catch (\Exception $e) {
//            return $this->respondWithErrors($e->getMessage(), $e->getCode());
//        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = $this->orderService->orderRepository->find($id);

        if ($order) {

            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $fractalManager->parseIncludes('subscription');

            $order = new Item($order, new OrderDetailTransformer());
            $order = $fractalManager->createData($order)->toArray();

            return $this->respond($order);
        }

        return $this->respond();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {
            $order = $this->orderService->cancel([
                'id' => $id,
                'szMobileKey' => $this->request['szMobileKey'],
            ]);

            return $this->respond($order);
        } catch(\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

#TODO user purchased passes
    public function find_by_user() {

//        Log::info('SubscriptionController find_by_merchant');

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {
            $model = $this->orderService->find_by_user($data);

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new OrderDetailTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function find_package_pass_by_user() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            $model = $this->orderService->find_package_pass_by_user($data);

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new OrderDetailTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Add promotional pass
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function order_promotional_pass() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'       => 'required',
            'subscriptionId'    => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $subscriptionId = $this->request['subscriptionId'];

        try {

            $subscription = $this->orderService->subscriptionRepository->findWhere([
                'id'        => $subscriptionId,
                'isDeleted'     => 0,
                'iPromotional'  => 1,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('Not a promotional pass');
            }

            $user = $this->orderService->userRepository->findWhere([
                'isDeleted'     => 0,
                'szMobileKey'   => $szMobileKey
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid user');
            }

            $model = $this->orderService->orderRepository->findWhere([
                'iPromotional'  => 1,
                'iCompleted'    => 1,
                'idUser'        => $user->id,
            ])->first();

            if ($model) {
                throw new \Exception('You have already claimed a promotional pass');
            }

            $model = $this->orderService->order_promotional_pass($user->id, $subscription->id);

            if ($model) {

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Item($model, new OrderTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Add promotional pass using promo code
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function order_promotional_pass_with_coupon() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szCouponCode'  => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $szCouponCode = $this->request['szCouponCode'];
        $now = Carbon::now();

        try {

            $subscription = $this->orderService->subscriptionRepository->findWhere([
                'szCouponCode' => $szCouponCode,
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('No subscription found with the coupon code');
            }

            $inviteCode = $this->orderService->inviteRepository->findWhere([
                'szName' => $szCouponCode,
                'isDeleted' => 0,
                'iActive'   => 1,
            ])->first();

            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey'   => $szMobileKey,
                'isDeleted'     => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $order = $this->orderService->orderRepository->getModel()->newQuery()
                ->whereHas('subscription', function($query) use ($subscription) {
                    $query->where('isDeleted', 0);
                    $query->where('idMerchant', $subscription->idMerchant);
                })
                ->where('iPromotional', 1)
                ->where('iCompleted', 1)
                ->where('idUser', $user->id)
                ->first();

            if ($order) {
                $merchant = $this->orderService->merchantRepository->find($subscription->idMerchant);

                $uiMapping = $this->orderService->uiMappingRepository->findWhere([
                    'idSignupUser'  => $user->id
                ])->first();

                if ($uiMapping) {
                    throw new \Exception("You have already claimed a promotional pass - {$merchant->szName}");
                }

                if ($inviteCode) {

                    if ($inviteCode->dtStart != '0000-00-00' && $inviteCode->dtStart > $now) {
                        throw new \Exception('Coupon Code not available at the time');
                    }

                    if ($inviteCode->dtEnd != '0000-00-00' && $inviteCode->dtEnd < $now) {
                        throw new \Exception('Coupon Code no longer available');
                    }

                    if ($inviteCode->iUserLimit > 0) {
                        $totalUser = $this->orderService->userRepository->findWhere([
                            'isDeleted' => 0,
                            'szInviteCode'  => $szCouponCode,
                        ])->count();

                        if ($totalUser >= $inviteCode->iUserLimit) {
                            throw new \Exception('Coupon Code over used');
                        }
                    }

                    $uiMapping = $this->orderService->uiMappingRepository->create([
                        'idReferUser'   => 0,
                        'idSignupUser'  => $user->id,
                        'szInviteCode'  => $szCouponCode,
                        'fReferralcredit'   => $inviteCode->fCreditAmount,
                        'dtSignup'  => $now,
                    ]);

                    # insert_signup_credit_history

                    \DB::table('tblusercreditdebithistory')->insert([
                        'dUser'     => $inviteCode->idSignupUser,
                        'fPrice'    => $inviteCode->fReferralcredit,
                        'idinvitecodemapped'    => $inviteCode->id,
                        'szcredittype'  => 'signup',
                        'sztransactiontype' => 'credit',
                        'datetime'  => $now,
                    ]);

                    $this->orderService->userRepository->update([
                        'fTotalCredit' => $user->fTotalCredit + $inviteCode->fReferralcredit,
                    ], $user->id);

                    #

                    return $this->respond('Coupon Code added');
                }

                return $this->respondWithErrors('No invite code found');

            } else {
                $order = $this->orderService->order_promotional_pass($user->id, $subscription->id);

                if ($order) {
                    return $this->respond([], 'Promo added');
                }

                return $this->respondWithErrors('Fail in order promo');
            }

            return $this->respondWithErrors();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Activate / Deactivate subscription
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function active_pass() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'orderId'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        try {
            $result = $this->orderService->active_pass($this->request->all());
            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * order by Braintree API
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function order_with_braintree() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szFirstName'   => 'required',
            'szLastName'    => 'required',
            'szYear'        => 'required|digits:4',
            'szMonth'       => 'required|digits_between:1,2',
            'szCardNumber'  => 'required',
            'szCVV'         => 'required',
            'szZipCode'     => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            if($data['szYear'] < date('y') || ($data['szYear'] == date('y') && $data['szMonth'] < date('m'))) {
                throw new \Exception('Card expired');
            }

            # user

            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey' => $data['szMobileKey'],
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Unauthorized user');
            }

            $attributes = [
                'idCart'        => '',
                'profileId'     => '',
                'transactionStatus' => '',
                'szRecurringPeriod' => '',
                'amountSubscription'    => 0,
                'paymentMethodToken'    => '',
            ];
            $szPeriod = '';

            # subscription

            $subscription = $this->orderService->subscriptionRepository->findWhere([
                'id' => $data['idSubscription'],
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('This Pass is not available at this time');
            }

            if(strtolower($subscription->szPassType) != 'package pass' && strtolower($subscription->zPassType) !='one time pass') {
                $szPeriod = $data->szPeriod;
            }

            $result = Braintree_Customer::create(array(
                "firstName" => $data['szFirstName'],
                "lastName"  => $data['szLastName'],
                "creditCard"    => array(
                    "number"            => $data['szCardNumber'],
                    "expirationMonth"   => $data['szMonth'],
                    "expirationYear"    => $data['szYear'],
                    "cvv"               => $data['szCVV']
                )
            ));

            if ($result->success) {
                // \Log::info("Success! Customer ID: " . $result->customer->id);

                $customer_id = $result->customer->id;
                $customer = Braintree_Customer::find($customer_id);
                $payment_method_token = $customer->creditCards[0]->token;

                if(strtolower($subscription->szPassType) == 'package pass' || strtolower($subscription->szPassType) == 'one time pass') {

                    $attributes["idCart"] = $customer_id;
                    $attributes["paymentMethodToken"] = $payment_method_token;
                } else {

                    if(strtolower($szPeriod)=='monthly') {
                        $firstBillingDate = date('Y-m-d',strtotime("+1 MONTH", strtotime(date('Y-m-d'))));
                        $planType = \Config::get('constants.__MONTHLY_PLAN__');
                    } else if(strtolower($szPeriod)=='yearly') {
                        $firstBillingDate = date('Y-m-d',strtotime("+1 YEAR", strtotime(date('Y-m-d'))));
                        $planType = \Config::get('constants.__YEARLY_PLAN__');
                    }

                    $result3 = Braintree_Subscription::create(array(
                        'paymentMethodToken'    => $payment_method_token,
                        'planId'    => $planType,
                        'price'     => $data['PassAmount'],
                        'trialPeriod'       => false,
                        'firstBillingDate'  => $firstBillingDate
                    ));

                    if ($result3->success) {
                        $attributes["idCart"] = $customer_id;
                        $attributes["profileId"] = $result3->subscription->id;
                        $attributes["transactionStatus"] = 'Active';
                        $attributes["szRecurringPeriod"] = $data['szPeriod'];
                        $attributes["amountSubscription"] = $data['fPassAmount'];
                        $attributes["paymentMethodToken"] = $payment_method_token;
                    } else {
                        return $this->respondWithErrors($result3->errors, 30001);
                    }
                }
            } else {
                return $this->respondWithErrors($result->errors, 30001);
            }

            # merchant

            $merchant = $this->orderService->merchantRepository->find($subscription->idMerchant);

            $serviceFee = 0.00;

            if ($merchant->fServiceFeePercentage > 0.00) {
                $serviceFee = $data['fPassAmount'] * $merchant->fServiceFeePercentage * 0.01;
            }

            if ($merchant->szBraintreeMerchantId) {

                $result1 = Braintree_Transaction::sale(array(
                    "amount" => $data->fPassAmount,
                    "merchantAccountId" => $merchant->szBraintreeMerchantId,
                    "creditCard" => array(
                        "number"            => $data['szCardNumber'],
                        "expirationMonth"   => $data['szMonth'],
                        "expirationYear"    => $data['szYear'],
                        "cvv"               => $data['szCVV'],
                    ),
                    "options" => array(
                        "submitForSettlement"   => true,
                        "holdInEscrow"          => true,
                    ),
                    "serviceFeeAmount" => $serviceFee
                ));
            }
            else
            {
                $result1 = Braintree_Transaction::sale(array(
                    "amount" => $data['fPassAmount'],
                    "creditCard" => array(
                        "number"            => $data['szCardNumber'],
                        "expirationMonth"   => $data['szMonth'],
                        "expirationYear"    => $data['szYear'],
                        "cvv"               => $data['szCVV'],
                    ),
                    "options" => array(
                        "submitForSettlement" => true
                    )
                ));
            }

            if ($result1->success!=1 ) {

                if ($attributes["profileId"] != '') {
                    $result = Braintree_Subscription::cancel($attributes["profileId"]);
                }

                throw new \Exception($result1->message);
            }

            $passType = $subscription->szPassType;
            $szPayment = 'Credit/Debit';
            $autoRenewFlag = 0;
            $activation_count = 0;
            $now = Carbon::now();

            if($passType == 'package pass') {

                $dtExpiry='2099-12-31';
                $activation_count = $subscription->iActivationCount;
                $passAmount = $data['fPassAmount'];
                $autoRenewFlag = $subscription->iAutoRenewFlag;
            } else {

                $activation_count = $subscription->iActivationCount;
                $passAmount = $subscription->fPrice;

                if (strtolower(sszPeriod) == 'monthly') {
                    $dtExpiry = date('Y-m-d', strtotime('+ 30 DAY'));
                } else if (strtolower(sszPeriod) == 'yearly') {
                    $dtExpiry = date('Y-m-d', strtotime('+ 1 YEAR'));
                }
            }

            $order = $this->orderService->orderRepository->create([
                'idUser'        => $user->id,
                'idSubscription'        => $data['idSubscription'],
                'fSubscriptionAmount'   => $data['fPassAmount'],
                'szFirstName'   => $data['szFirstName'],
                'szLastName'    => $data['szLastName'],
                'szEmail'       => $user->szEmail,
                'szZipCode'     => $data['szZipCode'],
                'szPaymentType' => $szPayment,
                'szSubscriptionPeriod'  => $data['szPeriod'],
                'dtExpiry'      => $dtExpiry,
                'iLimitionCount'        => $activation_count,
                'szPassType'    => $passType,
                'iAutoRenewFlag'        => $autoRenewFlag,
                'iCompleted'    => 1,
                'dtPurchased'   => $now,
                'dtAvailable'   => $now,
                'iTotalPaymentCount'    => 1,
                'fPaidAmount'   => $data['fPassAmount'],
                'PROFILESTATUS' => $attributes['transactionStatus'],
                'PROFILEID'     => $attributes['profileId'],
                'TRANSACTIONID' => $attributes['idCart'],
                'szPaymentToken'=> $payment_method_token,
                'fServiceFee'   => $serviceFee,
            ]);

            $this->orderService->uosMappingRepository->create([
                'idUser'    => $user->id,
                'idOrder'   => $order->id,
                'idSubscription' => $subscription->id,
                'fPrice'    => $passAmount,
                'iPeriod'   => $subscription->iPeriod,
                'iYearlyPeriod' => $subscription->iYearlyPeriod,
                'fYearlyPrice'  => $subscription->fYearlyPrice,
                'iLimitions'    => $subscription->iLimitions,
                'iLimitionCount'    => $activation_count,
                'szPassType'    => $passType,
            ]);

            if ($merchant->szBraintreeMerchantId != '') {
                $this->orderService->orderRepository->upate([
                    'iPayoutPaid'   => 1,
                    'dtPayoutPaid'  => $now,
                ], $order->id);
            }

            # email

            $szName = $user->szFirstName. ' ' . $user->szLastName;
            $szAppUrl = \Config::get('constants.__MAIN_SITE_URL__') . 'app/';
            $szPassUrl = \Config::get('constants.__MAIN_SITE_URL__') . 'myPasses/';

            $template = $this->orderService->emailTemplateRepository->findWhere(['keyname' => '__SUBSCRIPTION_CONFIRMATION_EMAIL__'])->first();

            $subject = $template->subject;
            $message = $template->description;

            $subject = str_replace('szNumber', $order->id, $subject);

            $message = str_replace('szNumber', $order->id, $message);
            $message = str_replace('szName', $szName, $message);
            $message = str_replace('szPeriod', $order->szSubscriptionPeriod, $message);
            $message = str_replace('szAmount',"Subscription Amount:- $" . number_format($order->fPaidAmount, 2), $message);
            $message = str_replace('szActivation',$order->iLimitions, $message);
            $message = str_replace('szSubscriptionName',$subscription->szTilte, $message);
            $message = str_replace('szAppUrl', $szAppUrl, $message);
            $message = str_replace('szPassUrl', $szPassUrl, $message);

            $to = $user->szEmail;
            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
            \EmailHelper::sendEmail($from, $to, $subject, $message, $order->idUser);

            if ($this->orderService->insertPaymentBrainTreeLog($order->id, $attributes['profileId'], $attributes['transactionStatus'])) {

            }

            # order

            if ($order) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $order = new Item($order, new OrderTransformer());
                $order = $fractalManager->createData($order)->toArray();

                return $this->respond($order);
            }

            return $this->respond();

        } catch (Braintree_Exception_NotFound $e) {

            if ($attributes["profileId"] != '') {
                Braintree_Subscription::cancel($attributes["profileId"]);
            }

            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Update auto renew flag
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function toggle_auto_renew_flag() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'orderId'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $orderId = $this->request['orderId'];

        try {
            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $order = $this->orderService->orderRepository->find($orderId);

            if (empty($order)) {
                throw new \Exception('No order found');
            }

            if ($order->idUser != $user->id) {
                throw new \Exception('Unauthorized user');
            }

            # main process

            if ($order->szPassType == 'subscription pass') {
                if ($order->iAutoRenewFlag == 0) {
                    $order = $this->orderService->orderRepository->update([
                        'iCancelSubscriptionFlag' => 1,
                    ], $order->id);
                } else {
                    $order = $this->orderService->orderRepository->update([
                        'iCancelSubscriptionFlag' => 0,
                    ], $order->id);
                }
            } else {
                if ($order->iAutoRenewFlag == 0) {
                    $order = $this->orderService->orderRepository->update([
                        'iAutoRenewFlag' => 1,
                    ], $order->id);
                } else {
                    $order = $this->orderService->orderRepository->update([
                        'iAutoRenewFlag' => 0,
                    ], $order->id);
                }
            }

            if ($order) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $order = new Item($order, new OrderTransformer());
                $order = $fractalManager->createData($order)->toArray();

                return $this->respond($order);
            }

            return $this->respondWithErrors();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Re-new package pass
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function renew_order() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'orderId'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $orderId = $this->request['orderId'];

        try {

            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $order = $this->orderService->orderRepository->find($orderId);

            if (empty($order)) {
                throw new \Exception('No order found');
            }

            if ($order->idUser != $user->id) {
                throw new \Exception('Unauthorized user');
            }

            $subscription = $this->orderService->subscriptionRepository->findWhere([
                'id' => $order->idSubscription,
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('This Pass is not available at this time');
            }

            $merchant = $this->orderService->merchantRepository->find($subscription->idMerchant);

            # main process

            $szPaymentAmount = $subscription->fPrice;
            $payment_method_token = $order->szPaymentToken;
            $paymentType = $order->szPaymentType;

            if ($order->idParentOrder > 0) {
                $idOrder_old = $order->idParentOrder;
            } else {
                $idOrder_old = $order->id;
            }

            $order = $this->orderService->placeOrder($user->id, $merchant->id, $subscription->id, $payment_method_token, $paymentType, $szPaymentAmount, $idOrder_old);

            # order

            if ($order) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $order = new Item($order, new OrderTransformer());
                $order = $fractalManager->createData($order)->toArray();

                return $this->respond($order);
            }

            return $this->respond();

        } catch (Braintree_Exception_NotFound $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * get braintree token or make a payment
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function get_braintree_token() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        try {
            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user )) {
                throw new \Exception('Invalid mobile key');
            }

            if ($user->szPaymentToken != '') {

                if (!isset($this->request['szPaymentAmount']) || $this->request['szPaymentAmount'] < 0.01) {
                    throw new \Exception('Payment Amount must be 0.01 or more');
                }

                if (!isset($this->request['idPass']) || $this->request['idPass'] <= 0) {
                    throw new \Exception('Pass Id required');
                }

                $szPaymentAmount = $this->request['szPaymentAmount'];
                $idSubscription = $this->request['idPass'];
                $payment_method_token = $user->szPaymentToken;

                $subscription = $this->orderService->subscriptionRepository->find($idSubscription);

                if (empty($subscription)) {
                    throw new \Exception('No subscription found');
                }

                if (strtolower($subscription->szPassType) != 'package pass' && strtolower($subscription->szPassType) != 'one time pass') {

                    if (!isset($this->request['szPeriod']) || $this->request['szPeriod'] == '') {
                        throw new \Exception('Period required');
                    }

                    $szPeriod = $this->request['szPeriod'];
                }

                $last_order = $this->orderService->orderRepository->findWhere([
                    'idUser' => $user->id
                ])->sortByDesc('id')->first();

                if ($last_order && $last_order->szPaymentType != '') {
                    $paymentType = $last_order->szPaymentType;
                } else {
                    $paymentType = 'Credit/Debit';
                }

                if (strtolower($subscription->szPassType) != 'package pass' && strtolower($subscription->szPassType) != 'one time pass') {
                    if ($szPeriod == 'Monthly') {
                        $firstBillingDate = date('Y-m-d', strtotime("+1 MONTH", strtotime(date('Y-m-d'))));
                        $planType = \Config::get('constants.__MONTHLY_PLAN__');
                    } else if ($szPeriod == 'Monthly') {
                        $firstBillingDate = date('Y-m-d', strtotime("+1 YEAR", strtotime(date('Y-m-d'))));
                        $planType = \Config::get('constants.__YEARLY_PLAN__');
                    }

                    $result3 = Braintree_Subscription::create(array(
                        'paymentMethodToken' => $payment_method_token,
                        'planId' => $planType,
                        'price' => $szPaymentAmount,
                        'trialPeriod' => false,
                        'firstBillingDate' => $firstBillingDate
                    ));

                    if ($result3->success != 1) {
                        throw new \Braintree_Exception('Fail in Braintree_Subscription');
                    }

                    $res["profileId"] = $result3->subscription->id;
                    $res["tranctionStatus"] = 'Active';
                    $res["szRecurringPeriod"] = $szPeriod;
                    $res["amountSubscription"] = $szPaymentAmount;
                    $res["paymentMethodToken"] = $payment_method_token;
                } else {
                    $res['paymentMethodToken'] = $payment_method_token;
                }

                $merchant = $this->orderService->merchantRepository->find($subscription->idMerchant);

                if (empty($merchant)) {
                    throw new \Exception('No merchant found');
                }

                $this->orderService->placeOrder($user->id, $merchant->id, $subscription->id, $payment_method_token, $paymentType, $szPaymentAmount);

                return $this->respond([
                    'purchase_status' => 'COMPLETED'
                ]);
            } else {
                throw new \Braintree_Exception('No current payment token');
            }
        } catch (\Braintree_Exception $be) {

            $clientToken = Braintree_ClientToken::generate(array(
                "customerId" => ""
            ));

            if ($clientToken != '') {
                return $this->respond([
                    'purchase_status' => 'CLIENT TOKEN',
                    'Client_Token' => $clientToken,
                ]);
            } else {
                return $this->respondWithErrors('Did not get client token');
            }
        } catch (\Exception $e) {
//            throw $e;
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Order using Braintree Nonce
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function order_with_braintree_nonce() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $idSubscription = $this->request['idPass'];
        $szPaymentNonce = $this->request['szNonce'];

        $szPaymentType = $this->request['szPaymentType'];
        $szPaymentAmount = $this->request['szPaymentAmount'];

        $res = [
            'idCart'    => 0,
            'profileId' => '',
            'transactionStatus' => '',
            'szRecurringPeriod' => '',
            'amountSubscription'    => 0,
            'paymentMethodToken'    => ''
        ];

        try {
            
            $user = $this->orderService->userRepository->findWhere([
                'isDeleted' => 0,
                'szMobileKey' => $szMobileKey
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $subscription = $this->orderService->subscriptionRepository->findWhere([
                'id' => $idSubscription,
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('No subscription found');
            }

            if (strtolower($subscription->szPassType) != 'package pass' && strtolower($subscription->szPassType) != 'one time pass') {

                if (!isset($this->request['szPeriod'])) {
                    throw new \Exception('szPeriod is required');
                }

                $szPeriod = $this->request['szPeriod'];
            }

            $result6 = Braintree_Customer::create(array(
                "firstName" => $user->szFirstName,
                "lastName"  => $user->szLastName,
            ));

            if ($result6->success != 1) {
//                $result->errors->deepAll()
                return $this->respondWithErrors($result6->errors->deepAll());
            }

            $customer_id = $result6->customer->id;

            $result = Braintree_PaymentMethod::create(array(
                'customerId' => $customer_id,
                'paymentMethodNonce' => $szPaymentNonce,
            ));

            $customer = Braintree_Customer::find($customer_id);

            if ($szPaymentType == 'creditcard') {
                $paymentType = 'Credit/Debit';
                $payment_method_token = $customer->creditCards[0]->token;
            } else {
                $paymentType = 'PayPal';
                $payment_method_token = $customer->paypalAccounts[0]->token;
            }

            if (strtolower($subscription->szPassType) != 'package pass' && strtolower($subscription->szPassType) != 'one time pass') {

                if (strtolower($szPeriod) == 'monthly') {

                    $firstBillingDate = date('Y-m-d', strtotime("+1 MONTH", strtotime(date('Y-m-d'))));
                    $planType = \Config::get('constatns.__MONTHLY_PLAN__');
                } else if (strtolower($szPeriod) == 'yearly') {

                    $firstBillingDate = date('Y-m-d',strtotime("+1 YEAR", strtotime(date('Y-m-d'))));
                    $planType = \Config::get('constatns.__YEARLY_PLAN__');
                }

                $result3 = Braintree_Subscription::create(array(
                    'paymentMethodToken' => $payment_method_token,
                    'planId' => $planType,
                    'price' => $szPaymentAmount,
                    'trialPeriod' => false,
                    'firstBillingDate' => $firstBillingDate
                ));

                if ($result3->success != 1) {
                    return $this->respondWithErrors($result6->errors());
                }

                $res["idCart"]=$customer_id;
                $res["profileId"]=$result3->subscription->id;
                $res["tranctionStatus"]='Active';
                $res["szRecurringPeriod"]=$szPeriod;
                $res["amountSubscription"]=$szPaymentAmount;
                $res["paymentMethodToken"]=$payment_method_token;

            } else {
                $res["idCart"]=$customer_id;
                $res["paymentMethodToken"]=$payment_method_token;
            }

            $merchant = $this->orderService->merchantRepository->find($subscription->idMerchant);

            $order = $this->orderService->placeOrder($user->id, $merchant->id, $subscription->id, $payment_method_token, $paymentType, $szPaymentAmount);

            if ($order) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $order = new Item($order, new OrderTransformer());
                $order = $fractalManager->createData($order)->toArray();

                return $this->respond($order);
            }

            return $this->respondWithErrors('no order created');
        } catch (\Braintree_Exception $be) {

            if ($res["profileId"] != '') {
                Braintree_Subscription::cancel($res["profileId"]);
            }

//            throw $be;
            return $this->respondWithErrors($be->getMessage(), $be->getCode());
        } catch (\Exception $e) {
//            throw $e;
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Make payment by Braintree PayPal Nonce
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function pay_with_paypal_nonce() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        $szPaymentAmount = $this->request['szPaymentAmount'];
        $szPaymentNonce = $this->request['szNonce'];

        try {
            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $result6 = Braintree_Customer::create(array(
                "firstName" => $user->szFirstName,
                "lastName"  => $user->szLastName,
            ));

            $customer_id =$result6->customer->id;

            $result = Braintree_PaymentMethod::create(array(
                'customerId' => $customer_id,
                'paymentMethodNonce' => $this->szPaymentNounce
            ));

            $customer = Braintree_Customer::find($customer_id);
            $payment_method_token = $customer->paypalAccounts[0]->token;

            $result4 = Braintree_Transaction::sale(array(
                'amount' => $this->szPaymentAmount,
                'paymentMethodToken' =>  $payment_method_token
            ));

            $paymentDetail = "Mobile Key:-" . $szMobileKey . "\nPaymentAmonut:-" . $szPaymentAmount . "\nPayment Nonce:-" . $szPaymentNonce;

            if($result4->success !=1 ) {
                return $this->respondWithErrors($result4->errors->deepAll());
            }

            return $this->respond(['PaymentDetail' => $paymentDetail], 'Payment Success');

        } catch (\Braintree_Exception $be) {
            return $this->respondWithErrors($be->getMessage(), $be->getCode());
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Upgrade pass confirm
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function upgrade_pass() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'idPass'       => 'required',
        ]);

        $szMobileKey = $this->request['szMobileKey'];
        $idSubscription = $this->request['idPass'];

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        try {

            $user = $this->orderService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            if ($user->szPaymentToken == '') {
                throw new \Braintree_Exception('User does not have payment token');
            }

            # user already has payment token

            $subscription = $this->orderService->subscriptionRepository->findWhere([
                'id' => $idSubscription,
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('No subscription found');
            }

            $payment_method_token = $user->szPaymentToken;

            $last_order = $this->orderService->orderRepository->findWhere([
                'idUser' => $user->id
            ])->sortByDesc('id')->first();

            if (empty($last_order)) {
                throw new \Exception('No last order found');
            }

            $paymentType = $last_order->szPaymentType;
            if ($paymentType == '') {
                $paymentType = 'Credit/Debit';
            }

            $szPaymentAmount = $subscription->fPrice;

            $order = $this->orderService->placeOrder($user->id, $subscription->idMerchant, $subscription->id, $payment_method_token, $paymentType, $szPaymentAmount);

            if ($order) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $order = new Item($order, new OrderTransformer());
                $order = $fractalManager->createData($order)->toArray();

                return $this->respond($order);
            }

            return $this->respondWithErrors();
        } catch (\Braintree_Exception $be) {

            $clientToken = Braintree_ClientToken::generate(array(
                "customerId" => ""
            ));

            if ($clientToken == '') {
                return $this->respondWithErrors('Did not get client token');
            }

            return $this->respond([
                'status' => 'CLIENT TOKEN',
                'client_token' => $clientToken,
            ]);
        } catch (\Exception $e) {
            throw $e;
//            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }
}
