<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Link extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'slug',
        'destination_url',
        'description',
        'tags',
        'domain_id',
        'user_id',
        'api_key_id',
        'creator_ip_hash',
        'creator_ip_encrypted',
        'click_count',
        'is_active',
        'is_removed',
        'expires_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'creator_ip_encrypted' => 'encrypted',
        'is_active' => 'boolean',
        'is_removed' => 'boolean',
        'expires_at' => 'datetime',
        'click_count' => 'integer',
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
