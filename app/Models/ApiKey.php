<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key_hash',
        'key_prefix',
        'api_version',
        'name',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'api_version' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiVersion(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version', 'version');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Display key as prefix with mask: "tl_abc1****"
     */
    public function getMaskedKeyAttribute(): string
    {
        return $this->key_prefix.'****';
    }
}
