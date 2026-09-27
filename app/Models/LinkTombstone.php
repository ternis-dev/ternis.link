<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkTombstone extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'domain_id',
        'domain_hostname',
        'created_day',
        'click_count',
        'clicks_by_day',
    ];

    protected $casts = [
        'created_day' => 'date',
        'click_count' => 'integer',
        'clicks_by_day' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Snapshot the stats-relevant aggregates of a link before it is
     * hard-deleted. Click details stay out — only per-day counts.
     */
    public static function snapshot(Link $link): self
    {
        $clicksByDay = $link->clicks()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date')
            ->map(fn ($count) => (int) $count)
            ->all();

        return self::create([
            'domain_id' => $link->domain_id,
            'domain_hostname' => $link->domain?->hostname ?? 'unknown',
            'created_day' => $link->created_at->format('Y-m-d'),
            'click_count' => (int) $link->click_count,
            'clicks_by_day' => $clicksByDay !== [] ? $clicksByDay : null,
        ]);
    }
}
