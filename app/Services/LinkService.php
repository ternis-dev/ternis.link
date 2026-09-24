<?php

namespace App\Services;

use App\Exceptions\JunkUrlException;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LinkService
{
    /**
     * Daily creation quota for anonymous (guest) links, enforced per
     * hashed creator IP. Mirrors the free plan quota.
     */
    public const ANONYMOUS_DAILY_LIMIT = 50;

    /**
     * Guest links are always auto-generated — custom slugs are reserved
     * for logged-in users. Guests get an 8-char slug, logged-in users
     * get a 6-char slug by default (or their plan minimum when set).
     */
    public const GUEST_SLUG_LENGTH = 8;

    public const AUTHENTICATED_DEFAULT_SLUG_LENGTH = 6;

    /**
     * @deprecated Use GUEST_SLUG_LENGTH. Kept for backwards compat.
     */
    public const ANONYMOUS_MIN_SLUG_LENGTH = 8;

    /**
     * Hot slug cache TTL in seconds. Cache hits skip the DB lookup on
     * the redirect path; writes invalidate via Link model events plus
     * explicit forgets below and in DeactivateExpiredLinks.
     */
    public const RESOLVE_CACHE_TTL = 300;

    public function __construct(
        private SlugGeneratorService $slugGenerator,
        private JunkUrlDetector $junkUrls,
    ) {}

    /**
     * Create a new short link.
     *
     * Enforces the owner's plan: custom-slug charset/minimum-length,
     * per-domain slug uniqueness, daily creation quota, and
     * per-minute rate limit.
     *
     * Anonymous links ($user === null) NEVER accept custom slugs —
     * they are always auto-generated (GUEST_SLUG_LENGTH chars). Pass
     * the SHA-256 of the creator IP via $creatorIpHash to attribute
     * them for the per-IP daily quota.
     *
     * Authenticated auto-generated slugs use the owner's plan minimum
     * (falling back to AUTHENTICATED_DEFAULT_SLUG_LENGTH).
     *
     * @throws ValidationException On slug or daily-quota violations (HTTP 422).
     * @throws JunkUrlException On scanner-junk destinations (HTTP 422).
     * @throws ThrottleRequestsException On per-minute rate-limit violations (HTTP 429).
     */
    public function create(
        string $destinationUrl,
        Domain $domain,
        ?User $user = null,
        ?string $customSlug = null,
        ?\DateTimeInterface $expiresAt = null,
        ?string $creatorIpHash = null,
        ?int $generatedLength = null,
    ): Link {
        $destinationUrl = trim($destinationUrl);

        // Scanner probes never become links — rejected before quota or
        // rate-limit state is touched, so junk can't burn anyone's budget.
        $this->junkUrls->rejectIfJunk($destinationUrl);

        $customSlug = $customSlug !== null && trim($customSlug) === '' ? null : $customSlug;

        if ($user === null && $customSlug !== null) {
            throw ValidationException::withMessages([
                'slug' => 'Custom slugs are for logged-in users only. Guests get an auto-generated link.',
            ]);
        }

        $minLength = $user?->plan?->min_slug_length ?? self::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        // Explicit length choice (dashboard picker) wins over the plan
        // default; clamped defensively so callers can't pass absurdities.
        $generatedLength = $generatedLength !== null
            ? max(1, min(64, $generatedLength))
            : ($user === null ? self::GUEST_SLUG_LENGTH : $minLength);

        if (! $domain->is_active) {
            throw ValidationException::withMessages([
                'domain_id' => 'The selected domain is not active.',
            ]);
        }

        if ($domain->user_id !== null && $domain->verified_at === null) {
            throw ValidationException::withMessages([
                'domain_id' => 'The selected domain has not been verified yet. Publish the DNS TXT record, then verify it.',
            ]);
        }

        if ($user) {
            $this->ensureWithinDailyQuota($user);
            $this->ensureWithinRateLimit($user);
        } elseif ($creatorIpHash !== null) {
            $this->ensureAnonymousWithinDailyQuota($creatorIpHash);
        }

        if ($customSlug !== null) {
            $this->validateCustomSlug($customSlug, $domain->id, $minLength, $user);
            $slug = $customSlug;
        } else {
            $slug = $this->slugGenerator->generate($generatedLength, $domain->id);
        }

        $link = Link::create([
            'slug' => $slug,
            'destination_url' => $destinationUrl,
            'domain_id' => $domain->id,
            'user_id' => $user?->id,
            'creator_ip_hash' => $creatorIpHash,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);

        if ($user) {
            RateLimiter::hit($this->rateLimitKey($user), 60);
        }

        return $link;
    }

    /**
     * Check whether a slug is still free on the given domain.
     */
    public function slugAvailable(string $slug, string $domainId): bool
    {
        return ! Link::where('domain_id', $domainId)
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * Enforce the plan's daily link-creation quota (null = unlimited).
     *
     * @throws ValidationException
     */
    public function ensureWithinDailyQuota(User $user): void
    {
        $max = $user->plan?->max_links_per_day;

        if ($max === null) {
            return;
        }

        $todayCount = $user->links()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($todayCount >= $max) {
            $planName = $user->plan?->name ?? 'current';

            throw ValidationException::withMessages([
                'destination_url' => "Daily link limit reached ({$max} per day on the {$planName} plan). Try again tomorrow.",
            ]);
        }
    }

    /**
     * Enforce the plan's per-minute creation rate limit.
     *
     * @throws ThrottleRequestsException
     */
    public function ensureWithinRateLimit(User $user): void
    {
        $limit = $user->plan?->rate_limit_per_minute ?? 10;
        $key = $this->rateLimitKey($user);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw new ThrottleRequestsException(
                'Too many links created. Try again in '.RateLimiter::availableIn($key).' seconds.',
                null,
                ['Retry-After' => RateLimiter::availableIn($key)]
            );
        }
    }

    /**
     * Enforce the anonymous daily link-creation quota per hashed creator IP.
     *
     * Only counts guest-created links (user_id null) carrying this IP hash,
     * so internal direct-URL tracking rows (no hash) never count against it.
     *
     * @throws ValidationException
     */
    public function ensureAnonymousWithinDailyQuota(string $creatorIpHash): void
    {
        $todayCount = Link::whereNull('user_id')
            ->where('creator_ip_hash', $creatorIpHash)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($todayCount >= self::ANONYMOUS_DAILY_LIMIT) {
            throw ValidationException::withMessages([
                'destination_url' => 'Daily link limit reached ('.self::ANONYMOUS_DAILY_LIMIT.' per day for guests). Log in with Ternis Auth for a higher quota.',
            ]);
        }
    }

    /**
     * Validate a user-supplied slug against charset, plan minimum
     * length, and per-domain uniqueness.
     *
     * @throws ValidationException
     */
    protected function validateCustomSlug(string $slug, string $domainId, int $minLength, ?User $user): void
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            throw ValidationException::withMessages([
                'slug' => 'The slug may only contain letters, numbers, dashes, and underscores.',
            ]);
        }

        if (strlen($slug) > 255) {
            throw ValidationException::withMessages([
                'slug' => 'The slug may not be greater than 255 characters.',
            ]);
        }

        if (strlen($slug) < $minLength) {
            $planName = $user?->plan?->name ?? 'current';

            throw ValidationException::withMessages([
                'slug' => "The slug must be at least {$minLength} characters for your plan ({$planName}).",
            ]);
        }

        if (! $this->slugAvailable($slug, $domainId)) {
            throw ValidationException::withMessages([
                'slug' => 'This slug is already taken on the selected domain.',
            ]);
        }
    }

    protected function rateLimitKey(User $user): string
    {
        return 'create-link:'.$user->id;
    }

    /**
     * Resolve a slug to a link on the given domain.
     *
     * Hot slugs are cached for RESOLVE_CACHE_TTL seconds. Misses are
     * never cached so a freshly created slug is visible immediately.
     * A stale hit (deactivated/expired while cached) falls through to
     * the database instead of serving the wrong redirect.
     */
    public function resolveSlug(string $slug, Domain $domain): ?Link
    {
        $key = Link::cacheKey($domain->id, $slug);
        $cached = Cache::get($key);

        if ($cached instanceof Link && $cached->domain_id === $domain->id && $cached->slug === $slug) {
            if ($cached->isAccessible()) {
                return $cached;
            }

            Cache::forget($key);
        }

        $link = Link::accessible()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();

        if ($link) {
            Cache::put($key, $link, self::RESOLVE_CACHE_TTL);
        }

        return $link;
    }

    /**
     * Find or create a link record for tracking direct URL redirects (/url/{url}).
     */
    public function findOrCreateDirectUrlLink(string $destinationUrl, Domain $domain): Link
    {
        $hashSlug = 'u_'.substr(hash('sha256', $destinationUrl), 0, 16);

        return Link::firstOrCreate(
            [
                'domain_id' => $domain->id,
                'slug' => $hashSlug,
            ],
            [
                'destination_url' => $destinationUrl,
                'is_active' => true,
            ]
        );
    }

    /**
     * Update a link.
     */
    public function update(Link $link, array $data): Link
    {
        $link->update($data);
        Link::forgetCachedSlug($link->domain_id, $link->slug);

        return $link->fresh();
    }

    /**
     * Soft-deactivate a link (don't hard-delete, preserve analytics).
     */
    public function deactivate(Link $link): void
    {
        $link->update(['is_active' => false]);
        Link::forgetCachedSlug($link->domain_id, $link->slug);
    }
}
