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

    'ternis_auth' => [
        'base_url' => env('TERNIS_AUTH_BASE_URL', 'https://auth.ternis.net'),
        'client_id' => env('TERNIS_AUTH_CLIENT_ID'),
        'client_secret' => env('TERNIS_AUTH_CLIENT_SECRET'),
        'redirect_uri' => env('TERNIS_AUTH_REDIRECT_URI'),
        'scopes' => env('TERNIS_AUTH_SCOPES', 'openid profile email ternis:sso'),
        'avatar_base' => env('TERNIS_AVATAR_BASE_URL', 'https://user.t-api.de'),
        'end_session' => env('TERNIS_AUTH_END_SESSION', false),
        'end_session_path' => env('TERNIS_AUTH_END_SESSION_PATH', '/oauth/logout'),
        'post_logout_redirect_uri' => env('TERNIS_AUTH_POST_LOGOUT_REDIRECT_URI'),
    ],

];
