<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides a standard
    | location for storing credentials, allowing packages to easily find
    | them and keeping your application secure.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Email Providers
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

    /*
    |--------------------------------------------------------------------------
    | Slack Notifications
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API (WABA / META)
    |--------------------------------------------------------------------------
    |
    | This section is used by our WabaService() and ProcessBroadcastJob(),
    | allowing the system to send template messages through WhatsApp API.
    |
    | All configs can be set inside your .env file:
    |
    | WABA_TOKEN=xxxx
    | WABA_PHONE_ID=xxxx
    | WABA_BASE_URL=https://graph.facebook.com
    | WABA_API_VERSION=v21.0
    |
    */

    'waba' => [

        // Base Graph API URL
        'base_url' => env('WABA_BASE_URL', 'https://graph.facebook.com'),

        // Graph API Version (default v21.0)
        'version'  => env('WABA_API_VERSION', 'v21.0'),

        // WhatsApp Business Access Token
        'token'    => env('WABA_TOKEN'),

        // WhatsApp Business PHONE NUMBER ID (not business ID)
        'phone_id' => env('WABA_PHONE_ID'),

        // WhatsApp Business ACCOUNT ID (WABA ID)
        'business_id' => env('WABA_BUSINESS_ID'),
    ],

];
