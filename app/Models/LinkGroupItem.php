<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkGroupItem extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'link_group_id',
        'link_id',
        'sort_order',
        'created_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LinkGroupItem $item) {
            if (empty($item->created_at)) {
                $item->created_at = now();
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LinkGroup::class, 'link_group_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
