<?php

namespace App\Services;

use App\Exceptions\JunkUrlException;
use App\Exceptions\UnsafeUrlException;
use App\Models\ApiKey;
use App\Models\BioPage;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkTarget;
use App\Models\User;
use App\Support\IpCapture;
use App\Support\IpHash;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LinkService
{
    /**
     * Daily creation quota for anonymous (guest) links, enforced per
     * hashed creator IP. Mirrors the free plan quota.
     */
    public const ANONYMOUS_DAILY_LIMIT = 50;

    /** Public destinations stay below common proxy/browser URL limits. */
    public const PUBLIC_MAX_URL_LENGTH = 1024;

    /**
     * Guest links are always auto-generated — custom slugs are reserved
     * for logged-in users. Guests get an 8-char slug, logged-in users
     * get a 6-char slug by default (or their plan minimum when set).
     */
    public const GUEST_SLUG_LENGTH = 8;

    public const AUTHENTICATED_DEFAULT_SLUG_LENGTH = 6;

    public const FREE_MIN_SLUG_LENGTH = 5;

    public const FREE_MAX_SLUG_LENGTH = 9;

    /**
     * Guest links may not outlive a year — anonymous URLs with
     * indefinite lifetimes are a phishing staple.
     */
    public const GUEST_MAX_EXPIRY_DAYS = 365;

    /**
     * Tag rules: lowercase slugs, max 10 per link.
     */
    public const MAX_TAGS = 10;

    public const TAG_PATTERN = '/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/';

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

    /**
     * Normalize user-supplied tags: lowercase, trim, drop empties and
     * invalid entries, dedupe, cap the count. Accepts a string array
     * (API) or a comma-separated string (dashboard form).
     *
     * @return list<string>
     */
    public static function normalizeTags(array|string|null $input): array
    {
        $parts = is_string($input) ? explode(',', $input) : (is_array($input) ? $input : []);

        $tags = [];
        foreach ($parts as $part) {
            $tag = strtolower(trim((string) $part));
            if ($tag !== '' && preg_match(self::TAG_PATTERN, $tag) === 1 && ! in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        return array_slice($tags, 0, self::MAX_TAGS);
    }

    /**
     * Tags the normalizer would drop — for friendly form feedback.
     *
     * @return list<string>
     */
    public static function invalidTags(array|string|null $input): array
    {
        $parts = is_string($input) ? explode(',', $input) : (is_array($input) ? $input : []);

        $invalid = [];
        foreach ($parts as $part) {
            $raw = trim((string) $part);
            if ($raw === '') {
                continue;
            }
            $tag = strtolower($raw);
            if (preg_match(self::TAG_PATTERN, $tag) !== 1 && ! in_array($raw, $invalid, true)) {
                $invalid[] = $raw;
            }
        }

        return $invalid;
    }

    public function __construct(
        private SlugGeneratorService $slugGenerator,
        private JunkUrlDetector $junkUrls,
        private UnsafeUrlValidator $unsafeUrls,
        private SocialPreviewValidator $socialPreview,
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
     * Pass $apiKey when the link is created through the API with a
     * personal key so the row is attributed to it (links.api_key_id).
     * Dashboard creations and SSO-token API calls leave it null.
     *
     * @throws ValidationException On slug or daily-quota violations (HTTP 422).
     * @throws JunkUrlException On scanner-junk destinations (HTTP 422).
     * @throws UnsafeUrlException On structurally unsafe guest destinations (HTTP 422).
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
        ?string $description = null,
        array|string|null $tags = null,
        ?string $creatorIp = null,
        ?ApiKey $apiKey = null,
        ?string $ogTitle = null,
        ?string $ogDescription = null,
        ?string $ogImageUrl = null,
        ?string $password = null,
    ): Link {
        $destinationUrl = trim($destinationUrl);

        if ($destinationUrl === '' || filter_var($destinationUrl, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'destination_url' => 'The destination must be a valid URL.',
            ]);
        }

        // Guests additionally get structural safety checks (intranet /
        // non-public targets, embedded credentials) plus a max lifetime.
        // Safety runs before junk so intranet targets get the accurate
        // "public website" message instead of the scanner-probe one.
        if ($user === null) {
            $this->unsafeUrls->rejectIfUnsafe($destinationUrl);

            if ($expiresAt !== null && $expiresAt > new \DateTimeImmutable('+'.self::GUEST_MAX_EXPIRY_DAYS.' days')) {
                throw UnsafeUrlException::forUrl($destinationUrl, 'expiry-too-far');
            }
        }

        // Scanner probes never become links — rejected before quota or
        // rate-limit state is touched, so junk can't burn anyone's budget.
        $this->junkUrls->rejectIfJunk($destinationUrl);

        // Raw creator IP (guests): the hash drives quotas, the encrypted
        // copy is abuse forensics with a short retention. Raw wins when
        // both are given; the model's cast encrypts it on write.
        if ($creatorIp !== null && trim($creatorIp) !== '') {
            $creatorIpHash = IpHash::make($creatorIp);
        }

        $customSlug = $customSlug !== null && trim($customSlug) === '' ? null : $customSlug;

        if ($user === null && $customSlug !== null) {
            throw ValidationException::withMessages([
                'slug' => 'Custom slugs are for logged-in users only. Guests get an auto-generated link.',
            ]);
        }

        if ($user === null && ($ogTitle !== null || $ogDescription !== null || $ogImageUrl !== null)) {
            throw ValidationException::withMessages([
                'og_title' => 'Social previews are for logged-in users only.',
            ]);
        }

        if ($user === null && $password !== null) {
            throw ValidationException::withMessages([
                'password' => 'Link passwords are for logged-in users only.',
            ]);
        }

        if ($password !== null && mb_strlen($password) < 8) {
            throw ValidationException::withMessages([
                'password' => 'The password must be at least 8 characters.',
            ]);
        }

        $og = $this->socialPreview->normalize($ogTitle, $ogDescription, $ogImageUrl);

        $minLength = $user?->plan?->min_slug_length ?? self::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        // Explicit length choice (dashboard picker) wins over the plan
        // default; clamped defensively so callers can't pass absurdities.
        $generatedLength = $user !== null && ! $user->isAdmin() && $user->plan?->name === 'free'
            ? random_int(self::FREE_MIN_SLUG_LENGTH, self::FREE_MAX_SLUG_LENGTH)
            : ($generatedLength !== null
                ? max(1, min(64, $generatedLength))
                : ($user === null ? self::GUEST_SLUG_LENGTH : $minLength));

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

        // An API key only ever attributes links of its own owner —
        // a programming error passing a foreign key degrades to
        // unattributed (NULL) instead of corrupt cross-user data.
        $attributedKeyId = $apiKey !== null && $user !== null && $apiKey->user_id === $user->id
            ? $apiKey->getKey()
            : null;

        $link = Link::create([
            'slug' => $slug,
            'destination_url' => $destinationUrl,
            'description' => $description !== null && trim($description) !== '' ? mb_substr(trim($description), 0, 500) : null,
            'og_title' => $og['og_title'],
            'og_description' => $og['og_description'],
            'og_image_url' => $og['og_image_url'],
            'tags' => ($normalizedTags = self::normalizeTags($tags)) !== [] ? $normalizedTags : null,
            'domain_id' => $domain->id,
            'user_id' => $user?->id,
            'api_key_id' => $attributedKeyId,
            'creator_ip_hash' => $creatorIpHash,
            'creator_ip_encrypted' => IpCapture::enabled() ? $creatorIp : null,
            'is_active' => true,
            'expires_at' => $expiresAt,
            'password_hash' => $password !== null ? Hash::make($password) : null,
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

        // First-write-wins against bio sub-pages on the same domain.
        if (BioPage::where('domain_id', $domainId)->whereNotNull('parent_id')->where('slug', strtolower($slug))->where('is_removed', false)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'Slug taken by a bio sub-page on this domain.',
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
     * Hot slugs are cached for RESOLVE_CACHE_TTL seconds. The cache
     * holds plain attribute rows (rehydrated on read) because cached
     * models unserialize as __PHP_Incomplete_Class under
     * cache.serializable_classes=false. Misses are never cached so a
     * freshly created slug is visible immediately. A stale hit
     * (deactivated/expired while cached) falls through to the database
     * instead of serving the wrong redirect.
     */
    public function resolveSlug(string $slug, Domain $domain): ?Link
    {
        $key = Link::cacheKey($domain->id, $slug);
        $cached = Cache::get($key);

        if (is_array($cached)
            && ($cached['domain_id'] ?? null) === $domain->id
            && ($cached['slug'] ?? null) === $slug
        ) {
            $link = Link::hydrate([$cached])->first();

            if ($link && $link->isAccessible()) {
                return $link;
            }

            Cache::forget($key);
        }

        $link = Link::accessible()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();

        if ($link) {
            Cache::put($key, Arr::except($link->getAttributes(), ['creator_ip_encrypted', 'creator_ip_hash']), self::RESOLVE_CACHE_TTL);
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
     *
     * @throws JunkUrlException When the new destination is scanner junk.
     * @throws UnsafeUrlException When the new destination targets intranet or non-public IPs.
     */
    public function update(Link $link, array $data, ?User $actor = null): Link
    {
        if (isset($data['destination_url'])) {
            $data['destination_url'] = trim((string) $data['destination_url']);
            $actingUser = $actor ?? $link->user;
            if ($actingUser === null || ! $actingUser->isAdmin()) {
                $this->unsafeUrls->rejectIfUnsafe($data['destination_url']);
            }
            $this->junkUrls->rejectIfJunk($data['destination_url']);
        }

        if (array_key_exists('og_title', $data) || array_key_exists('og_description', $data) || array_key_exists('og_image_url', $data)) {
            $og = $this->socialPreview->normalize(
                array_key_exists('og_title', $data) ? $data['og_title'] : $link->og_title,
                array_key_exists('og_description', $data) ? $data['og_description'] : $link->og_description,
                array_key_exists('og_image_url', $data) ? $data['og_image_url'] : $link->og_image_url,
            );
            $data['og_title'] = $og['og_title'];
            $data['og_description'] = $og['og_description'];
            $data['og_image_url'] = $og['og_image_url'];
        }

        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] !== null && trim((string) $data['description']) !== ''
                ? mb_substr(trim((string) $data['description']), 0, 500)
                : null;
        }

        if (array_key_exists('tags', $data)) {
            $normalized = self::normalizeTags($data['tags']);
            $data['tags'] = $normalized !== [] ? $normalized : null;
        }

        if (array_key_exists('password', $data) && $data['password'] !== null) {
            $this->setPassword($link->fresh() ?? $link, (string) $data['password']);
        }

        if (! empty($data['remove_password'])) {
            $this->clearPassword($link->fresh() ?? $link);
        }

        unset($data['password'], $data['remove_password']);

        $link->update($data);
        Link::forgetCachedSlug($link->domain_id, $link->slug);

        return $link->fresh();
    }

    public static function sessionKey(string $linkId): string
    {
        return "link_unlocked_{$linkId}";
    }

    /**
     * Set (or rotate) a link password. Minimum 8 chars; bcrypt hash only.
     *
     * @throws ValidationException
     */
    public function setPassword(Link $link, string $password): void
    {
        if (mb_strlen($password) < 8) {
            throw ValidationException::withMessages(['password' => 'The password must be at least 8 characters.']);
        }

        $link->update(['password_hash' => Hash::make($password)]);
        Link::forgetCachedSlug($link->domain_id, $link->slug);
    }

    public function clearPassword(Link $link): void
    {
        $link->update(['password_hash' => null]);
        Link::forgetCachedSlug($link->domain_id, $link->slug);
    }

    /**
     * Replace a link's targeting rules (full replace; [] clears).
     *
     * @param  list<array{label?: ?string, destination_url: string, country_codes?: ?array, device?: ?string, weight?: int, sort_order?: int, is_active?: bool}>  $targets
     */
    public function syncTargets(Link $link, array $targets, ?User $actor = null): void
    {
        if (count($targets) > LinkTarget::MAX_PER_LINK) {
            throw ValidationException::withMessages([
                'targets' => 'At most '.LinkTarget::MAX_PER_LINK.' destinations per link.',
            ]);
        }

        $existing = $link->targets()->get()->keyBy('id');

        $rows = [];
        $seenIds = [];
        foreach (array_values($targets) as $i => $t) {
            $id = $t['id'] ?? null;
            if ($id !== null) {
                if (! $existing->has($id)) {
                    throw ValidationException::withMessages(['targets' => "Row {$i}: unknown target."]);
                }
                if (in_array($id, $seenIds, true)) {
                    throw ValidationException::withMessages(['targets' => "Row {$i}: duplicate target."]);
                }
                $seenIds[] = $id;
            }
            $url = trim((string) ($t['destination_url'] ?? ''));
            if ($url === '' || strlen($url) > 2048) {
                throw ValidationException::withMessages(['targets' => "Row {$i}: destination_url is required (max 2048)."]);
            }
            $actingUser = $actor ?? $link->user;
            if ($actingUser === null || ! $actingUser->isAdmin()) {
                $this->unsafeUrls->rejectIfUnsafe($url);
            }
            $this->junkUrls->rejectIfJunk($url);

            $codes = $t['country_codes'] ?? null;
            if ($codes !== null) {
                $codes = array_values(array_unique(array_map(fn ($c) => strtoupper(trim((string) $c)), (array) $codes)));
                $codes = array_filter($codes, fn ($c) => $c !== '');
                if (count($codes) > 50) {
                    throw ValidationException::withMessages(['targets' => "Row {$i}: at most 50 country codes."]);
                }
                foreach ($codes as $c) {
                    if (! preg_match('/^[A-Z]{2}$/', $c)) {
                        throw ValidationException::withMessages(['targets' => "Row {$i}: invalid country code '{$c}'."]);
                    }
                }
                $codes = $codes === [] ? null : $codes;
            }

            $device = $t['device'] ?? null;
            $device = $device !== null && trim((string) $device) !== '' ? strtolower(trim((string) $device)) : null;
            if ($device !== null && ! in_array($device, LinkTarget::DEVICES, true)) {
                throw ValidationException::withMessages(['targets' => "Row {$i}: device must be desktop, mobile or tablet."]);
            }

            $weight = isset($t['weight']) ? (int) $t['weight'] : 100;
            if ($weight < 0 || $weight > 10000) {
                throw ValidationException::withMessages(['targets' => "Row {$i}: weight must be 0–10000."]);
            }

            $rows[] = [
                'id' => $id,
                'label' => isset($t['label']) && trim((string) $t['label']) !== '' ? mb_substr(trim((string) $t['label']), 0, 60) : null,
                'destination_url' => $url,
                'country_codes' => $codes,
                'device' => $device,
                'weight' => $weight,
                'sort_order' => isset($t['sort_order']) ? max(0, min(255, (int) $t['sort_order'])) : $i,
                'is_active' => array_key_exists('is_active', $t) ? (bool) $t['is_active'] : true,
            ];
        }

        \DB::transaction(function () use ($link, $rows, $seenIds) {
            // Identity-preserving replace: known ids update in place
            // (per-target click counts survive), the rest is created,
            // and anything missing from the payload is dropped.
            $link->targets()->whereNotIn('id', $seenIds)->delete();
            foreach ($rows as $row) {
                $id = $row['id'];
                unset($row['id']);

                if ($id !== null) {
                    $link->targets()->where('id', $id)->update($row);
                } else {
                    $link->targets()->create($row);
                }
            }
            TargetSelector::forgetCached($link->id);
            Link::forgetCachedSlug($link->domain_id, $link->slug);
        });
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
