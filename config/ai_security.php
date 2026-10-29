<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Threat Detection Master Switch
    |--------------------------------------------------------------------------
    | Enables or disables TypeSafe AI-powered threat analysis and moderation.
    */
    'enabled' => (bool) env('AI_SECURITY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | TypeSafe AI Provider Credentials
    |--------------------------------------------------------------------------
    | API Key and optional custom base URL for the TypeSafe/Jev model client.
    */
    'api_key' => env('TYPESAFE_API_KEY'),
    'base_url' => env('TYPESAFE_BASE_URL'),
    'model' => env('TYPESAFE_MODEL', 'jev-latest'),

    /*
    |--------------------------------------------------------------------------
    | Target Scopes for Automated Evaluation
    |--------------------------------------------------------------------------
    | Evaluates links created anonymously by guests or accounts newer than X days.
    */
    'evaluate_guests' => (bool) env('AI_SECURITY_EVAL_GUESTS', true),
    'evaluate_new_users_days' => (int) env('AI_SECURITY_NEW_USER_DAYS', 7),
    'evaluate_max_lifetime_links' => (int) env('AI_SECURITY_MAX_LIFETIME_LINKS', 5),

    /*
    |--------------------------------------------------------------------------
    | Threat Decision Thresholds (0 - 100)
    |--------------------------------------------------------------------------
    */
    'quarantine_threshold' => (int) env('AI_SECURITY_QUARANTINE_SCORE', 80),
    'bot_protect_threshold' => (int) env('AI_SECURITY_BOT_PROTECT_SCORE', 60),

    /*
    |--------------------------------------------------------------------------
    | Burst Attack Velocity Limits
    |--------------------------------------------------------------------------
    */
    'burst_limit_per_minute' => (int) env('AI_SECURITY_BURST_LIMIT', 10),
];
