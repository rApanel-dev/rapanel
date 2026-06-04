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

    'discord' => [
        'bot_token'  => env('DISCORD_BOT_TOKEN'),
        'server_id'  => env('DISCORD_SERVER_ID'),
        'invite_url' => env('DISCORD_INVITE_URL', '#'),
    ],

    'ra' => [
        'emulator'     => env('RA_EMULATOR', 'rathena'),
        'game_mode'    => env('RA_GAME_MODE', 'renewal'),
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
        'ws_port'            => env('RA_WS_PORT', 3001),
        'ws_secret'          => env('RA_WS_SECRET'),
        // Overrides opcionales — si no se definen, se derivan de RA_LOGIN_IP + RA_WS_PORT
        'ws_internal_url'    => env('RA_WS_INTERNAL_URL'),
        'ws_public_url'      => env('RA_WS_PUBLIC_URL'),
        'require_email_verify' => env('RA_REQUIRE_EMAIL_VERIFY', false),
        '2fa_enabled'          => env('RA_2FA_ENABLED', false),
        '2fa_force_admins'     => env('RA_2FA_FORCE_ADMINS', false),
        'inactivity_timeout'   => (int) env('RA_INACTIVITY_TIMEOUT', 30),
        'login_ip'      => env('RA_LOGIN_IP',  '127.0.0.1'),
        'login_port'    => env('RA_LOGIN_PORT',  6900),
        'char_ip'       => env('RA_CHAR_IP',   '127.0.0.1'),
        'char_port'     => env('RA_CHAR_PORT',   6121),
        'map_ip'        => env('RA_MAP_IP',    '127.0.0.1'),
        'map_port'      => env('RA_MAP_PORT',    5121),
        'web_ip'        => env('RA_WEB_IP',    '127.0.0.1'),
        'web_port'      => env('RA_WEB_PORT',    8080),
        'robrowser_url' => env('RA_ROBROWSER_URL', ''),
    ],

];
