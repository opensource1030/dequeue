<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\Controller as BaseController;

use Log;

// use Illuminate\Auth\Guard;
// use App\Model\User;

class ApiController extends BaseController
{

	public $response;
	public $request;
	// public $auth;


	public function __construct(
			ResponseFactory 	$response,
			Request 			$request
			// Guard 				$auth
		) {

		$this->response 	= $response;
		$this->request 		= $request;
		// $this->auth 		= $auth;
		// $this->currentUser 	= $this->auth->user();

		// Log::info('__construct');
	}


	/**
	 *
	 * @param array $data
	 * @param string response_message
	 * @param int $status_code
	 * @return Response
	 */
	public function respond($data = [], $response_message = 'Success', $status_code = 200) {

		// if this is and internal request, we only return the data
		// if ($this->request->input('no-json'))
		// 	return $data;

		$message = [
			'status'	=> true,
			'message'	=> $response_message,
			'data'		=> $data
		];

		// Log::info('respond');

		return $this->response->json($message, $status_code);
	}


	public function respondWithErrors($errors = 'Fail', $error_code = 10, $status_code = 400) {

		if (is_string($errors))
			$errors = [$errors];

		$message = [
			'status'		=> false,
			'error_code'	=> $error_code,
			'errors'		=> $errors
		];

		return $this->response->json($message, $status_code);
	}


	public function respondWithValidationErrors($errors, $status_code = 400) {

		$message = [
			'status'	=> false,
			'message'	=> "Please double check your form",
			'validation_errors'	=> $errors
		];

		return $this->response->json($message, $status_code);
	}


	public function respondCreate($message = "Resource created") {
		return $this->respond($message, 201);
	}


	public function respondUnauthorized($error_code, $message = "You are not authorized for this") {
		return $this->respondWithErrors($message, $error_code, 401);
	}


	public function respondInternalError($error_code, $message = "Internal error") {
		return $this->respondWithErrors($message, $error_code, 500);
	}


	public function respondOk($message = "Done") {
		return $this->respond([], $message, 200);
	}
}
