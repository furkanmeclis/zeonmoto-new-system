<?php

// config for FurkanMeclis/PayTRLink
return [
    'merchant_id' => env('PAYTR_MERCHANT_ID'),
    'merchant_key' => env('PAYTR_MERCHANT_KEY'),
    'merchant_salt' => env('PAYTR_MERCHANT_SALT'),
    'debug_on' => env('PAYTR_DEBUG_ON', 1),

    /*
     * PayTR API endpoints
     */
    'api' => [
        'base_url' => env('PAYTR_API_BASE_URL', 'https://www.paytr.com'),
        'create_link' => '/odeme/api/link/create',
        'delete_link' => '/odeme/api/link/delete',
        'send_sms' => '/odeme/api/link/send-sms',
        'send_email' => '/odeme/api/link/send-email',
    ],

    /*
     * HTTP timeout in seconds
     */
    'timeout' => env('PAYTR_TIMEOUT', 30),
];
