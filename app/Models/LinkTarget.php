<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LinkTarget extends Model
{
    use HasFactory, HasUlids;

    public const DEVICES = ['desktop', 'mobile', 'tablet'];

    public const MAX_PER_LINK = 20;

    protected $fillable = [
        'link_id',
        'label',
        'destination_url',
        'country_codes',
        'device',
        'weight',
        'sort_order',
        'click_count',
        'is_active',
    ];

    protected $casts = [
        'country_codes' => 'array',
        'weight' => 'integer',
        'sort_order' => 'integer',
        'click_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }
}
