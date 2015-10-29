<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Database\Services\CardService;
use App\Database\Services\UserService;

class CardController extends ApiController
{
    protected $cardService;
    protected $userService;

    public function __construct(
        ResponseFactory $response,
        Request $request,
        CardService $cardService,
        UserService $userService
    )
    {
        parent::__construct($response, $request);
        $this->cardService = $cardService;
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        $user = $this->userService->get_by_key($szMobileKey);

        if (empty($user)) {
            return $this->respondWithErrors('Invalid mobile key');
        }

        if ($user->szCustomerId == '') {
            return $this->respond([], 'Unregistered customer');
        }

        $result = $this->cardService->get_all_cards($user->szCustomerId);

        if ($result) {
//            var_dump($result);
//            var_dump($result->creditCards);
//            var_dump($result->paymentMethods);
            return $this->respond($result);
        }

        return $this->respond(['customerId' => $user->szCustomerId]);
//        return $this->respond();
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'number'        => 'required',
            'expirationMonth'   => 'required',
            'expirationYear'    => 'required',
            'cvv'           => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $number = $this->request['number'];
        $expirationMonth = $this->request['expirationMonth'];
        $expirationYear = $this->request['expirationYear'];
        $cvv = $this->request['cvv'];

        $user = $this->userService->get_by_key($szMobileKey);

        if (empty($user)) {
            return $this->respondWithErrors('Invalid mobile key');
        }

        if ($user->szCustomerId == '') {
            return $this->respond([], 'Unregistered customer');
        }

        try {
            $result = $this->cardService->create_card($number, $expirationMonth, $expirationYear, $cvv, $user->szCustomerId);

            var_dump($result);
//        return $this->respond($result);
        } catch (\Braintree_Exception $be) {
            return $this->respondWithErrors($be->getMessage(), $be->getCode());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];

        $user = $this->userService->get_by_key($szMobileKey);

        if (empty($user)) {
            return $this->respondWithErrors('Invalid mobile key');
        }

        if ($user->szCustomerId == '') {
            return $this->respond([], 'Unregistered customer');
        }

        $result = $this->cardService->get_card($id, $user->szCustomerId);

//        var_dump($result);
        return $this->respond($result);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
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

        $szMobileKey = $this->request['szMobileKey'];

        $user = $this->userService->get_by_key($szMobileKey);

        if (empty($user)) {
            return $this->respondWithErrors('Invalid mobile key');
        }

        if ($user->szCustomerId == '') {
            return $this->respond([], 'Unregistered customer');
        }

        $result = $this->cardService->delete_card($id, $user->szCustomerId);

//        var_dump($result);
        if ($result) {
            return $this->respond();
        } else {
            return $this->respondWithErrors();
        }
    }

    public function set_default_card() {

        $validator = Validator::make($this->request->all(), [
            'szMobileKey'   => 'required',
            'id'            => 'required'
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator->errors());
        }

        $szMobileKey = $this->request['szMobileKey'];
        $id = $this->request['id'];

        $user = $this->userService->get_by_key($szMobileKey);

        if (empty($user)) {
            return $this->respondWithErrors('Invalid mobile key');
        }

        if ($user->szCustomerId == '') {
            return $this->respond([], 'Unregistered customer');
        }

        $result = $this->cardService->delete_card($id, $user->szCustomerId);

//        var_dump($result);
        if ($result) {
            $this->cardService->userRepository->update([
                'szPaymentToken' => $id,
            ], $user->id);
            return $this->respond();
        } else {
            return $this->respondWithErrors();
        }
    }
}