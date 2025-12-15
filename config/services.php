<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key'     => env('AWS_ACCESS_KEY_ID'),
        'secret'  => env('AWS_SECRET_ACCESS_KEY'),
        'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WHATSAPP CLOUD API CONFIG
    |--------------------------------------------------------------------------
    */

    'waba' => [

    'base_url' => env('WABA_BASE_URL', 'https://graph.facebook.com'),

    'version'  => env('WABA_API_VERSION', 'v22.0'),

    // WA Business Cloud API Access Token
    'token'    => env('WABA_ACCESS_TOKEN'),

    // Phone Number ID
    'phone_id' => env('WABA_PHONE_NUMBER_ID'),

    // Business ID
    'business_id' => env('WABA_BUSINESS_ID'),
],


];
