<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Enviroment
    |--------------------------------------------------------------------------
    |
    | Please provide the enviroment you would like to use for braintree.
    | This can be either 'sandbox' or 'production'.
    |
    */
    'environment' => env('BRAINTREE_ENVIRONMENT'),
//	'environment' => 'sandbox',

	/*
    |--------------------------------------------------------------------------
    | Merchant ID
    |--------------------------------------------------------------------------
    |
    | Please provide your Merchant ID.
    |
    */
    'merchantId' => env('BRAINTREE_MERCHANT_ID'),
//	'merchantId' => 'ghm77yx7g8cytn59',

	/*
    |--------------------------------------------------------------------------
    | Public Key
    |--------------------------------------------------------------------------
    |
    | Please provide your Public Key.
    |
    */
    'publicKey' => env('BRAINTREE_PUBLIC_KEY'),
//	'publicKey' => 'cxgn6xz9py2gvs3h',

	/*
    |--------------------------------------------------------------------------
    | Private Key
    |--------------------------------------------------------------------------
    |
    | Please provide your Private Key.
    |
    */
    'privateKey' => env('BRAINTREE_PRIVATE_KEY'),
//	'privateKey' => '5668fbfa70c40b3ee41e0cf5620ee275',

	/*
    |--------------------------------------------------------------------------
    | Client Side Encryption Key
    |--------------------------------------------------------------------------
    |
    | Please provide your CSE Key.
    |
    */
	'clientSideEncryptionKey' => '',
	
];