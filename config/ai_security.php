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
    | AI Confidence Thresholds (0.00 - 1.00)
    |--------------------------------------------------------------------------
    | TypeSafe AI API responses return a confidence probability for verdicts
    | and scores. We use these thresholds to avoid false-positive disruptions:
    |
    | - min_confidence_quarantine: Requires high confidence (default 0.75) to
    |   deactivate and quarantine links. Threats below this (e.g. 0.40) are
    |   downgraded to bot protection instead of taking the link offline.
    |
    | - min_confidence_bot_protect: Minimum confidence required (default 0.35)
    |   to challenge visitors with Turnstile/bot protection. Scores below this
    |   are treated as statistical noise in multi-class output and cleared.
    */
    'min_confidence_quarantine' => (float) env('AI_SECURITY_MIN_CONFIDENCE_QUARANTINE', 0.75),
    'min_confidence_bot_protect' => (float) env('AI_SECURITY_MIN_CONFIDENCE_BOT_PROTECT', 0.35),

    /*
    |--------------------------------------------------------------------------
    | Burst Attack Velocity Limits
    |--------------------------------------------------------------------------
    */
    'burst_limit_per_minute' => (int) env('AI_SECURITY_BURST_LIMIT', 10),
];
