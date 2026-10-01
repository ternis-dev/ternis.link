<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class BioPage extends Model
{
    use HasUlids;

    public const THEMES = ['minimal', 'dark', 'paper'];

    public const LOCALES = ['en', 'de', 'fr', 'es', 'it'];

    public const BUTTON_STYLES = ['filled', 'outline', 'soft'];

    protected $fillable = [
        'user_id',
        'domain_id',
        'parent_id',
        'slug',
        'title',
        'bio',
        'avatar_url',
        'cover_url',
        'footer_text',
        'theme',
        'locale',
        'accent',
        'theme_color',
        'password_hash',
        'button_style',
        'og_title',
        'og_description',
        'og_image_url',
        'is_active',
        'is_removed',
        'published_at',
        'expires_at',
        'sort_order',
        'view_count',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_removed' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'sort_order' => 'integer',
        'view_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(BioButton::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BioEvent::class);
    }

    public function isSubPage(): bool
    {
        return $this->parent_id !== null;
    }

    public function isVisible(): bool
    {
        if (! $this->is_active || $this->is_removed) {
            return false;
        }

        if ($this->published_at !== null && $this->published_at->isFuture()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public static function cacheKeyRoot(string $domainId): string
    {
        return "bio:{$domainId}:root";
    }

    public static function cacheKeyPage(string $pageId): string
    {
        return "bio:{$pageId}";
    }

    /**
     * Keep the public render cache coherent for every model-based
     * write (deactivate, expiry passing via update, settings saves).
     */
    protected static function booted(): void
    {
        static::updated(fn (BioPage $page) => self::flushCaches($page));
        static::deleted(fn (BioPage $page) => self::flushCaches($page));
    }

    public static function flushCaches(BioPage $page): void
    {
        Cache::forget(self::cacheKeyPage($page->id));

        if ($page->domain_id) {
            Cache::forget(self::cacheKeyRoot($page->domain_id));
        }

        if ($page->parent_id) {
            $rootDomainId = self::where('id', $page->parent_id)->value('domain_id');

            if ($rootDomainId) {
                Cache::forget(self::cacheKeyRoot($rootDomainId));
            }
        }
    }
}
