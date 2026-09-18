<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'link_id',
        'referrer',
        'user_agent',
        'ip_hash',
        'country_code',
        'city',
        'is_direct_url',
        'created_at',
    ];

    protected $casts = [
        'is_direct_url' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Boot: auto-set created_at since we disabled $timestamps.
     */
    protected static function booted(): void
    {
        static::creating(function (Click $click) {
            $click->created_at = $click->created_at ?? now();
        });
    }
}
