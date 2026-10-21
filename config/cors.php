<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing
|--------------------------------------------------------------------------
|
| Only the auth API of the public dashboard is cross-origin. It lets a
| third-party web app (a browser extension, an embed, a companion
| site) ask whether the visitor is signed in and exchange that session
| for a short-lived API token.
|
| The allowlist is explicit and empty by default: no origin can reach
| these endpoints until it is configured. Credentials are allowed, so
| the list must never contain "*" — a wildcard with credentials is
| rejected by browsers and would leak the session to every site.
|
| Configure with a comma-separated list:
|
|   CORS_ALLOWED_ORIGINS="https://app.example.com,https://other.test"
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

return [
    /*
     * Only the auth API is cross-origin. The REST API under v1/* and
     * v2/* stays same-origin: it authenticates with bearer tokens, not
     * cookies, so it never needs a CORS preflight.
     */
    'paths' => ['auth/api/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    /*
     * The session cookie must travel with the request, otherwise the
     * client could not ask "am I signed in?".
     */
    'supports_credentials' => true,
];
