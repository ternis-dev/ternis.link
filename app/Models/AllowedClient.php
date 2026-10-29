<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

class AllowedClient extends Model
{
    use HasUlids;

    public const CACHE_KEY = 'allowed_clients:active';

    protected $fillable = [
        'name',
        'ip_address',
        'user_agent',
        'created_by',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the current incoming request matches any active allowed client rule.
     */
    public static function isAllowed(?Request $request = null): bool
    {
        $request ??= request();
        if (! $request instanceof Request) {
            return false;
        }

        return static::isAllowedPair($request->ip(), $request->userAgent());
    }

    /**
     * Check if a given IP and User-Agent pair matches any active allowed client rule.
     */
    public static function isAllowedPair(?string $ip, ?string $userAgent): bool
    {
        if (empty($ip) && empty($userAgent)) {
            return false;
        }

        /** @var Collection<int, static> $rules */
        $rules = Cache::remember(self::CACHE_KEY, 300, function () {
            return static::where('is_active', true)->get();
        });

        foreach ($rules as $rule) {
            if ($rule->matchesPair($ip, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if this rule matches the supplied IP and User-Agent.
     *
     * - If both ip_address and user_agent are specified: BOTH must match.
     * - If only ip_address is specified: IP must match (any user agent).
     * - If only user_agent is specified: User-Agent must match (any IP).
     */
    public function matchesPair(?string $ip, ?string $userAgent): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $ruleIp = trim($this->ip_address ?? '');
        $ruleUa = trim($this->user_agent ?? '');

        $hasIp = $ruleIp !== '';
        $hasUa = $ruleUa !== '';

        if (! $hasIp && ! $hasUa) {
            return false;
        }

        if ($hasIp) {
            if (empty($ip)) {
                return false;
            }

            if (! self::matchesIp($ruleIp, $ip)) {
                return false;
            }
        }

        if ($hasUa) {
            if (empty($userAgent)) {
                return false;
            }

            if (! self::matchesUserAgent($ruleUa, $userAgent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Match IP address against rule (supports single IP, comma-separated IPs, CIDR, or '*').
     */
    public static function matchesIp(string $ruleIp, string $requestIp): bool
    {
        if ($ruleIp === '*' || $ruleIp === $requestIp) {
            return true;
        }

        $targets = array_filter(array_map('trim', explode(',', $ruleIp)));
        if (empty($targets)) {
            return false;
        }

        try {
            return IpUtils::checkIp($requestIp, $targets);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Match User-Agent against rule (case-insensitive, exact, wildcard pattern '*', or substring).
     */
    public static function matchesUserAgent(string $pattern, string $userAgent): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '*' || $pattern === '') {
            return true;
        }

        // Exact match (case-insensitive)
        if (strcasecmp($pattern, $userAgent) === 0) {
            return true;
        }

        // Wildcard pattern match (e.g. *bot*, twins-on-ice*)
        if (str_contains($pattern, '*')) {
            return Str::is(Str::lower($pattern), Str::lower($userAgent));
        }

        // Substring match (e.g. twins-on-ice matching twins-on-ice-fallback/1.0)
        return str_contains(Str::lower($userAgent), Str::lower($pattern));
    }

    /**
     * Friendly description of what kind of rule this is.
     */
    public function getRuleTypeAttribute(): string
    {
        $hasIp = filled($this->ip_address);
        $hasUa = filled($this->user_agent);

        if ($hasIp && $hasUa) {
            return 'Pair (IP + UA)';
        }

        if ($hasIp) {
            return 'IP only';
        }

        if ($hasUa) {
            return 'User-Agent only';
        }

        return 'Empty';
    }
}
