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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'contifico' => [
        'base_url' => env('CONTIFICO_BASE_URL', 'https://api.contifico.com/sistema/api/v1'),
        'api_key' => env('CONTIFICO_API_KEY'),
    ],

    'legacy_erp' => [
        'occupancy_table' => env('LEGACY_ERP_OCCUPANCY_TABLE', 'ocupacion_historico'),
    ],

    'inventory' => [
        'google_sheets_url' => env('INVENTORY_GOOGLE_SHEETS_URL'),
    ],

];
