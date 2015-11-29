<?php

namespace App\Http\Controllers\Api;

use App\Database\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

use Illuminate\Support\Facades\Validator;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

use App\Database\Serializers\CustomSerializer;
use App\Database\Repositories\MerchantRepository;
use App\Database\Transformers\MerchantTransformer;
use App\Database\Repositories\AdminRepository;
use App\Database\Services\MerchantService;

use Log;
use Input;


/**
 * @Resource ("/merchants", except={"show"})
 */

class MerchantController extends ApiController {

    protected $merchantService;
    protected $orderService;

	public function __construct(
		ResponseFactory 		$response, 
		Request 				$request,
        MerchantService         $merchantService,
        OrderService            $orderService
    ) {
		parent::__construct($response, $request);

        $this->merchantService = $merchantService;
        $this->orderService = $orderService;
	}

	/**
	* Display a listing of the resource.
	*
	* @return Response
	*/
	public function index()
	{

        $isAdmin = (bool) $this->request['isAdmin'];

        $where = [
            'isDeleted'     => 0
        ];

        if (! $isAdmin) {
            $where = array_merge($where, [
                'iActive'   => 1,
                'iHidden'   => 0,
                'iDraft'    => 0,
            ]);
        }

        $model = $this->merchantService->merchantRepository
            ->findWhere($where)
            ->sortBy('iOrder');

        if ($model) {

            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $fractalManager->parseIncludes(['locations']);
//            $fractalManager->parseIncludes(['locations', 'categories']);

            $model = new Collection($model, new MerchantTransformer());
            $model = $fractalManager->createData($model)->toArray();

            return $this->respond($model);
        } else {
            return $this->respond();
        }

		### if not using presenter, an array is returned
//		$merchants = $this->merchantService->merchantRepository->skipPresenter()->all();
	}


	/**
	* Show the form for creating a new resource.
	*
	* @return Response
	*/
	public function create()
	{
		//
	}


	/**
	* Store a newly created resource in storage.
	*
	* @param  Request $request
	*
	* @return Response
	*/
    #TODO set szFileName, szUploadFileName - merchant / addEditMerchant()
	public function store( Request $request )
	{
		$validator = Validator::make($request->all(), [
            'szMobileKey'       => 'required',
            'szName'            => 'required',
            'szFirstName'       => 'required',
            'szLastName'        => 'required',
            'szCompanyName'     => 'required',
            'szEmail'           => 'required|email|unique:tblmerchant',
            'szPassword'        => 'required',
            'szConPassword'     => 'required|same:szPassword',
            'szPhoneNumber'     => 'required',
            'szZipCode'         => 'digits:5',
            'szDescription'     => 'required',
            'szShortDescription'    => 'required',
            'onDemandToDate'    => 'after:onDemandFromDate'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $isAdmin = (bool) $this->request['isAdmin'];
        $isAdmin = true;

        $szMobileKey = $this->request->input('szMobileKey');
        $data = $this->request->except(['szMobileKey', 'szConPassword', 'szMerchantImage', 'merchantId', 'idMerchant']);
        try {
            $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

            if ($admin && $admin->id > 0) {
                $model = $this->merchantService->merchantRepository->create($data);

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Item($model, new MerchantTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            } else {
                return $this->respondWithErrors('Do not have the admin right');
            }
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
	}

	/**
	* Display the specified resource.
	*
	* @param  int $id
	*
	* @return Response
	*/
	public function show( $id ) {

        try {
            $model = $this->merchantService->merchantRepository->find($id);

            if ($model && $model->isDeleted == 0) {

                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $fractalManager->parseIncludes(['locations']);

                $model = new Item($model, new MerchantTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            } else {
                return $this->respond();
            }
        } catch (\Exception $e) {
            $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
	}

	/**
	* Show the form for editing the specified resource.
	*
	* @param  int $id
	*
	* @return Response
	*/
	public function edit( $id )
	{
		//
	}


	/**
	* Update the specified resource in storage. - merchant / addEditMerchant()
	*
	* @param  Request $request
	* @param  int     $id
	*
	* @return Response
	*/
	public function update( Request $request, $id )
	{
        $validator = Validator::make($request->all(), [
            'szMobileKey'       => 'required',
            'szName'            => 'required',
            'szFirstName'       => 'required',
            'szLastName'        => 'required',
            'szCompanyName'     => 'required',
            'szEmail'           => 'email',
            'szPhoneNumber'     => 'required',
            'szZipCode'         => 'digits:5',
            'szDescription'     => 'required',
            'szShortDescription'    => 'required',
            'onDemandToDate'    => 'after:onDemandFromDate'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {
            $data = $this->request->except([
                'szMobileKey',
                'szPassword',
                'szConPassword',
                'szMerchantImage',
                'isAdmin',
                'merchantId',
                'idMerchant'
            ]);

            $isAdmin = (bool) $this->request['isAdmin'];
            $szMobileKey = $this->request['szMobileKey'];

            # check existence a merchant with $id
            $model = $this->merchantService->merchantRepository->find($id);

            if (empty($model)) {
                return $this->respondWithErrors('Not foun d', 10005);
            }

            # check $id and its szMobileKey when user is of merchant type
            if ($isAdmin) {
                $model = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

                if (empty($model) || $model->id == 0) {
                    return $this->respondWithErrors('Unauthorized Admin', 10005);
                }
            } else {
                $model = $this->merchantService->merchantRepository->find($id);

                if ($model->szMobileKey != $szMobileKey) {
                    return $this->respondWithErrors('Unauthorized Merchant', 10005);
                }
            }

            # check email exists
            $exists_email = false;

            if ($data['szEmail'] && $data['szEmail'] != '') {
                $exists_email = $this->merchantService->merchantRepository->existsEmail($data['szEmail'], $id);
            } else {
                $data = array_except($data, 'szEmail');
            }

            if ($exists_email) {
                return $this->respondWithErrors('Email already taken', 10005);
            }

            $model = $this->merchantService->merchantRepository->update($data, $id);

            if ($model) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Item($model, new MerchantTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            } else {
                return $this->respondWithErrors('Not found');
            }
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
	}


	/**
	* Remove the specified resource from storage.
	*
	* @param  int $id
	*
	* @return Response
	*/
	public function destroy( $id )
	{
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        try {
            $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

            if (empty($admin)) {
                throw new \Exception('Invalid mobile key');
            }

            $merchant = $this->merchantService->merchantRepository->find($id);

            if (empty($merchant)) {
                throw new \Exception('No merchant found');
            }

            $merchant = $this->merchantService->merchantRepository->update([
                'iActive' => 0,
                'isDeleted' => 1,
            ], $id);

            if ($merchant) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $merchant = new Item($merchant, new MerchantTransformer());
                $merchant = $fractalManager->createData($merchant)->toArray();

                return $this->respond($merchant);
            }

            return $this->respondWithErrors();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
	}


# user defined actions

    public function forgot_password() {

        $validator = Validator::make($this->request->all(), [
            'szEmail'       => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szEmail = $this->request['szEmail'];
        $random_password = \StringHelper::randomCode(10);

        try {
            $merchant = $this->merchantService->merchantRepository->findWhere([
                'szEmail' => $szEmail,
                'isDeleted' => 0,
            ])->first();

            if (empty($merchant)) {
                throw new \Exception('Invalid email');
            }

            $this->merchantService->merchantRepository->update([
                'szMobileKey' => '',
                'szPassword' => $random_password,
            ], $merchant->id);

            # email

            $szName = "($merchant->szFirstName} {$merchant->szLastName}";

            $template = $this->merchantService->emailTemplateRepository->findWhere(['keyname' => '__MERCHANT_FORGOT_PASSWORD___'])->first();

            $subject = $template->subject;
            $message = $template->description;

            $message=str_replace('szName', $szName, $message);
            $message=str_replace('szPassword', $random_password, $message);

            $to = $merchant->szEmail;
            $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');

            \EmailHelper::sendEmail($from, $to, $subject, $message, $merchant->id, 2);

            return $this->respond([], 'Your Password has been sent.');
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function update_status() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'idMerchant'    => 'required',
            'iActive'       => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $idMerchant = $this->request['idMerchant'];
        $iActive = (int) $this->request['iActive'];

        try {
            $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

            if (empty($admin)) {
                throw new \Exception('Invalid mobile key');
            }

            $merchant = $this->merchantService->merchantRepository->find($idMerchant);

            if (empty($merchant)) {
                throw new \Exception('No merchant found');
            }

            $merchant = $this->merchantService->merchantRepository->update([
                'iActive' => $iActive
            ], $idMerchant);

            if ($merchant) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $merchant = new Item($merchant, new MerchantTransformer());
                $merchant = $fractalManager->createData($merchant)->toArray();

                return $this->respond($merchant);
            }

            return $this->respondWithErrors();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function get_payout() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
//            'idMerchant'    => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            if (isset($data['isAdmin']) && $data['isAdmin'] == 1) {
                $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->merchantService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }
            } else {
                $merchant = $merchant = $this->merchantService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($merchant)) {
                    throw new \Exception('Invalid mobile key');
                }
            }

            $subscriptions = collect($this->orderService->purchasedSubscriptions($merchant->id));
            $total_amount = $this->orderService->totalPaidAmountInMonth($merchant->id);
            $total_subscriptions = $subscriptions->sum('total');
            $popular_pass = $subscriptions->sortBy('total')->first();

            $result = [
                'bank_name'         => $merchant->szBankName,
                'account_number'    => $merchant->szAccountNumber,
                'routing_number'    => $merchant->szRoutingNumber,
                'totalSubscriberPerPass'    => $subscriptions,
                'totalAmount'       => $total_amount,
                'totalPassSubscribed'       => $total_subscriptions,
                'szPopularPass'     => $popular_pass,
            ];

            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function update_payout() {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            if (isset($data['isAdmin']) && $data['isAdmin'] == 1) {
                $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->merchantService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }

                if (isset($data['dtPayout']) && $data['dtPayout'] > 0 && $data['dtPayout'] < 100) {
                    $merchant = $merchant = $this->merchantService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Payout Date Should not be more than 2 digits');
                }
            } else {
                $merchant = $this->merchantService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($merchant)) {
                    throw new \Exception('Invalid mobile key');
                }

                $data['isAdmin'] = 0;
            }

            $attributes = [
                'szBankName'        => $data['szBankName'],
                'szAccountNumber'   => $data['szAccountNumber'],
                'szRoutingNumber'   => $data['szRoutingNumber'],
            ];

            if ($data['isAdmin'] == 1) {
                $attributes['dtPayout'] = $data['dtPayout'];
            }

            $merchant = $this->merchantService->merchantRepository->update($attributes, $merchant->id);

            if ($merchant) {

                $template = $this->merchantService->emailTemplateRepository->findWhere(['keyname' => '__PAYOUT_DETAIL_UPDATE_NOTIFICATION__'])->first();

                $subject = $template->subject;
                $message = $template->description;

                $time = date("Y-m-d H:i:s", time());
                $szName = $merchant->szName;
                $message = str_replace('szName', $szName, $message);
                $message = str_replace('dtTime', $time, $message);

                $to = $merchant->szEmail;
                $from = \Config::get('constants.__SUPPORT_EMAIL_ADDRESS__');

                \EmailHelper::sendEmail($from, $to, $subject, $message, $merchant->id, 2);

                $subscriptions = collect($this->orderService->purchasedSubscriptions($merchant->id));
                $total_amount = $this->orderService->totalPaidAmountInMonth($merchant->id);
                $total_subscriptions = $subscriptions->sum('total');
                $popular_pass = $subscriptions->sortBy('total')->first();

                $result = [
                    'bank_name'         => $merchant->szBankName,
                    'account_number'    => $merchant->szAccountNumber,
                    'routing_number'    => $merchant->szRoutingNumber,
                    'totalSubscriberPerPass'    => $subscriptions,
                    'totalAmount'       => $total_amount,
                    'totalPassSubscribed'       => $total_subscriptions,
                    'szPopularPass'     => $popular_pass,
                ];

                return $this->respond($result);
            }

            return $this->respondWithErrors();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function update_notes() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szNote'        => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            if (isset($data['isAdmin']) && $data['isAdmin'] == 1) {
                $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->merchantService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }
            } else {
                $merchant = $merchant = $this->merchantService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($merchant)) {
                    throw new \Exception('Invalid mobile key');
                }
            }

            $merchant = $this->merchantService->merchantRepository->update([
                'szNote' => $data['szNote'],
            ], $merchant->id);

            if ($merchant) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $merchant = new Item($merchant, new MerchantTransformer());
                $model = $fractalManager->createData($merchant)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function get_by_mobile_key() {

        $validator = Validator::make($this->request->all(), [
//            'idMerchant'    => 'required',
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            if (isset($data['isAdmin']) && $data['isAdmin'] == 1) {
                $admin = $this->merchantService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $this->merchantService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }
            } else {
                $merchant = $this->merchantService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                $data['isAdmin'] = 0;
            }

            if (empty($merchant)) {
                throw new \Exception('No merchant found');
            }

            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $merchant = new Item($merchant, new MerchantTransformer());
            $merchant = $fractalManager->createData($merchant)->toArray();

            return $this->respond($merchant);
        } catch (\Exception $e) {
//            throw $e;
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }
}
