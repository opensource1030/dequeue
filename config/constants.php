<?php

return [
    '__MAIL_CHIMP_API_KEY__'            => env('MAIL_CHIMP_API_KEY'),
    '__MAIL_CHIMP_LIST_KEY__'           => env('MAIL_CHIMP_LIST_KEY'),

//    '__PAYPAL_API_USERNAME__'           => 'coolaj_1345697072_biz_api1.gmail.com',
//    '__PAYPAL_API_PASSWORD__'           => '1345697097',
//    '__PAYPAL_API_SIGNATURE__'          => 'AgepJub252CnqrjWo1MSvqbn9e4tAAkAY588oagb7YbSAxCOk8NLl1a.',
//    '__PAYPAL_CURRENCY_CODE__'          => 'USD',
//    '__PAYPAL_PAYMENT_TYPE__'           => 'Sale',
//    '__PAYPAL_SANDBOX_FLAG__'           => true,
//    '__PAYPAL_CASHOUT_ENVIRONMENT__'    => 'sandbox',

    '__MONTHLY_PLAN__'                  => env('BRAINTREE_MONTHLY_PLAN'),
    '__YEARLY_PLAN__'                   => env('BRAINTREE_YEARLY_PLAN'),

    '__MAIN_SITE_PATH__'                => env('MAIN_SITE_PATH'),
    '__MAIN_SITE_URL__'                 => env('MAIN_SITE_URL'),

    '__IMAGE_DIR__'                     => 'images/',
    '__UPLOAD_SUBSCRIPTION_IMAGE_DIR__' => 'subscriptionImage/archiveSubscriptionImage/',
    '__UPLOAD_CATEGORY_IMAGE_DIR__'     => 'categoryImage/archiveCategoryImage/',
    '__UPLOAD_USER_IMAGE_DIR__'         => 'userImage/archiveUserImage/',
    '__UPLOAD_MERCHANT_IMAGE_DIR__'     => 'merchantImage/archiveMerchantImage/',

    '__SUPPORT_EMAIL_ADDRESS__'         => 'support@chooseyourpass.com',
    '__FEEDBACK_EMAIL_ADDRESS__'        => 'feedback@chooseyourpass.com',
    '__NOTIFICATIN_EMAIL__'             => 'merchantteam@chooseyourpass.com',

    '__INVITE_REFERRAL_CREDIT__'        => 5.0,
];