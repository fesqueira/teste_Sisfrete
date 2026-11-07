<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'freight' => [
        'url' => env('FREIGHT_API_URL', 'https://api.transportadora.example/v1'),
        'token' => env('FREIGHT_API_TOKEN'),
        'origin_zip_code' => env('FREIGHT_ORIGIN_ZIP_CODE', '17500000'),
        'connect_timeout' => (int) env('FREIGHT_CONNECT_TIMEOUT', 2),
        'timeout' => (int) env('FREIGHT_TIMEOUT', 5),
        'retries' => (int) env('FREIGHT_RETRIES', 3),
        'simulate' => (bool) env('FREIGHT_SIMULATE', false),
        'simulated_failure_rate' => (int) env('FREIGHT_SIMULATED_FAILURE_RATE', 20),
    ],

];
