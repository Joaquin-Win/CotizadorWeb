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

    'transoft' => [
        'base_url'            => env('TRANSOFT_BASE_URL'),
        'username'            => env('TRANSOFT_USERNAME'),
        'password'            => env('TRANSOFT_PASSWORD'),
        'operation_id'        => env('TRANSOFT_OPERATION_ID'),
        'dador_cuit'          => env('TRANSOFT_DADOR_CUIT'),
        'transportista_cuit'  => env('TRANSOFT_TRANSPORTISTA_CUIT'),
        'webhook_secret'      => env('TRANSOFT_WEBHOOK_SECRET'),
        'timeout'             => env('TRANSOFT_TIMEOUT', 15),
    ],

];
