<?php

return [
    'export_ttl_days' => 7,
    'deletion_grace_days' => 14,
    'api_log_retention_months' => 12,
    'guest_ip_retention_days' => 30,

    /*
     * Session tokens of the public dashboard's auth API. They are
     * credentials, so they do not live forever: at most
     * `session_token_max_active` are valid per account at a time (the
     * oldest is revoked when a new one is minted), and dead rows are
     * removed by `privacy:prune-session-tokens` after
     * `session_token_retention_days`.
     */
    'session_token_max_active' => 5,
    'session_token_retention_days' => 30,
];
