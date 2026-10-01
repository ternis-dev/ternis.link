<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BioButton extends Model
{
    use HasUlids;

    public const KINDS = ['link', 'header', 'divider', 'social'];

    public const ACTIONS = ['url', 'subpage', 'modal'];

    public const ICONS = ['instagram', 'tiktok', 'x', 'youtube', 'github', 'globe', 'mail', 'link'];

    protected $fillable = [
        'bio_page_id',
        'label',
        'sublabel',
        'kind',
        'action',
        'destination_url',
        'target_page_id',
        'modal_title',
        'modal_body',
        'modal_image_url',
        'icon',
        'thumbnail_url',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
        'tap_count',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'tap_count' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(BioPage::class, 'bio_page_id');
    }

    public function targetPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class, 'target_page_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BioEvent::class);
    }

    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
