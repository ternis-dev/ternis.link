<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit every /v1/* request into api_request_logs (one row each).
 *
 * Runs as a regular middleware (first in the API groups so its
 * try/finally also covers auth/throttle rejections) and records in
 * a finally block so failures still leave a trace. Recording never
 * throws (ApiRequestLog::record swallows) and stores no bodies,
 * query strings, tokens, or raw IPs — method/host/path/status +
 * duration + IP hash + truncated UA only.
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        } finally {
            $status = isset($response) ? $response->getStatusCode() : null;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            ApiRequestLog::recordCurrent($status, $durationMs);
        }
    }
}
