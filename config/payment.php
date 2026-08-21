<?php

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    'esewa' => [
        'merchant_code' => env('ESEWA_MERCHANT_CODE'),
        'secret_key' => env('ESEWA_SECRET_KEY'),
        'form_url' => env('ESEWA_FORM_URL'),
        'status_check_url' => env('ESEWA_STATUS_CHECK_URL'),
        'success_url' => env('ESEWA_SUCCESS_URL'),
        'failure_url' => env('ESEWA_FAILURE_URL'),
        // eSewa rejects the form submit itself (ES407 "Maximum amount error") above this
        // total, before our callback ever fires. Null disables the check.
        'max_amount' => env('ESEWA_MAX_AMOUNT'),
    ],

    'nicasia' => [
        'access_key' => env('NICASIA_ACCESS_KEY'),
        'profile_id' => env('NICASIA_PROFILE_ID'),
        'secret_key' => env('NICASIA_SECRET_KEY'),
        'payment_url' => env('NICASIA_PAYMENT_URL'),
        'success_url' => env('NICASIA_SUCCESS_URL'),
        'cancel_url' => env('NICASIA_CANCEL_URL'),
    ],

    'esewa_intent' => [
        'access_key' => env('ESEWA_INTENT_ACCESS_KEY'),
        'product_code' => env('ESEWA_INTENT_PRODUCT_CODE', 'INTENT'),
        'book_url' => env('ESEWA_INTENT_BOOK_URL'),
        'status_url' => env('ESEWA_INTENT_STATUS_URL'),
        'cancel_url' => env('ESEWA_INTENT_CANCEL_URL'),
        'callback_url' => env('ESEWA_INTENT_CALLBACK_URL'),
        'redirect_url' => env('ESEWA_INTENT_REDIRECT_URL'),
    ],

    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'base_url' => env('KHALTI_BASE_URL'),
        'return_url' => env('KHALTI_RETURN_URL'),
        'website_url' => env('KHALTI_WEBSITE_URL'),
    ],

    'cybersource' => [
        'merchant_id' => env('CYBERSOURCE_MERCHANT_ID'),
        'key_id' => env('CYBERSOURCE_KEY_ID'),
        'secret_key' => env('CYBERSOURCE_SECRET_KEY'),
        // REST API host used for both capture-context generation and payment
        // authorization. apitest.cybersource.com for the sandbox, api.cybersource.com
        // in production.
        'run_environment' => env('CYBERSOURCE_RUN_ENVIRONMENT', 'apitest.cybersource.com'),
    ],

];
