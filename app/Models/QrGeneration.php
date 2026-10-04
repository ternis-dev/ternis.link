<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class QrGeneration extends Model
{
    use HasFactory, HasUlids;

    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'link_id',
        'format',
        'created_at',
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
        static::creating(function (QrGeneration $generation) {
            $generation->created_at = $generation->created_at ?? now();
        });
        static::created(function () {
            Cache::forget('stats:overview');
            Cache::forget('stats:qr-30d');
        });
    }
}
