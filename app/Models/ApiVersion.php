<?php

namespace App\Models;

use App\Enums\ApiVersionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'version';

    public $incrementing = false;

    protected $fillable = [
        'version',
        'status',
        'deprecated_at',
        'changelog',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'status' => ApiVersionStatus::class,
        'deprecated_at' => 'date',
        'created_at' => 'datetime',
    ];

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'api_version', 'version');
    }

    public function isActive(): bool
    {
        return $this->status === ApiVersionStatus::Active;
    }

    /**
     * Get the latest active API version number.
     */
    public static function latestVersion(): int
    {
        return static::where('status', ApiVersionStatus::Active)
            ->max('version') ?? 1;
    }
}
