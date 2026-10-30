<?php

namespace App\Services;

use App\Models\Link;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class LinkHealthService
{
    public const USER_AGENT = 'ternis.link-HealthMonitor/1.0 (+https://ternis.link/health)';

    public const TIMEOUT_SECONDS = 5;

    public const CONNECT_TIMEOUT_SECONDS = 3;

    public const MAX_REDIRECTS = 3;

    public const NOTIFICATION_THROTTLE_SECONDS = 43200; // 12 hours

    /**
     * Probe a URL and return raw health metrics.
     *
     * @return array{status: string, status_code: ?int, response_time_ms: int, error: ?string}
     */
    public function probe(string $url): array
    {
        $start = microtime(true);

        try {
            $client = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => '*/*',
            ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->withOptions([
                'allow_redirects' => [
                    'max' => self::MAX_REDIRECTS,
                    'strict' => false,
                ],
            ]);

            // Try lightweight HEAD request first
            $response = $client->head($url);

            // Some CDNs/servers block HEAD with 403/405; fallback to GET
            if (in_array($response->status(), [403, 405], true)) {
                $response = $client->get($url);
            }

            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();

            $status = match (true) {
                $statusCode >= 200 && $statusCode < 400 => 'healthy',
                $statusCode === 429 || ($statusCode >= 500 && $statusCode <= 599) => 'degraded',
                default => 'unreachable',
            };

            $error = $status === 'healthy' ? null : "HTTP {$statusCode}";

            return [
                'status' => $status,
                'status_code' => $statusCode,
                'response_time_ms' => $elapsedMs,
                'error' => $error,
            ];
        } catch (Throwable $e) {
            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
            $message = trim($e->getMessage());

            return [
                'status' => 'unreachable',
                'status_code' => null,
                'response_time_ms' => $elapsedMs,
                'error' => $message !== '' ? mb_substr($message, 0, 250) : 'Connection failed or timed out',
            ];
        }
    }

    /**
     * Check a specific Link's destination health and update its attributes.
     *
     * @return array{status: string, status_code: ?int, response_time_ms: int, error: ?string}
     */
    public function check(Link $link, bool $notify = true): array
    {
        $result = $this->probe($link->destination_url);

        $link->update([
            'health_status' => $result['status'],
            'health_status_code' => $result['status_code'],
            'health_response_time_ms' => $result['response_time_ms'],
            'health_error' => $result['error'],
            'last_health_check_at' => now(),
        ]);

        if ($notify && $result['status'] === 'unreachable') {
            $this->notifyOwnerIfUnreachable($link, $result);
        }

        return $result;
    }

    /**
     * Send a notification to the link's owner if the destination is unreachable.
     * Throttled to at most once per link per 12 hours.
     */
    public function notifyOwnerIfUnreachable(Link $link, array $result): void
    {
        $user = $link->user;
        if (! $user) {
            return;
        }

        $cacheKey = "link:health:unreachable:notify:{$link->id}";
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, self::NOTIFICATION_THROTTLE_SECONDS);

        $reason = $result['error'] ?? 'Destination server is unresponsive';
        $destination = $link->destination_url;

        Notifier::security(
            $user,
            "Destination Offline: Health Check Failed for /{$link->slug}",
            [
                "Your short link \"/{$link->slug}\" points to \"{$destination}\", which failed automated health monitoring.",
                "Diagnostic details: {$reason} (Latency: {$result['response_time_ms']}ms).",
                'If this target website has moved or is permanently offline, update or deactivate your short link to preserve visitor trust.',
            ],
            DomainUrls::dashboard("/links/{$link->id}"),
            'Inspect Short Link'
        );
    }
}
