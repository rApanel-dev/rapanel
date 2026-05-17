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

    'ra' => [
        'use_md5'      => env('RA_USE_MD5_PASSWORDS', false),
        'max_accounts' => env('RA_MAX_GAME_ACCOUNTS', 3),
        'reset_map'    => env('RA_RESET_MAP', 'prontera'),
        'reset_x'      => env('RA_RESET_X', 150),
        'reset_y'      => env('RA_RESET_Y', 180),
        'server_name'  => env('RA_SERVER_NAME', 'Ragnarok Online'),
        'vip_enabled'        => env('RA_VIP_ENABLED', false),
        'bank_enabled'       => env('RA_BANK_ENABLED', false),
        'cashpoints_enabled' => env('RA_CASHPOINTS_ENABLED', false),
        'log_path'           => env('RA_LOG_PATH', null),
    ],

];
