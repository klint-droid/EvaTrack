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
    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'api_key' => env('ONESIGNAL_API_KEY'),
    ],
    'textbee' => [
        'api_key' => env('TEXTBEE_API_KEY'),
        'device_id' => env('TEXTBEE_DEVICE_ID'),
    ],

    'external_households' => [
        'host'    => env('EXTERNAL_HOUSEHOLDS_HOST', 'http://192.168.110.201:8000'),
        'url'     => env('EXTERNAL_HOUSEHOLDS_URL', 'http://192.168.110.201:8000/api/external/households'),
        'api_key' => env('EXTERNAL_HOUSEHOLDS_API_KEY', 'evatrack_live_sec_9a87f12bc34de56f'),
        'timeout' => (int) env('EXTERNAL_HOUSEHOLDS_TIMEOUT', 5),
    ],

];
