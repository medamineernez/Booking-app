<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'fcm' => [
        /*
        |----------------------------------------------------------------------
        | Firebase Cloud Messaging Configuration
        |----------------------------------------------------------------------
        |
        | Configuration for sending push notifications via Firebase Cloud
        | Messaging (FCM). Supports both legacy API and v1 API.
        |
        */

        'use_v1_api' => env('FCM_USE_V1_API', false),
        'project_id' => env('FCM_PROJECT_ID', null),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON', null),
        'server_key' => env('FCM_LEGACY_SERVER_KEY', null),
    ],

];
