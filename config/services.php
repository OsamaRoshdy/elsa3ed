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

    'daftra' => [
        'base_url' => rtrim((string) env('DAFTRA_API_BASE_URL', ''), '/'),
        'token' => env('DAFTRA_API_TOKEN'),
        'auth_type' => env('DAFTRA_AUTH_TYPE', 'apikey'),
        'webhook_secret' => env('DAFTRA_WEBHOOK_SECRET'),
        'currency' => env('DAFTRA_CURRENCY', 'EGP'),
        'low_stock_threshold' => (int) env('DAFTRA_LOW_STOCK_THRESHOLD', 10),
        'timeout' => (int) env('DAFTRA_TIMEOUT', 30),
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

];
