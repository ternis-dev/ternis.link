<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    public const KINDS = ['view', 'tap'];

    protected $fillable = [
        'bio_page_id',
        'bio_button_id',
        'kind',
        'referrer',
        'user_agent',
        'ip_hash',
        'country_code',
        'city',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(BioPage::class, 'bio_page_id');
    }

    public function button(): BelongsTo
    {
        return $this->belongsTo(BioButton::class, 'bio_button_id');
    }

    protected static function booted(): void
    {
        static::creating(function (BioEvent $event) {
            $event->created_at = $event->created_at ?? now();
        });
    }
}
