<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Link extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'slug',
        'destination_url',
        'description',
        'og_title',
        'og_description',
        'og_image_url',
        'tags',
        'domain_id',
        'user_id',
        'api_key_id',
        'creator_ip_hash',
        'creator_ip_encrypted',
        'password_hash',
        'bot_protection',
        'allow_bots',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'user_tracking_enabled',
        'click_count',
        'is_active',
        'is_removed',
        'expires_at',
        'health_check_enabled',
        'last_health_check_at',
        'health_status',
        'health_status_code',
        'health_response_time_ms',
        'health_error',
    ];

    protected $hidden = ['password_hash', 'creator_ip_encrypted'];

    protected $casts = [
        'tags' => 'array',
        'user_tracking_enabled' => 'boolean',
        'creator_ip_encrypted' => 'encrypted',
        'bot_protection' => 'boolean',
        'allow_bots' => 'boolean',
        'is_active' => 'boolean',
        'is_removed' => 'boolean',
        'expires_at' => 'datetime',
        'click_count' => 'integer',
        'health_check_enabled' => 'boolean',
        'last_health_check_at' => 'datetime',
        'health_status_code' => 'integer',
        'health_response_time_ms' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(LinkTarget::class)->orderBy('sort_order');
    }

    public function threatEvaluations(): HasMany
    {
        return $this->hasMany(ThreatEvaluation::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(LinkGroup::class, 'link_group_items')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function hasTargeting(): bool
    {
        return $this->targets()->where('is_active', true)->exists();
    }

    public function hasBotProtection(): bool
    {
        return (bool) $this->bot_protection;
    }

    public function allowsBots(): bool
    {
        return (bool) $this->allow_bots;
    }

    public function spamAppeals(): HasMany
    {
        return $this->hasMany(SpamAppeal::class);
    }

    /**
     * Cache key for a hot slug lookup (scoped per domain).
     */
    public static function cacheKey(string $domainId, string $slug): string
    {
        return "link:{$domainId}:{$slug}";
    }

    public static function forgetCachedSlug(string $domainId, string $slug): void
    {
        Cache::forget(self::cacheKey($domainId, $slug));
    }

    /**
     * Keep the redirect cache coherent for every model-based write.
     * Query-builder writes (bulk cleanup) invalidate explicitly in
     * their own command/service — see DeactivateExpiredLinks.
     */
    protected static function booted(): void
    {
        static::updated(function (Link $link) {
            $originalSlug = $link->getOriginal('slug');
            $originalDomainId = $link->getOriginal('domain_id');

            if (is_string($originalSlug) && $originalDomainId !== null) {
                self::forgetCachedSlug((string) $originalDomainId, $originalSlug);
            }

            self::forgetCachedSlug($link->domain_id, $link->slug);
        });

        static::deleted(function (Link $link) {
            self::forgetCachedSlug($link->domain_id, $link->slug);
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->is_active && ! $this->is_removed && ! $this->isExpired();
    }

    public function isHealthCheckEnabled(): bool
    {
        return (bool) $this->health_check_enabled;
    }

    public function isHealthy(): bool
    {
        return $this->health_status === 'healthy';
    }

    public function isDegraded(): bool
    {
        return $this->health_status === 'degraded';
    }

    public function isUnreachable(): bool
    {
        return $this->health_status === 'unreachable';
    }

    public function healthBadgeClass(): string
    {
        return match ($this->health_status) {
            'healthy' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
            'degraded' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
            'unreachable' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
            default => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
        };
    }

    public function healthLabel(): string
    {
        if (! $this->health_check_enabled) {
            return 'Disabled';
        }

        if ($this->health_status === null) {
            return 'Pending check';
        }

        return match ($this->health_status) {
            'healthy' => 'Healthy',
            'degraded' => 'Degraded',
            'unreachable' => 'Unreachable',
            default => ucfirst((string) $this->health_status),
        };
    }

    public function hasSocialPreview(): bool
    {
        return $this->og_title !== null
            || $this->og_description !== null
            || $this->og_image_url !== null;
    }

    /**
     * Effective OG values for crawlers / preview cards.
     *
     * @return array{title: string, description: ?string, image: ?string}
     */
    public function effectiveSocialPreview(): array
    {
        $host = (string) parse_url((string) $this->destination_url, PHP_URL_HOST);
        $fallback = $host !== '' ? $host : $this->slug;

        return [
            'title' => $this->og_title ?? $this->description ?? $fallback,
            'description' => $this->og_description,
            'image' => $this->og_image_url,
        ];
    }

    /**
     * Get the full short URL for this link (e.g. https://href.nz/myslug).
     */
    public function getShortUrlAttribute(): string
    {
        $host = $this->domain?->hostname ?? config('domains.public_host', 'href.nz');

        try {
            $scheme = app()->runningInConsole()
                ? parse_url((string) config('app.url'), PHP_URL_SCHEME)
                : request()->getScheme();
        } catch (\Throwable) {
            $scheme = null;
        }

        $scheme = is_string($scheme) && $scheme !== '' ? $scheme : 'https';

        return "{$scheme}://{$host}/{$this->slug}";
    }

    /**
     * Increment click count atomically (denormalized counter).
     */
    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    /**
     * Scope: only active, non-expired, non-removed links.
     */
    public function scopeAccessible(Builder $query): Builder
    {
        return $query
            ->notRemoved()
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: exclude admin-removed links (dashboards, API).
     */
    public function scopeNotRemoved(Builder $query): Builder
    {
        return $query->where('is_removed', false);
    }

    /**
     * Scope: only links created with the given API key (null =
     * dashboard-created, i.e. no key involved).
     */
    public function scopeForApiKey(Builder $query, ?string $apiKeyId): Builder
    {
        return $apiKeyId === null
            ? $query->whereNull('api_key_id')
            : $query->where('api_key_id', $apiKeyId);
    }

    /**
     * Scope: links visible on the main dashboard list — everything
     * except links made with a key that opted out via
     * `show_on_dashboard = false` (those live on their per-key page).
     */
    public function scopeVisibleOnDashboard(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('api_key_id')
                ->orWhereHas('apiKey', fn (Builder $keys) => $keys->where('show_on_dashboard', true));
        });
    }
}
