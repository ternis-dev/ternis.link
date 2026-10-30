<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server-Side Stats Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Overview totals (clicks, links today, scans) update rapidly, so we keep
    | a shorter default TTL (45s) so the frontend poll actually receives live
    | updates. Heavy aggregate heatmaps remain cached for 600s.
    |
    */
    'overview_ttl' => (int) env('STATS_OVERVIEW_TTL', 45),
    'charts_ttl' => (int) env('STATS_CHARTS_TTL', 60),
    'heatmaps_ttl' => (int) env('STATS_HEATMAPS_TTL', 600),
    'domains_ttl' => (int) env('STATS_DOMAINS_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Frontend Web API Poll Intervals (seconds)
    |--------------------------------------------------------------------------
    |
    | How often the frontend stats page polls the Web API when the tab is open:
    | - admin: 30s
    | - user: 45s (standard authenticated user)
    | - guest: 60s (unauthenticated visitor)
    |
    */
    'poll_interval' => [
        'admin' => (int) env('STATS_POLL_INTERVAL_ADMIN', 30),
        'user' => (int) env('STATS_POLL_INTERVAL_USER', 45),
        'guest' => (int) env('STATS_POLL_INTERVAL_GUEST', 60),
    ],
];
