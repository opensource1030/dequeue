<?php

namespace App\Http\Controllers\Api;

use App\Database\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Facades\Validator;

use Carbon\Carbon;
use App\Database\Services\Service;
use Braintree_Subscription;
use Braintree_Transaction;
use Braintree_ClientToken;

use DB;
use Exception;
use StringHelper;
use EmailHelper;
use Config;
use Log;

class OtherController extends ApiController
{
    protected $service;
    protected $orderService;

    public function __construct(
        ResponseFactory $response,
        Request $request,
        Service $service,
        OrderService $orderService
    ) {

        parent::__construct($response, $request);
        $this->service = $service;
        $this->orderService = $orderService;
    }

    /**
     * Get app 3 images
     *
     * @return \Illuminate\Http\Response
     */
    public function app_images() {

        $result = array();

        $rows = DB::table('tblappimages')->get();

        foreach ($rows as $row) {
            $result[] = [
                'id'        => $row->id,
                'szName'    => $row->szName,
                'szFileName'    => $row->szFileName,
                'dtUploaded'    => $row->dtUploaded,
            ];
        }

        return $this->respond($result);
    }

    /**
     * New pass request to admin
     *
     * @return \Illuminate\Http\Response
     */
    public function new_pass_request() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szTitle'       => 'required',
            'szRating'      => 'required',
            'szMessage'     => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        $user = $this->service->userRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

        if (empty($user)) {
            return $this->respondWithErrors('Invalid user');
        }

        $id = DB::table('tblpassrequesttoadmin')->insertGetId([
            'szMobileKey'   => $data['szMobileKey'],
            'szTitle'       => $data['szTitle'],
            'szRating'      => $data['szRating'],
            'szMessage'     => $data['szMessage'],
            'dtRequested'   => Carbon::now(),
        ]);

        $row = DB::table('tblpassrequesttoadmin')->where('id', $id)->first();

        $result = [
            'id'            => $row->id,
            'szMobileKey'   => $row->szMobileKey,
            'szTitle'       => $row->szTitle,
            'szRating'      => $row->szRating,
            'szMessage'     => $row->szMessage,
            'dtRequested'   => $row->dtRequested,
        ];

        # send an email

        $template = $this->service->emailTemplateRepository->findWhere(['keyname' => '__NEW_PASSES_REQUEST_TO_ADMIN__'])->first();

        $subject = $template['subject'];
        $message = $template['description'];

        $szFlag = '';
        $subject = str_replace('szFlag', $szFlag, $subject);
        $message = str_replace('szTitle', $data['szTitle'], $message);
        $message = str_replace('szRating', $data['szRating'], $message);
        $message = str_replace('szMessage', $data['szMessage'], $message);
        $message = str_replace('szName', "{$user->szFirstName} {$user->szLastName}", $message);
        $message = str_replace('szEmail', $user->szEmail, $message);
        $message = str_replace('szFlag', $szFlag, $message);
        $to = Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
        $from = $user->szEmail;

        EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);

        return $this->respond($result);
    }

    /**
     * New feedback to admin
     *
     * @return \Illuminate\Http\Response
     */
    public function new_feedback() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szFlag'        => 'required|in:user,merchant',
            'szTitle'       => 'required',
            'szRating'      => 'required',
            'szMessage'     => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        if ($data['szFlag'] == 'user') {
            $sender = $this->service->userRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
        } else {
            $sender = $this->service->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
        }

        if (empty($sender)) {
            return $this->respondWithErrors('Invalid mobile key');
        }

        $id = DB::table('tblfeedbacktoadmin')->insertGetId([
            'szMobileKey'   => $data['szMobileKey'],
            'szFlag'        => $data['szFlag'],
            'szTitle'       => $data['szTitle'],
            'szRating'      => $data['szRating'],
            'szMessage'     => $data['szMessage'],
            'dtSend'        => Carbon::now(),
        ]);

        $row = DB::table('tblfeedbacktoadmin')->where('id', $id)->first();

        $result = [
            'id'            => $row->id,
            'szMobileKey'   => $row->szMobileKey,
            'szFlag'        => $row->szFlag,
            'szTitle'       => $row->szTitle,
            'szRating'      => $row->szRating,
            'szMessage'     => $row->szMessage,
            'dtSend'        => $row->dtSend,
        ];

        # send an email

        $template = $this->service->emailTemplateRepository->findWhere(['keyname' => '__FEEDBACK_TO_ADMIN__'])->first();

//        $subject = $template['subject'];
        $message = $template['description'];

        $subject = "{$row->szRating} from {$sender->szEmail}";

        $message = str_replace('szTitle', $row->szTitle, $message);
//        $message = str_replace('szRating', $data['szRating'], $message);
        $message = str_replace('szMessage', $row->szMessage, $message);
        $message = str_replace('szName', "{$sender->szFirstName} {$sender->szLastName}", $message);
        $message = str_replace('szEmail', $sender->szEmail, $message);
        $message = str_replace('szFlag', $row->szFlag, $message);
        $to = Config::get('constants.__FEEDBACK_EMAIL_ADDRESS__');
        $from = $sender->szEmail;

        EmailHelper::sendEmail($from, $to, $subject, $message, $sender->id);

        return $this->respond($result);
    }

    /**
     * Apply promo code
     *
     * @return \Illuminate\Http\Response
     */
    public function apply_promo_code() {

        $validator = Validator::make($this->request->all(), [
            'szPromoCode'   => 'required',
            'idPass'        => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $data = $this->request->all();
        $szPromoCode = $this->request['szPromoCode'];
        $idPass = $this->request['idPass'];
        $now = Carbon::now();
        $flag = false;

        try {
            $subscription = $this->service->subscriptionRepository->find($idPass);
            if (empty($subscription)) {
                throw new \Exception('Invalid idPass');
            }

            $promo = collect(DB::table('tblpromocode')->where('szName', $szPromoCode)->get())->first();

            if (empty($promo)) {
                throw new \Exception('Invalid szPromoCode');
            }

            if ($promo->idMerchant != $subscription->idMerchant) {
                throw new \Exception('Promotion code is invalid. Merchant is different');
            }

            $mapping = DB::table('tblpromocodepassmapping')->where('idPromoCode', $promo->id)->get();

            if (empty($mapping) || count($mapping) == 0) {
                throw new \Exception('Not mapping found');
            }

            $idSubscriptionArr = array_pluck($mapping, 'idSubscription');

//            if ((count($idSubscriptionArr) == 1 && $idSubscriptionArr[0] == 0) || in_array($data['idPass'], $idSubscriptionArr)) {
//
//            }

            if ($promo->szType == 'Fixed') {
                $discountAmount = $promo->fAmount;
            } else if ($promo->szType = 'Percentage') {
                $discountAmount = .01 * $promo->fAmount;
            }

            if ($flag) {
                DB::table('tblorder')->where('id', $data['idOrder'])->update([
                    'idPromoCode'       => $promo->id,
                    'fDiscountAmount'   => $discountAmount,
                ]);

                DB::table('tblpromocodetracking')->insert([
                    'idPromoCode'   => $promo->id,
                    'idUser'        => $data['idUser'],
                    'idSubscription'=> $data['idPass'],
                    'idOrder'       => $data['idOrder'],
                    'dtUsed'        => $now,
                ]);
            }

            return $this->respond([
                'idPromoCode'       => $promo->id,
                'szType'            => $promo->szType,
                'discountAmount'    => $discountAmount,
            ]);

        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function privacy_policy() {
        return $this->respond([
            'privacy_policy' => \Config::get('constants.__MAIN_SITE_URL__') . 'pdf/C.it_-_Privacy_Policy921906_3_BN.pdf'
        ]);
    }

    public function terms_conditions() {
        return $this->respond([
            'terms_conditions' => \Config::get('constants.__MAIN_SITE_URL__') . 'pdf/Pass_Terms_of_Service_.pdf'
        ]);
    }

    public function faq_text() {
        return $this->respond([
            'faq_text' => '<h1 class="heading-link">FAQ:</h1><p><strong>Coming Soon</strong></p>'
        ]);
    }

    /**
     * Send invite code to emails
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function send_invite_code() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szEmail'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $szEmail = $this->request['szEmail'];

        $explodeArr = array(',', ';');
        $emailStr = str_replace($explodeArr, ";", $szEmail);
        $emailArr = explode(";", $emailStr);

        try {
            $user = $this->service->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $szName = $user->szFirstName . ' ' . $user->szLastName;

            $template = $this->service->emailTemplateRepository->findWhere(['keyname' => '__PASS_CREDIT_EMAIL__'])->first();

            $subject = $template->subject;
            $message = $template->description;

            $message = str_replace('szUserName', $szName, $message);
            $message = str_replace('szReferralCode', $user->szInviteCode, $message);

            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
            foreach ($emailArr as $to) {
                \EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);
            }

            return $this->respond([], 'Email has been successfully sent');
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Track level up click
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function track_level_up() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'idOrder'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $idOrder = $this->request['idOrder'];

        try {
            $user = $this->service->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $order = $this->service->orderRepository->findWhere([
                'id' => $idOrder,
                'iCompleted' => 1,
            ])->first();

            if (empty($order)) {
                throw new \Exception('No order found');
            }

            if ($order->iduser != $user->id) {
                throw new \Exception('Unauthorized user');
            }

            \DB::table('tbltracklevelup')->insert([
                'idPass' => $order->idSubscription,
                'idUser' => $user->id,
                'dbClick' => Carbon::non()
            ]);

            return $this->respond();

        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Track redemption location
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function track_redemption_location() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'idOrder'       => 'required',
            'idPass'        => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $idOrder = $this->request['idOrder'];
        $idSubscription = $this->request['idPass'];

        try {
            $user = $this->service->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $order = $this->service->orderRepository->findWhere([
                'id' => $idOrder,
                'iCompleted' => 1,
            ])->first();

            if (empty($order)) {
                throw new \Exception('No order found');
            }

            $subscription = $this->service->subscriptionRepository->findWhere([
                'id' => $idSubscription,
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \Exception('No subscription found');
            }

            if (!isset($this->request['szLocationCode']) || $this->request['szLocationCode'] == '') {
                $location = $this->service->locationRepository->findWhere([
                    'idMerchant' => $subscription->idMerchant,
                    'szLocationCode' => '',
                ])->first();

                if (empty($location)) {
                    throw new \Exception('Location code is required');
                }

                return $this->respond();
            }

            $location = $this->service->locationRepository->findWhere([
                'idMerchant' => $subscription->idMerchant,
                'szLocationCode' => $this->request['szLocationCode'],
            ])->first();

            if (empty($location)) {
                throw new \Exception('Invalid location code');
            }

            $now = Carbon::now();

            if ($order->dtExpiry <= $now) {
                throw new \Exception('Your pass has expired');
            }

            if ($order->dtAvailable > $now){
                throw new \Exception('This Pass is not available at this time');
            }

            if ($order->szPassType == 'package pass' ||
                $order->szPassType == 'one time pass' ||
                $order->iLimitions == 'Activation Number' ||
                $order->szPassType =='gift pass') {
                if ($order->iTotalUsedCount == $order->iLimitionCount){
                    throw new \Exception('message"=>"Your pass has been used');
                }
            }

            $attributes = [
                'idUser'    => $user->id,
                'idOrder'   => $order->id,
                'idPass'    => $subscription->id,
                'idMerchant'    => $subscription->idMerchant,
                'idMerchantLocation'    => $location->id,
                'szAddress1'    => $location->szAddress1,
                'szAddress2'    => $location->szAddress2,
                'szCity'    => $location->szCity,
                'szState'   => $location->szState,
                'szZipCode' => $location->szZipCode,
                'szLatitude'    => $location->szLatitude,
                'szLongitude'   => $location->szLongitude,
                'dtRedeem'  => $now,
            ];

            \DB::table('tbltrackredemptionlocation')->insert($attributes);

            return $this->respond($attributes);
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }
}