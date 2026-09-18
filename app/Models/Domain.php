<?php

namespace App\Models;

use App\Enums\DomainType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostname',
        'user_id',
        'type',
        'is_active',
        'verified_at',
    ];

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
}
