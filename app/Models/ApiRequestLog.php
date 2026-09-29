<?php

namespace App\Models;

use App\Support\IpHash;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class ApiRequestLog extends Model
{
    use HasUlids;

    /**
     * Append-only audit rows: created_at is set by the database,
     * updated_at does not exist.
     */
    public const UPDATED_AT = null;

    /**
     * Retention window in days (see privacy:prune-api-logs + privacy policy).
     */
    public const RETENTION_DAYS = 90;

    protected $fillable = [
        'user_id',
        'api_key_id',
        'method',
        'host',
        'path',
        'status',
        'duration_ms',
        'ip_hash',
        'user_agent',
    ];

    protected $casts = [
        'status' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Record one API request. Never throws — auditing must not break
     * the response being audited (e.g. when the database is down).
     *
     * Only coarse, non-identifying data is stored: method, host, path
     * (no query string), status, duration, IP hash, truncated UA. No
     * request bodies, tokens, or raw IPs ever touch this table.
     */
    public static function record(
        string $method,
        string $host,
        string $path,
        ?int $status = null,
        ?int $durationMs = null,
        ?string $userId = null,
        ?string $apiKeyId = null,
        ?string $ipHash = null,
        ?string $userAgent = null,
    ): void {
        try {
            static::create([
                'user_id' => $userId,
                'api_key_id' => $apiKeyId,
                'method' => mb_substr($method, 0, 10),
                'host' => mb_substr($host, 0, 255),
                'path' => mb_substr('/'.ltrim($path, '/'), 0, 2048),
                'status' => $status,
                'duration_ms' => $durationMs,
                'ip_hash' => $ipHash !== null ? mb_substr($ipHash, 0, 64) : null,
                'user_agent' => $userAgent !== null && $userAgent !== '' ? mb_substr($userAgent, 0, 512) : null,
            ]);
        } catch (Throwable) {
            // Logging a request must never raise a new error.
        }
    }

    /**
     * Convenience wrapper for the current HTTP request + response.
     */
    public static function recordCurrent(?int $status, ?int $durationMs): void
    {
        try {
            $request = request();

            /** @var ApiKey|null $apiKey */
            $apiKey = $request->attributes->get('api_key');

            // The user is only attributed when THIS request authenticated
            // (AuthenticateApi sets auth_via on success). Reading
            // $request->user() unconditionally would leak the auth
            // manager's cached user from a previous request in the same
            // process (long-lived workers, test suites) into failed or
            // public rows.
            $authenticated = $request->attributes->get('auth_via') !== null;

            // Strip the query string: paths are enough for capacity
            // planning and abuse triage; params may carry PII.
            $path = '/'.ltrim((string) $request->path(), '/');

            static::record(
                method: $request->method(),
                host: $request->getHost(),
                path: strtok($path, '?') ?: $path,
                status: $status,
                durationMs: $durationMs,
                userId: $authenticated ? $request->user()?->id : null,
                apiKeyId: $apiKey?->getKey(),
                ipHash: IpHash::make($request->ip()),
                userAgent: $request->userAgent(),
            );
        } catch (Throwable) {
            // Never break the response.
        }
    }
}
