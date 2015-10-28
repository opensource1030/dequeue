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
            var_dump($result);
//            var_dump($result->creditCards);
//            var_dump($result->paymentMethods);
//            return $this->respond($result->creditCards);
        }

//        return $this->respond(['szCustomerId' => $user->szCustomerId]);
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

    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }
}