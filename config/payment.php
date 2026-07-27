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
    ],

    'nicasia' => [
        'access_key' => env('NICASIA_ACCESS_KEY'),
        'profile_id' => env('NICASIA_PROFILE_ID'),
        'secret_key' => env('NICASIA_SECRET_KEY'),
        'payment_url' => env('NICASIA_PAYMENT_URL'),
        'success_url' => env('NICASIA_SUCCESS_URL'),
        'cancel_url' => env('NICASIA_CANCEL_URL'),
    ],

    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'base_url' => env('KHALTI_BASE_URL'),
        'return_url' => env('KHALTI_RETURN_URL'),
        'website_url' => env('KHALTI_WEBSITE_URL'),
    ],

];
