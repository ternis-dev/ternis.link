<?php

namespace App\Models;

use App\Enums\DomainType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'hostname',
        'user_id',
        'verification_token',
        'type',
        'is_active',
        'verified_at',
    ];

    protected $hidden = ['verification_token'];

    protected $casts = [
        'type' => DomainType::class,
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function isSystemDomain(): bool
    {
        return $this->user_id === null;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * System domains are implicitly trusted; user-owned domains must
     * be active and DNS-verified before links can be created on them.
     */
    public function isUsableForLinks(): bool
    {
        return $this->is_active && ($this->user_id === null || $this->isVerified());
    }

    public function markVerified(): static
    {
        $this->update(['verified_at' => now()]);

        return $this->fresh();
    }
}
