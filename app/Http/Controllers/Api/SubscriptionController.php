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
use App\Database\Services\SubscriptionService;
use App\Database\Transformers\SubscriptionTransformer;

use Mockery\CountValidator\Exception;
use StringHelper;
use Log;

class SubscriptionController extends ApiController
{
    protected  $subscriptionService;

    public function __construct (
        ResponseFactory 		$response,
        Request 				$request,
        SubscriptionService	    $subscriptionService
    ) {
        parent::__construct($response, $request);

        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    #TODO create and use PassTransformer instead of SubscriptionTransformer
    public function index()
    {
        try {
            $model = $this->subscriptionService->search_by_text([], false);

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new SubscriptionTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
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
    public function store(Request $request)
    {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szFlag'        => 'required|in:admin,merchant',
            'szTilte'       => 'required',
            'idCategory'    => 'required',
            'idMerchant'    => 'required',
            'iPromotional'  => 'required',
            'iUserLimit'    => 'required',
            'szShortDescription'    => 'required',
            'szDescription' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {
            $szMobileKey = $this->request['szMobileKey'];
            $szFlag = $this->request['szFlag'];
            $iPromotional = $this->request['iPromotional'];

            $data = $this->request->only([
                'idMerchant',
                'idCategory',
                'szTilte',
                'iPromotional',
                'iUserLimit',
                'szShortDescription',
                'szDescription',

                'iLimitions',
                'iLimitionCount',
                'iCountFinalExpiry',
                'iActivationCount',
                'fPrice',
                'iPeriod'
            ]);

            $data['dtCreated'] = Carbon::now();
            $data['iPeriod'] = '';

            if ($szFlag == 'merchant') {
                $creator = $this->subscriptionService->merchantRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

                if (empty($creator) || $creator->id != $data['idMerchant']) {
                    throw new \ErrorException('Unauthorized merchant');
                }
            } else {
                $creator = $this->subscriptionService->adminRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

                if (empty($creator)) {
                    throw new \ErrorException('Unauthorized admin');
                }

                $data = array_merge($data, $this->request->only(['szOnDemandPopup', 'dtOnDemandFromdate', 'dtOnDemandTodate']));
            }

            if ($iPromotional == 1) {
                $data['szPassType'] = 'promotional pass';
                $data['szCouponCode'] = $this->request['szCouponCode'];

                if ($data['szCouponCode'] != '') {
                    $subscription = $this->subscriptionService->subscriptionRepository->findWhere(['szCouponCode' => $data['szCouponCode']])->first();

                    if ($subscription) {
                        throw new \ErrorException('Coupon Code already exists');
                    }
                } else {
                    throw new \ErrorException('Coupon Code is required');
                }
            } else {
                if ($data['szPassType'] == 'subscription pass') {
                    if ($data['szPeriodMonthly'] == 0 && $data['szPeriodYearly'] == 0)
                    {
                        throw new \ErrorException('Subscription period is required');
                    }

                    if ($data['szPeriodMonthly'] == 1) {
                        $data['iPeriod'] = 'Monthly';
                    }
                }
            }

            $data['szCleanTitle'] = $this->subscriptionService->newCleanTitle($data['szTilte'], 0, $data['idMerchant']);

            $subscription = $this->subscriptionService->subscriptionRepository->create($data);

            if ($subscription) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $subscription = new Item($subscription, new SubscriptionTransformer());
                $subscription = $fractalManager->createData($subscription)->toArray();

                return $this->respond($subscription);
            }

            return $this->respond();
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
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
        //
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
            'szFlag'        => 'required|in:admin,merchant',
            'szTilte'       => 'required',
            'idCategory'    => 'required',
            'idMerchant'    => 'required',
            'iPromotional'  => 'required',
            'iUserLimit'    => 'required',
            'szShortDescription'    => 'required',
            'szDescription' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {
            $szMobileKey = $this->request['szMobileKey'];
            $szFlag = $this->request['szFlag'];
            $iPromotional = $this->request['iPromotional'];

            $data = $this->request->only([
                'idMerchant',
                'idCategory',
                'szTilte',
                'iPromotional',
                'iUserLimit',
                'szShortDescription',
                'szDescription',

                'iLimitions',
                'iLimitionCount',
                'iCountFinalExpiry',
                'iActivationCount',
                'fPrice',
                'iPeriod'
            ]);

            $data['dtCreated'] = Carbon::now();
            $data['iPeriod'] = '';

            if ($szFlag == 'merchant') {
                $creator = $this->subscriptionService->merchantRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

                if (empty($creator) || $creator->id != $data['idMerchant']) {
                    throw new \ErrorException('Unauthorized merchant');
                }
            } else {
                $creator = $this->subscriptionService->adminRepository->findWhere(['szMobileKey' => $szMobileKey])->first();

                if (empty($creator)) {
                    throw new \ErrorException('Unauthorized admin');
                }

                $data = array_merge($data, $this->request->only(['szOnDemandPopup', 'dtOnDemandFromdate', 'dtOnDemandTodate']));
            }

            if ($iPromotional == 1) {
                $data['szPassType'] = 'promotional pass';
                $data['szCouponCode'] = $this->request['szCouponCode'];

                if ($data['szCouponCode'] != '') {
                    $subscription = $this->subscriptionService->subscriptionRepository->findWhere([
                        'szCouponCode' => $data['szCouponCode'],
                        'id' => ['id', '!=', $id]
                    ])->first();

                    if ($subscription) {
                        throw new \ErrorException('Coupon Code already exists');
                    }
                } else {
                    throw new \ErrorException('Coupon Code is required');
                }
            } else {
                if ($data['szPassType'] == 'subscription pass') {
                    if ($data['szPeriodMonthly'] == 0 && $data['szPeriodYearly'] == 0)
                    {
                        throw new \ErrorException('Subscription period is required');
                    }

                    if ($data['szPeriodMonthly'] == 1) {
                        $data['iPeriod'] = 'Monthly';
                    }
                }
            }

            $data['szCleanTitle'] = $this->subscriptionService->newCleanTitle($data['szTilte'], $id, $data['idMerchant']);

            $subscription = $this->subscriptionService->subscriptionRepository->update($data, $id);

            if ($subscription) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $subscription = new Item($subscription, new SubscriptionTransformer());
                $subscription = $fractalManager->createData($subscription)->toArray();

                return $this->respond($subscription);
            }

            return $this->respond();
        } catch (\ErrorException $e) {
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
        $validator = Validator::make($this->request->all(), [
            'szFlag'        => 'required|in:admin,merchant',
            'szMobileKey'   => 'required'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {
            $subscription = $this->subscriptionService->subscriptionRepository->find($id);

            if ($data['szFlag'] == 'admin') {
                $admin = $this->subscriptionService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
                if (empty($admin)) {
                    throw new \ErrorException('Invalid mobile key');
                }
            } else {
                $merchant = $this->subscriptionService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
                if (empty($merchant)) {
                    throw new \ErrorException('Invalid mobile key');
                }

                if ($merchant->id != $subscription->idMerchant) {
                    throw new \ErrorException('Unauthorized merchant');
                }
            }

            $order = $this->subscriptionService->orderRepository->findWhere(['idSubscription' => $subscription->id])->first();

            if ($order) {
                $this->subscriptionService->subscriptionRepository->update(['isDeleted' => 1], $subscription->id);
            } else {
                $this->subscriptionService->subscriptionRepository->delete($subscription->id);
            }

            return $this->respond();
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    ### user defined actions

    public function find_by_merchant() {

//        $name = 'find_by_merchant';
//        Log::info("SubscriptionController {$name} called");

        $validator = Validator::make($this->request->all(), [
            'idMerchant'    => 'required',
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {
            $model = $this->subscriptionService->find_by_merchant($data);

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new SubscriptionTransformer());
                $model = $fractalManager->createData($model)->toArray();
            }

            return $this->respond($model);
//            var_dump($model);
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function find_by_mobile_key() {
        $validator = Validator::make($this->request->all(), [
            'idMerchant'    => 'required',
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $data = $this->request->all();

        try {

            if (isset($data['isAdmin']) && $data['isAdmin'] == 1) {
                $admin = $this->subscriptionService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($admin)) {
                    throw new \ErrorException('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->subscriptionService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \ErrorException('Merchant Id is required');
                }
            } else {
                $merchant = $this->subscriptionService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($merchant)) {
                    throw new \ErrorException('Invalid mobile key');
                }

                $data['isAdmin'] = 0;
            }

            $model = $this->subscriptionService->find_by_mobile_key($data);

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new SubscriptionTransformer());
                $model = $fractalManager->createData($model)->toArray();
            }

            return $this->respond($model);
//            var_dump($model);
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

#TODO create and use PassTransformer instead of SubscriptionTransformer
    public function search_by_text() {
        try {
            $model = $this->subscriptionService->search_by_text($this->request->all(), true);

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new SubscriptionTransformer());
                $model = $fractalManager->createData($model)->toArray();
            }

            return $this->respond($model);
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function search_by_zipcode() {

        try {
            $model = $this->subscriptionService->search_by_zipcode($this->request->all());

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new SubscriptionTransformer());
                $model = $fractalManager->createData($model)->toArray();
            }

            return $this->respond($model);
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    public function find_by_title() {
        $validator = Validator::make($this->request->all(), [
            'szCleanTitle'  => 'required',
//            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {
            $model = $this->subscriptionService->find_by_title($this->request->all());

            if (empty($model) || count($model) == 0) {
                return $this->respond();
            }

            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $model = new Collection($model, new SubscriptionTransformer());
            $model = $fractalManager->createData($model)->toArray();

            return $this->respond($model);
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Find subscriptions by category id
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function find_by_category() {
        $validator = Validator::make($this->request->all(), [
            'categoryId'  => 'required',
//            'merchantId'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        try {
            $model = $this->subscriptionService->find_by_category($this->request->all());

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new SubscriptionTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Upgrade pop up
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function upgrade_popup() {

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
            $user = $this->subscriptionService->userRepository->findWhere([
                'szMobileKey' => $szMobileKey,
                'isDeleted' => 0,
            ])->first();

            if (empty($user)) {
                throw new \ErrorException('Invalid mobile key');
            }

            $order = $this->subscriptionService->orderRepository->findWhere([
                'id' => $idOrder,
                'iCompleted' => 1,
            ])->first();

            if (empty($order)) {
                throw new \ErrorException('No order found');
            }

            if ($order->idUser != $user->id) {
                throw new \ErrorException('Unauthorized user');
            }

            $subscription = $this->subscriptionService->subscriptionRepository->findWhere([
                'id' => $order->idSubscription,
                'isDeleted' => 0,
            ])->first();

            if (empty($subscription)) {
                throw new \ErrorException('No subscription found');
            }

            $query = $this->subscriptionService->subscriptionRepository->getModel()->newQuery()
                ->from('tblsubscriptions as s')
                ->join('tblmerchant as m', 's.idMerchant', '=', 'm.id')
                ->where('s.isDeleted', 0)
                ->where('s.iActive', 1)
                ->where('s.szPassType', 'package pass')
                ->where('s.fPrice', '>', $subscription->fPrice)
                ->where('m.iActive', 1)
                ->where('m.isDeleted', 0)
                ->where('m.id', $subscription->idMerchant)
                ->select('s.*')
                ->orderBy('fPrice', 'asc');
//                ->first();

//            \Log::info("{$subscription->id}, {$subscription->idMerchant}, {$subscription->fPrice}");
//            \Log::info($query->toSql());

            $sub = $query->first();

            if (empty($sub)) {
                throw new \ErrorException('No record found');
            }

            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $sub = new Item($sub, new SubscriptionTransformer());
            $sub = $fractalManager->createData($sub)->toArray();

            return $this->respond($sub);
        } catch (\ErrorException $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }
}
