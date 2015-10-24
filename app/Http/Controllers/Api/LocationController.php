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
use App\Database\Repositories\LocationRepository;
use App\Database\Transformers\LocationTransformer;
use App\Database\Services\LocationService;

class LocationController extends ApiController
{
    protected $locationService;

    public function __construct (
        ResponseFactory 		$response,
        Request 				$request,
        LocationService         $locationService
    ) {
        parent::__construct($response, $request);
        $this->locationService = $locationService;
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
    public function store(Request $request)
    {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'szFlag'        => 'required|in:admin,merchant',
            'szLocationCode'=> 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $data = $this->request->all();
        $now = Carbon::now();

        try {
            if ($data['szFlag'] == 'admin') {
                $admin = $this->locationService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->locationService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }
            } else {
                $merchant = $this->locationService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
                if (empty($merchant)) {
                    throw new \Exception('Invalid mobile key');
                }
                $data['idMerchant'] = $merchant->id;
            }

            $loc = $this->locationService->locationRepository->findWhere(['szLocationCode' => $data['szLocationCode']])->first();

            if ($loc) {
                throw new \Exception('Location code already exists"');
            }

            $coordinate = \LocationHelper::getCoordinate($data['szAddress1'] . ' ' . $data['szAddress2'], $data['szCity'] . ',' . $data['szState'] . ',' . $data['szZipCode']);
            if ($coordinate) {
                $latitude = $coordinate['lat'];
                $longitude = $coordinate['long'];
            } else {
                $latitude = '';
                $longitude = '';
            }

            array_pull($data, 'szFlag');
            array_pull($data, 'szMobileKey');
            array_pull($data, 'Photo');

            array_add($data, 'szLatitude', $latitude);
            array_add($data, 'szLongitude', $longitude);
            array_add($data, 'dtAdded', $now);

            $location = $this->locationService->locationRepository->create($data);

            if ($location) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $location = new Item($location, new LocationTransformer());
                $location = $fractalManager->createData($location)->toArray();

                return $this->respond($location);
            }

            return $this->respond();
        } catch (\Exception $e) {
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
            'szLocationCode'=> 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithErrors($validator->errors());
        }

        $data = $this->request->all();
        $now = Carbon::now();

//        try {
            if ($data['szFlag'] == 'admin') {
                $admin = $this->locationService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->locationService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }
            } else {
                $merchant = $this->locationService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();
                if (empty($merchant)) {
                    throw new \Exception('Invalid mobile key');
                }
                $data['idMerchant'] = $merchant->id;
            }

            $loc = $this->locationService->locationRepository->findWhere([
                'szLocationCode' => $data['szLocationCode'],
                'id' => ['id', '!=', $id],
            ])->first();

            if ($loc) {
                throw new \Exception('Location code already exists"');
            }

            $coordinate = \LocationHelper::getCoordinate($data['szAddress1'] . ' ' . $data['szAddress2'], $data['szCity'] . ',' . $data['szState'] . ',' . $data['szZipCode']);

            if ($coordinate) {
                $latitude = $coordinate['lat'];
                $longitude = $coordinate['long'];
            } else {
                $latitude = '';
                $longitude = '';
            }

            array_pull($data, 'szFlag');
            array_pull($data, 'szMobileKey');
            array_pull($data, 'Photo');

            array_add($data, 'szLatitude', $latitude);
            array_add($data, 'szLongitude', $longitude);
            array_add($data, 'dtUpdated', $now);

            $location = $this->locationService->locationRepository->update($data, $id);

            if ($location) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $location = new Item($location, new LocationTransformer());
                $location = $fractalManager->createData($location)->toArray();

                return $this->respond($location);
            }

            return $this->respond();
//        } catch (\Exception $e) {
//            return $this->respondWithErrors($e->getMessage(), $e->getCode());
//        }
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

# user defined

    public function find_by_mobile_key() {
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
                $admin = $this->locationService->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($admin)) {
                    throw new \Exception('Invalid mobile key');
                }

                if (isset($data['idMerchant']) && $data['idMerchant'] > 0) {
                    $merchant = $merchant = $this->locationService->merchantRepository->find($data['idMerchant']);
                } else {
                    throw new \Exception('Merchant Id is required');
                }
            } else {
                $merchant = $this->locationService->merchantRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

                if (empty($merchant)) {
                    throw new \Exception('Invalid mobile key');
                }

                $data['isAdmin'] = 0;
            }

            $model = $merchant->locations()->get();

            if (count($model) > 0) {
                $fractalManager = new Manager();
                $fractalManager->setSerializer(new CustomSerializer());
                $model = new Collection($model, new LocationTransformer());
                $model = $fractalManager->createData($model)->toArray();

                return $this->respond($model);
            }

            return $this->respond();
        } catch (\Exception $e) {
            return $this->respondWithErrors($e->getMessage(), $e->getCode());
        }
    }
}
