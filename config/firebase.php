<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is used for Firebase Cloud Messaging (FCM) to send
    | push notifications to devices. You can use either the Legacy API or
    | the new v1 API.
    |
    */

    'fcm' => [
        /*
        |----------------------------------------------------------------------
        | Use FCM v1 API (Recommended)
        |----------------------------------------------------------------------
        |
        | Set to true to use the new FCM v1 API (recommended for new projects).
        | Set to false to use the legacy FCM API (for backward compatibility).
        |
        */
        'use_v1_api' => env('FCM_USE_V1_API', false),

        /*
        |----------------------------------------------------------------------
        | FCM Project ID (for v1 API)
        |----------------------------------------------------------------------
        |
        | Your Firebase project ID. Required if using FCM v1 API.
        | Example: "my-app-12345"
        |
        */
        'project_id' => env('FCM_PROJECT_ID', null),

        /*
        |----------------------------------------------------------------------
        | Service Account JSON Path (for v1 API)
        |----------------------------------------------------------------------
        |
        | Path to your Firebase service account JSON file.
        | Download from: Firebase Console > Settings > Service Accounts
        | Example: "/path/to/serviceAccountKey.json"
        |
        */
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON', null),

        /*
        |----------------------------------------------------------------------
        | FCM Server Key (for Legacy API - Deprecated)
        |----------------------------------------------------------------------
        |
        | Your Firebase Cloud Messaging server key.
        | Find in: Firebase Console > Project Settings > Cloud Messaging tab
        | WARNING: This key is deprecated. Use v1 API instead.
        |
        */
        'server_key' => env('FCM_LEGACY_SERVER_KEY', null),
    ],
];
