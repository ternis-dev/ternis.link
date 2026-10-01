<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BioPage extends Model
{
    use HasUlids;

    public const THEMES = ['minimal', 'dark', 'paper'];

    public const LOCALES = ['en', 'de', 'fr', 'es', 'it'];

    protected $fillable = [
        'user_id',
        'domain_id',
        'parent_id',
        'slug',
        'title',
        'bio',
        'avatar_url',
        'theme',
        'locale',
        'accent',
        'theme_color',
        'og_title',
        'og_description',
        'og_image_url',
        'is_active',
        'is_removed',
        'published_at',
        'sort_order',
        'view_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_removed' => 'boolean',
        'published_at' => 'datetime',
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

        return true;
    }

    public static function cacheKeyRoot(string $domainId): string
    {
        return "bio:{$domainId}:root";
    }

    public static function cacheKeyPage(string $pageId): string
    {
        return "bio:{$pageId}";
    }
}
