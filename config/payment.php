<?php


return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when processing payments. You may change this to any of the gateways
    | defined in the "gateways" array below.
    |
    */

    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'credit_card'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many payment gateways as you wish. Each
    | gateway can have its own configuration options.
    |
    */

    'gateways' => [
        'credit_card' => [
            'api_key' => env('CREDIT_CARD_API_KEY', 'test_cc_key'),
            'secret' => env('CREDIT_CARD_SECRET', 'test_cc_secret'),
            'mode' => env('CREDIT_CARD_MODE', 'sandbox'),
            'merchant_id' => env('CREDIT_CARD_MERCHANT_ID', 'merchant_123'),
        ],

        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID', 'test_paypal_client'),
            'secret' => env('PAYPAL_SECRET', 'test_paypal_secret'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
        ],

        'stripe' => [
            'public_key' => env('STRIPE_PUBLIC_KEY', 'pk_test_123'),
            'secret_key' => env('STRIPE_SECRET_KEY', 'sk_test_123'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | General payment processing settings.
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'USD'),
    'timeout' => env('PAYMENT_TIMEOUT', 30), // seconds
];
