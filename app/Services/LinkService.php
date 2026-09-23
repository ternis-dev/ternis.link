<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LinkService
{
    public function __construct(
        private SlugGeneratorService $slugGenerator,
    ) {}

    /**
     * Create a new short link.
     *
     * Enforces the owner's plan: custom-slug charset/minimum-length,
     * per-domain slug uniqueness, daily creation quota, and
     * per-minute rate limit.
     *
     * @throws ValidationException On slug or daily-quota violations (HTTP 422).
     * @throws ThrottleRequestsException On per-minute rate-limit violations (HTTP 429).
     */
    public function create(
        string $destinationUrl,
        Domain $domain,
        ?User $user = null,
        ?string $customSlug = null,
        ?\DateTimeInterface $expiresAt = null,
    ): Link {
        $customSlug = $customSlug !== null && trim($customSlug) === '' ? null : $customSlug;
        $minLength = $user?->plan?->min_slug_length ?? 8;

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
        }

        if ($customSlug !== null) {
            $this->validateCustomSlug($customSlug, $domain->id, $minLength, $user);
            $slug = $customSlug;
        } else {
            $slug = $this->slugGenerator->generate($minLength, $domain->id);
        }

        $link = Link::create([
            'slug' => $slug,
            'destination_url' => $destinationUrl,
            'domain_id' => $domain->id,
            'user_id' => $user?->id,
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
    public function slugAvailable(string $slug, int $domainId): bool
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
     * Validate a user-supplied slug against charset, plan minimum
     * length, and per-domain uniqueness.
     *
     * @throws ValidationException
     */
    protected function validateCustomSlug(string $slug, int $domainId, int $minLength, ?User $user): void
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
     */
    public function resolveSlug(string $slug, Domain $domain): ?Link
    {
        return Link::accessible()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();
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

        return $link->fresh();
    }

    /**
     * Soft-deactivate a link (don't hard-delete, preserve analytics).
     */
    public function deactivate(Link $link): void
    {
        $link->update(['is_active' => false]);
    }
}
