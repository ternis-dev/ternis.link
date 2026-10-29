<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreatEvaluation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'link_id',
        'user_id',
        'creator_ip_hash',
        'destination_url',
        'destination_host',
        'user_agent',
        'evaluation_type',
        'verdict',
        'threat_score',
        'confidence',
        'action_taken',
        'reasoning',
        'metadata',
    ];

    protected $casts = [
        'threat_score' => 'integer',
        'confidence' => 'float',
        'metadata' => 'array',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
