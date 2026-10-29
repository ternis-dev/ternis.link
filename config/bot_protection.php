<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bot Protection Master Switch
    |--------------------------------------------------------------------------
    | When enabled, links with bot_protection=true and suspicious user-agents
    | are routed to the dedicated verification gateway.
    */
    'enabled' => (bool) env('BOT_PROTECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Protected Gateway Domain
    |--------------------------------------------------------------------------
    | Canonical host where challenges, Turnstile puzzles, and password checks run.
    */
    'protected_host' => env('DOMAIN_PROTECTED', 'protected.ternis.link'),

    /*
    |--------------------------------------------------------------------------
    | Remember Non-Bot Human Status (Minutes)
    |--------------------------------------------------------------------------
    | Duration to remember a verified human visitor across the link network
    | before requiring a new Turnstile challenge (default: 2 hours).
    */
    'remember_duration_minutes' => (int) env('BOT_PROTECTION_REMEMBER_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | Cookie Name
    |--------------------------------------------------------------------------
    | Name of the secure signed cookie remembering human verification.
    */
    'cookie_name' => 'tl_human_verified',

    /*
    |--------------------------------------------------------------------------
    | Suspicious User-Agent Screening
    |--------------------------------------------------------------------------
    | Automatically detect and filter suspicious scrapers, automated probes,
    | and command-line HTTP clients.
    */
    'filter_suspicious_user_agents' => (bool) env('BOT_PROTECTION_FILTER_UA', true),

    'suspicious_user_agents' => [
        'curl/',
        'wget/',
        'python-requests',
        'python-urllib',
        'aiohttp',
        'httpx',
        'scrapy',
        'httpclient',
        'go-http-client',
        'postmanruntime',
        'headlesschrome',
        'phantomjs',
        'selenium',
        'puppeteer',
        'playwright',
        'zgrab',
        'nmap',
        'libwww-perl',
        'masscan',
        'nikto',
        'sqlmap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting on Protected Gateways
    |--------------------------------------------------------------------------
    | Throttle brute-force verification and password attempts.
    */
    'rate_limit' => [
        'max_attempts' => (int) env('BOT_PROTECTION_MAX_ATTEMPTS', 30),
        'decay_minutes' => 1,
    ],
];
