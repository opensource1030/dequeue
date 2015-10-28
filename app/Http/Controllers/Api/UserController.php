<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

use Illuminate\Support\Facades\Validator;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

use App\Database\Serializers\CustomSerializer;
use App\Database\Transformers\UserTransformer;
use App\Database\Transformers\MerchantTransformer;
use App\Database\Services\UserService;
use App\Database\Services\OrderService;

use Mailchimp;
use StringHelper;
use Log;

class UserController extends ApiController
{
    protected $userService;
    protected $orderService;
    protected $mailchimp;

    public function __construct (
        ResponseFactory 		$response,
        Request 				$request,
        UserService             $userService,
        OrderService            $orderService,
        Mailchimp               $mailchimp)
    {
        parent::__construct($response, $request);

        $this->userService = $userService;
        $this->orderService = $orderService;

        $this->mailchimp = $mailchimp;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fractalManager = new Manager();
        $fractalManager->setSerializer(new CustomSerializer());

        $users = $this->userService->userRepository->all();
        $users = new Collection($users, new UserTransformer());
        $users = $fractalManager->createData($users)->toArray();

        return $this->respond($users);
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
            'szEmail'       => 'required',
//            'szEmail'       => 'required|email|unique:tbluser',
//            'szPassword'    => 'required',
//            'szConPassword' => 'required|same:szPassword'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {

//            $data = $this->request->except(['szConPassword', 'iFBFlag']);
//            $data['iFacekbookMapped'] = $iFBFlag;

//            $user = new \App\Database\Models\User($data);
//            $user = $this->userService->userRepository->create($data);

            $now = Carbon::now();

            $iFBFlag = 0;
            if (isset($this->request['iFBFlag']) && $this->request['iFBFlag'] == 1) {
                $iFBFlag = 1;
            }

            if ($iFBFlag == 1) {

                $szPassword = \StringHelper::randomString(8);
            } else {

                if (!isset($this->request['szPassword']) && $this->request['szPassword'] == '') {
                    throw new \Exception('Password is required');
                }
                $szPassword = $this->request['szPassword'];

                if (!isset($this->request['szConPassword']) && $this->request['szConPassword'] == '') {
                    throw new \Exception('Confirm password is required');
                }
                $szConPassword = $this->request['szConPassword'];

                if ($szPassword != $szConPassword) {
                    throw new \Exception('Password does not match');
                }
            }

            $szEmail = $this->request['szEmail'];

            $exists_email = $this->userService->userRepository->existsEmail($szEmail, 0);
            if ($exists_email) {
                throw new \Exception('Email address already exists');
            }

            $szInviteCode = '';
            if (isset($this->request['szInviteCode']) && $this->request['szInviteCode'] != '') {
                $szInviteCode = $this->request['szInviteCode'];
            }

            $newInviteCode = false;
            if ($szInviteCode != '') {
                $refer_user = $this->userService->userRepository->findWhere([
                    'szInviteCode' => $szInviteCode,
                    'isDeleted' => 0,
                ])->first();

                if (empty($refer_user)) {

                    $invite_code = $this->userService->inviteRepository->findWhere([
                        'szName' => $szInviteCode,
                        'iActive' => 1,
                        'isDeleted' => 0,
                    ])->first();

                    if (empty($invite_code)) {
                        throw new \Exception('Wrong Invite Code');
                    }

                    $newInviteCode = true;
                    $now = Carbon::now();

                    if ($invite_code->dtStart != '0000-00-00' && $now < $invite_code->dtStart) {
                        throw new \Exception('Invite code is not available at the time');
                    }

                    if ($invite_code->dtEnd != '0000-00-00' && $now > $invite_code->dtEnd) {
                        throw new \Exception('Invite code is expired');
                    }

                    if ($invite_code->iUserLimit > 0) {

                        $total = $this->userService->userRepository->findWhere([
                            'szInviteCode' => $szInviteCode,
                            'isDeleted' => 0,
                        ])->count();

                        if ($total >= $invite_code->iUserLimit) {
                            throw new \Exception('Limit is overflowed');
                        }
                    }
                }
            }


            $szMobileKey = \StringHelper::uniqueKey();
            $szFirstName = '';
            $szLastName = '';

            if (isset($this->request['szFirstName'])) {
                $szFirstName = $this->request['szFirstName'];
            }

            if (isset($this->request['szLastName'])) {
                $szLastName = $this->request['szLastName'];
            }

            $user = $this->userService->userRepository->create([
                'szFirstName'   => $szFirstName,
                'szLastName'    => $szLastName,
                'szEmail'       => $szEmail,
                'szPassword'    => $szPassword,
                'dtCreated'     => $now,
                'dtUpdated'     => $now,
                'iFacekbookMapped'  => $iFBFlag,
                'szMobileKey'   => $szMobileKey,
            ]);

            if (empty($user)) {
                return $this->respondWithErrors('Fail in sign up');
            }

            if ($newInviteCode) {

                $uiMapping = $this->userService->uiMappingRepository->create([
                    'idReferUser'   => 0,
                    'idSignupUser'  => $user->id,
                    'szInviteCode'  => $szInviteCode,
                    'fReferralcredit'   => $invite_code->fCreditAmount,
                    'dtSignup'      => $now,
                ]);

                # insert_signup_credit_history
                if ($uiMapping) {
                    $credit_history = \DB::table('')->insert([
                        'idUser'    => $user->id,
                        'fPrice'    => $uiMapping->fReferralcredit,
                        'idinvitecodemapped'    => $uiMapping->id,
                        'szcredittype'  => 'signup',
                        'sztransactiontype' => 'credit',
                        'datetime'  => $now,
                    ]);

                    if ($credit_history) {
                        $this->userService->userRepository->update([
                            'fTotalCredit' => $user->fTotalCredit + $uiMapping->fReferralcredit
                        ], $user->id);
                    }
                }
            } else {
#TODO very straing in original code, this part wouldn't be executed
                if ($szInviteCode != '' && $refer_user) {

                    $uiMapping = $this->userService->uiMappingRepository->create([
                        'idReferUser'   => $refer_user->id,
                        'idSignupUser'  => $user->id,
                        'szInviteCode'  => $szInviteCode,
                        'fReferralcredit'   => $invite_code->fCreditAmount,
                        'dtSignup'      => $now,
                    ]);

                    # insert_signup_credit_history
                    if ($uiMapping) {
                        $credit_history = \DB::table('')->insert([
                            'idUser'    => $user->id,
                            'fPrice'    => $uiMapping->fReferralcredit,
                            'idinvitecodemapped'    => $uiMapping->id,
                            'szcredittype'  => 'signup',
                            'sztransactiontype' => 'credit',
                            'datetime'  => $now,
                        ]);

                        if ($credit_history) {
                            $this->userService->userRepository->update([
                                'fTotalCredit' => $user->fTotalCredit + $uiMapping->fReferralcredit
                            ], $user->id);
                        }
                    }
                }
            }

            $wildCardEmail = substr($szEmail, strpos($szEmail, "@") + 1);
            $gifts  = $this->orderService->giftRepository->findWhereIn('szEmail', [$szEmail, $wildCardEmail]);

            foreach ($gifts as $gift) {
                $this->orderService->giftPassOrder($user->id, $gift->idPass, $gift->iType);

                if ($gift->iType == 1) {
                    \DB::table('tblusergiftmapping')->where('szEmail', $szEmail)->delete();
                }
            }

            # mailchimp

            $this->mailchimp->lists->subscribe(\Config::get('constants.__MAIL_CHIMP_LIST_KEY__'), ['email' => $szEmail]);

            # email

            $template = $this->userService->emailTemplateRepository->findWhere(['keyname' => '__REGISTRATION_EMAIL__'])->first();

            $subject = $template->subject;
            $message = $template->description;

            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');
            $to = $szEmail;

            \EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);

            if ($user) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $user = new Item($user, new UserTransformer());
                $user = $fractalManager->createData($user)->toArray();
                return $this->respond($user);
            }
        } catch (\Exception $e) {
//            throw $e;
            return $this->respondWithErrors($e->getMessage(), $e->getLine());
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = $this->userService->userRepository->find($id);

        $fractalManager = new Manager();
        $fractalManager->setSerializer(new CustomSerializer());

        $user = new Item($user, new UserTransformer());
        $user = $fractalManager->createData($user)->toArray();

        return $this->respond($user);
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
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szFirstName'   => 'required',
            'szLastName'    => 'required',
            'szEmail'       => 'email',
//            'szZipCode'     => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->only([
            'szFirstName',
            'szLastName',
            'szEmail',
            'szZipCode'
        ]);

        try {
            $model = $this->userService->userRepository->find($id);

            if (empty($model)) {
                return $this->respondWithErrors('Not found');
            }

            if ($model->szMobileKey != $this->request['szMobileKey']) {
                return $this->respondWithErrors('Unauthorized User');
            }

            # check email exists
            $exists_email = false;

            if ($data['szEmail'] && $data['szEmail'] != '') {
                $exists_email = $this->userService->userRepository->existsEmail($data['szEmail'], $id);
            } else {
                $data = array_except($data, 'szEmail');
            }

            if ($exists_email) {
                return $this->respondWithErrors('Email already taken', 10005);
            }

            $model = $this->userService->userRepository->update($data, $id);

            if ($model) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Item($model, new UserTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respondWithErrors();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

### user defined actions

    /**
     * sign up
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function sign_up(Request $request) {
        return $this->store($request);
    }

    /**
     * Get login code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_login_code() {

        $validator = Validator::make($this->request->all(), [
            'szEmail'       => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szEmail = $this->request['szEmail'];

        try {

            $szLoginCode = \StringHelper::randomDigits();
            $user = $this->userService->get_by_email($szEmail);

            if (empty($user)) {
                throw new \Exception('Invalid email');
            }

            $this->userService->userRepository->update([
                'szLoginCode' => $szLoginCode,
            ], $user->id);

            # email

            $subject = "Login Code";
            $message = "Hello {$user->szFirstName}<br>Here is your login code : {$szLoginCode}";

            $to = $user->szEmail;
            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');

            \EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);

            return $this->respond([
                'szLoginCode' => $szLoginCode
            ]);
        } catch (\Exception $e) {
            throw $e;
//            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * sign in with login code
     *
     * @return \Illuminate\Http\Response
     */
    public function sign_in_with_code() {
        ### validate

        $validator = Validator::make($this->request->all(), [
            'szEmail'       => 'required|email',
            'szLoginCode'   => 'required|digits:6',
//            'szUserType'    => 'required'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szEmail = $this->request['szEmail'];
        $szLoginCode = $this->request['szLoginCode'];

        try {
            $user = $this->userService->get_by_email($szEmail);

            if (empty($user)) {
                throw new \Exception('Invalid email');
            }

            if ($user->szLoginCode != $szLoginCode) {
                throw new \Exception('Invalid login code');
            }

            $this->userService->userRepository->update([
                'szLoginCode' => '',
            ], $user->id);

            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $user = new Item($user, new UserTransformer());
            $user = $fractalManager->createData($user)->toArray();

            return $this->respond($user);
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * sign in
     *
     * @return \Illuminate\Http\Response
     */
    public function sign_in() {
        ### validate

        $validator = Validator::make($this->request->all(), [
            'szEmail'       => 'required|email',
            'szPassword'    => 'required',
            'szUserType'    => 'required'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        ### search

        $szUserType = $this->request->input('szUserType');
        $where = $this->request->except('szUserType');

        if ($szUserType == 'merchant') {
            $merchant = $this->userService->merchantRepository->signIn($where);

            if (isset($merchant['id']) && $merchant['id'] > 0) {

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $merchant = new Item($merchant, new MerchantTransformer());
                $merchant = $fractalManager->createData($merchant)->toArray();

                return $this->respond($merchant);
            } else {
                return $this->respondWithErrors('Invalid email and password');
            }
        } else {
            $user = $this->userService->userRepository->signIn($where);

            if (isset($user['id']) && $user['id'] > 0) {

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $user = new Item($user, new UserTransformer());
                $user = $fractalManager->createData($user)->toArray();

                return $this->respond($user);
            } else {
                return $this->respondWithErrors('Invalid email and password');
            }
        }
    }

    /**
     * change password
     *
     * @return \Illuminate\Http\Response
     */
    public function change_password()
    {
        ### validate

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
//            'szEmail'       => 'required|email',
            'szPassword'    => 'required',
            'szConPassword' => 'required|same:szPassword',
            'szUserType'    => 'required'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        ### getting a user

        $szUserType = $this->request->input('szUserType');
        $attributes = $this->request->except('szUserType');

        if ($szUserType == 'merchant') {
            $merchant = $this->userService->merchantRepository->changePassword($attributes);

            if (isset($merchant['id']) && $merchant['id'] > 0) {

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $merchant = new Item($merchant, new MerchantTransformer());
                $merchant = $fractalManager->createData($merchant)->toArray();

                return $this->respond($merchant);
            } else {

                return $this->respondWithErrors('No merchant found');
            }
        } else {
            $user = $this->userService->userRepository->changePassword($attributes);

            if (isset($user['id']) && $user['id'] > 0) {

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $user = new Item($user, new UserTransformer());
                $user = $fractalManager->createData($user)->toArray();

                return $this->respond($user);
            } else {

                return $this->respondWithErrors('No user found');
            }
        }
    }

    /**
     * sign in with facebook
     *
     * @return \Illuminate\Http\Response
     */
    public function sign_in_by_facebook() {

        $validator = Validator::make($this->request->all(), [
            'szEmail'       => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();
        $user = $this->userService->userRepository->signInByFacebook($data);

        if (isset($user['id']) && $user['id'] > 0) {
            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $user = new Item($user, new UserTransformer());
            $user = $fractalManager->createData($user)->toArray();

            return $this->respond($user);
        } else {
            return $this->respondWithErrors('Invalid email');
        }
    }

    /**
     * Forgot password
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function forgot_password() {

        $validator = Validator::make($this->request->all(), [
            'szEmail'       => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szEmail = $this->request['szEmail'];
        $forgot_key = \StringHelper::randomCode(15);

        try {
            $user = $this->userService->get_by_email($szEmail);

            if (empty($user)) {
                throw new \Exception('Invalid email');
            }

            $this->userService->userRepository->update([
                'szForgotPasswordKey' => $forgot_key,
                'keyDateTime' => Carbon::now()
            ], $user->id);

            # email

            $url = \Config::get('constants.__MAIN_SITE_URL__') . 'resetpassword/' . $forgot_key . '/';
            $szName = "($user->szFirstName} {$user->szLastName}";

            $template = $this->userService->emailTemplateRepository->findWhere(['keyname' => '__FORGOT_EMAIL__'])->first();

            $subject = $template->subject;
            $message = $template->description;

            $message = str_replace('szLink', '<a href=\''.$url.'\'>Click here</a>', $message);
            $message = str_replace('szTextLink', $url, $message);
            $message = str_replace('szName', $szName, $message);

            $to = $user->szEmail;
            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');

            \EmailHelper::sendEmail($from, $to, $subject, $message, $user->id);

            return $this->respond([
                'url' => $url,
            ], 'Your password reset link has been sent.');
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get invite code
     *
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function get_invite_code() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        try {
            $user = $this->userService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0
            ])->first();

            if (empty($user)) {
                throw new \Exception('Invalid mobile key');
            }

            $szInviteCode = $user->szInviteCode;

            if ($szInviteCode != '') {
                return $this->respond([
                    "screen_message"    => $szInviteCode ,
                    "text_message"      => $szInviteCode,
                    "email_message"     => $szInviteCode,
                    "twitter_message"   => $szInviteCode,
                    "facebook_message"  => $szInviteCode
                ]);
            }

            $randomCode = '';
            do {
                $randomCode = \StringHelper::randomCode(6);

                $u = $this->userService->userRepository->findWhere([
                    'szInviteCode' => $randomCode,
                    'isDeleted' => 0,
                ])->first();
            } while ($u);

            $szInviteCode = $randomCode;

            $this->userService->userRepository->update([
                'szInviteCode' => $szInviteCode,
                'dtUpdated' => Carbon::now(),
            ], $user->id);

            return $this->respond([
                "screen_message"    => $szInviteCode ,
                "text_message"      => $szInviteCode,
                "email_message"     => $szInviteCode,
                "twitter_message"   => $szInviteCode,
                "facebook_message"  => $szInviteCode
            ]);
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }
}
