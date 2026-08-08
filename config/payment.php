<?php

use App\Payments\Gateways\EsewaGateway;
use App\Payments\Gateways\KhaltiGateway;
use App\Payments\Gateways\NicAsiaGateway;

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    // Keyed by the checkout `payment.type` slug. Each entry's `route` is the URL
    // segment used for /payment/{route}/initiate and /payment/{route}/callback —
    // it defaults to matching the key, but can differ to preserve a gateway's
    // already-registered callback URL (see nic_asia below).
    //
    // To add a new merchant/bank: add an entry here + a driver implementing
    // App\Payments\Contracts\PaymentGateway. No controller or route changes needed.
    'gateways' => [
        'esewa' => [
            'route' => 'esewa',
            'driver' => EsewaGateway::class,
            'merchant_code' => env('ESEWA_MERCHANT_CODE'),
            'secret_key' => env('ESEWA_SECRET_KEY'),
            'form_url' => env('ESEWA_FORM_URL'),
            'status_check_url' => env('ESEWA_STATUS_CHECK_URL'),
            'success_url' => env('ESEWA_SUCCESS_URL'),
            'failure_url' => env('ESEWA_FAILURE_URL'),
        ],

        'khalti' => [
            'route' => 'khalti',
            'driver' => KhaltiGateway::class,
            'secret_key' => env('KHALTI_SECRET_KEY'),
            'base_url' => env('KHALTI_BASE_URL'),
            'return_url' => env('KHALTI_RETURN_URL'),
            'website_url' => env('KHALTI_WEBSITE_URL'),
        ],

        // Route stays 'nicasia' (no underscore) — matches NICASIA_SUCCESS_URL /
        // NICASIA_CANCEL_URL already registered with CyberSource in .env.
        'nic_asia' => [
            'route' => 'nicasia',
            'driver' => NicAsiaGateway::class,
            'access_key' => env('NICASIA_ACCESS_KEY'),
            'profile_id' => env('NICASIA_PROFILE_ID'),
            'secret_key' => env('NICASIA_SECRET_KEY'),
            'payment_url' => env('NICASIA_PAYMENT_URL'),
            'success_url' => env('NICASIA_SUCCESS_URL'),
            'cancel_url' => env('NICASIA_CANCEL_URL'),
        ],
    ],

];
