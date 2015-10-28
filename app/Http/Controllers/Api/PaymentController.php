<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

use App\Database\Services\PaymentService;

class PaymentController extends ApiController
{
    protected $paymentService;

    public function __construct(
        ResponseFactory $response,
        Request $request,
        PaymentService $paymentService
    )
    {
        parent::__construct($response, $request);
        $this->categoryRepository = $paymentService;
    }


}