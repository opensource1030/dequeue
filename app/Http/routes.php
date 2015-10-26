<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => '/api/v1'], function () {
    Route::resource('users'         , 'Api\UserController');
	Route::resource('merchants'     , 'Api\MerchantController');
    Route::resource('subscriptions' , 'Api\SubscriptionController');
    Route::resource('orders'        , 'Api\OrderController');
    Route::resource('categories'    , 'Api\CategoryController');
    Route::resource('locations'     , 'Api\LocationController');

    Route::post('users/sign_up', ['as' => 'api.v1.users.sign_up', 'uses' => 'Api\UserController@sign_up']);
    Route::post('users/sign_in', ['as' => 'api.v1.users.sign_in', 'uses' => 'Api\UserController@sign_in']);
    Route::post('users/sign_in_by_facebook', ['as' => 'api.v1.users.sign_in_by_facebook', 'uses' => 'Api\UserController@sign_in_by_facebook']);
    Route::post('users/change_password', ['as' => 'api.v1.users.change_password', 'uses' => 'Api\UserController@change_password']);
    Route::post('users/forgot_password', ['as' => 'api.v1.users.forgot_password', 'uses' => 'Api\UserController@forgot_password']);
    Route::post('users/get_invite_code', ['as' => 'api.v1.users.get_invite_code', 'uses' => 'Api\UserController@get_invite_code']);
    Route::post('users/get_login_code', ['as' => 'api.v1.users.get_login_code', 'uses' => 'Api\UserController@get_login_code']);
    Route::post('users/sign_in_with_code', ['as' => 'api.v1.users.sign_in_with_code', 'uses' => 'Api\UserController@sign_in_with_code']);

    Route::post('merchants/forgot_password', ['as' => 'api.v1.merchants.forgot_password', 'uses' => 'Api\MerchantController@forgot_password']);
    Route::post('merchants/update_status', ['as' => 'api.v1.merchants.update_status', 'uses' => 'Api\MerchantController@update_status']);
    Route::post('merchants/get_payout', ['as' => 'api.v1.merchants.get_payout', 'uses' => 'Api\MerchantController@get_payout']);
    Route::post('merchants/update_payout', ['as' => 'api.v1.merchants.update_payout', 'uses' => 'Api\MerchantController@update_payout']);
    Route::post('merchants/update_notes', ['as' => 'api.v1.merchants.update_notes', 'uses' => 'Api\MerchantController@update_notes']);

    Route::post('locations/find_by_mobile_key', ['as' => 'api.v1.locations.find_by_mobile_key', 'uses' => 'Api\LocationController@find_by_mobile_key']);

    Route::post('subscriptions/search_by_text', ['as' => 'api.v1.subscriptions.search_by_text', 'uses' => 'Api\SubscriptionController@search_by_text']);
    Route::post('subscriptions/search_by_zipcode', ['as' => 'api.v1.subscriptions.search_by_zipcode', 'uses' => 'Api\SubscriptionController@search_by_zipcode']);
    Route::post('subscriptions/find_by_merchant', ['as' => 'api.v1.subscriptions.find_by_merchant', 'uses' => 'Api\SubscriptionController@find_by_merchant']);
    Route::post('subscriptions/find_by_mobile_key', ['as' => 'api.v1.subscriptions.find_by_mobile_key', 'uses' => 'Api\SubscriptionController@find_by_mobile_key']);
    Route::post('subscriptions/find_by_category', ['as' => 'api.v1.subscriptions.find_by_category', 'uses' => 'Api\SubscriptionController@find_by_category']);
    Route::post('subscriptions/find_by_title', ['as' => 'api.v1.subscriptions.find_by_title', 'uses' => 'Api\SubscriptionController@find_by_title']);
    Route::post('subscriptions/upgrade_popup', ['as' => 'api.v1.subscriptions.upgrade_popup', 'uses' => 'Api\SubscriptionController@upgrade_popup']);

    Route::post('orders/find_by_user', ['as' => 'api.v1.orders.find_by_user', 'uses' => 'Api\OrderController@find_by_user']);
    Route::post('orders/find_package_pass_by_user', ['as' => 'api.v1.orders.find_package_pass_by_user', 'uses' => 'Api\OrderController@find_package_pass_by_user']);
    Route::post('orders/active_pass', ['as' => 'api.v1.orders.active_pass', 'uses' => 'Api\OrderController@active_pass']);
    Route::post('orders/order_promotional_pass', ['as' => 'api.v1.orders.order_promotional_pass', 'uses' => 'Api\OrderController@order_promotional_pass']);
    Route::post('orders/order_promotional_pass_with_coupon', ['as' => 'api.v1.orders.order_promotional_pass_with_coupon', 'uses' => 'Api\OrderController@order_promotional_pass_with_coupon']);
    Route::post('orders/order_with_braintree', ['as' => 'api.v1.orders.order_with_braintree', 'uses' => 'Api\OrderController@order_with_braintree']);
    Route::post('orders/order_with_braintree_nonce', ['as' => 'api.v1.orders.order_with_braintree_nonce', 'uses' => 'Api\OrderController@order_with_braintree_nonce']);
    Route::post('orders/toggle_auto_renew_flag', ['as' => 'api.v1.orders.toggle_auto_renew_flag', 'uses' => 'Api\OrderController@toggle_auto_renew_flag']);
    Route::post('orders/renew_order', ['as' => 'api.v1.orders.renew_order', 'uses' => 'Api\OrderController@renew_order']);
    Route::post('orders/get_braintree_token', ['as' => 'api.v1.orders.get_braintree_token', 'uses' => 'Api\OrderController@get_braintree_token']);
    Route::post('orders/upgrade_pass', ['as' => 'api.v1.orders.upgrade_pass', 'uses' => 'Api\OrderController@upgrade_pass']);

    Route::get('others/app_images', ['as' => 'api.v1.others.app_images', 'uses' => 'Api\OtherController@app_images']);
    Route::get('others/privacy_policy', ['as' => 'api.v1.others.privacy_policy', 'uses' => 'Api\OtherController@privacy_policy']);
    Route::get('others/terms_conditions', ['as' => 'api.v1.others.terms_conditions', 'uses' => 'Api\OtherController@terms_conditions']);
    Route::get('others/faq_text', ['as' => 'api.v1.others.faq_text', 'uses' => 'Api\OtherController@faq_text']);
    Route::post('others/new_pass_request', ['as' => 'api.v1.others.new_pass_request', 'uses' => 'Api\OtherController@new_pass_request']);
    Route::post('others/new_feedback', ['as' => 'api.v1.others.new_feedback', 'uses' => 'Api\OtherController@new_feedback']);
    Route::post('others/apply_promo_code', ['as' => 'api.v1.others.apply_promo_code', 'uses' => 'Api\OtherController@apply_promo_code']);
    Route::post('others/send_invite_code', ['as' => 'api.v1.others.send_invite_code', 'uses' => 'Api\OtherController@apply_promo_code']);
    Route::post('others/track_level_up', ['as' => 'api.v1.others.track_level_up', 'uses' => 'Api\OtherController@track_level_up']);
    Route::post('others/track_redemption_location', ['as' => 'api.v1.others.track_redemption_location', 'uses' => 'Api\OtherController@track_redemption_location']);
});