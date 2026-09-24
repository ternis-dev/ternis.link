<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Raw IP Capture (encrypted, short retention)
    |--------------------------------------------------------------------------
    |
    | Day-to-day features run on one-way IP hashes (see App\Support\IpHash),
    | which is enough for quotas, dedup and rate limits. For abuse
    | forensics the raw address is additionally stored Laravel-encrypted
    | (APP_KEY) and irreversibly deleted after the retention window by
    | the daily `privacy:prune-ips` schedule. Disable entirely with
    | IP_CAPTURE_ENABLED=false.
    |
    */

    'capture_ips' => env('IP_CAPTURE_ENABLED', true),

    'ip_retention_days' => (int) env('IP_RETENTION_DAYS', 30),

];
