<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory, HasUlids;

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

    /**
     * Token hashing scheme (single source of truth).
     *
     * Only the SHA-256 hex digest is ever persisted. The raw `tl_…`
     * token is shown to the owner ONCE at creation time and is never
     * stored anywhere — a stored value can therefore never be
     * displayed as a usable key, and a bcrypt-style hash can never
     * validate (see the saving guard below).
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Constant-time check of a presented bearer token against this key.
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->key_hash, self::hashToken($token));
    }

    protected static function booted(): void
    {
        // Never persist anything but a SHA-256 hex digest. This turns
        // a programming error (e.g. storing Hash::make($raw), which
        // would surface a `$2y$…` string in the UI and silently break
        // authentication) into a loud failure instead of corrupt data.
        static::saving(function (ApiKey $key): void {
            if (! preg_match('/^[0-9a-f]{64}$/', (string) $key->key_hash)) {
                throw new \RuntimeException(
                    'ApiKey key_hash must be a SHA-256 hex digest (see ApiKey::hashToken). '.
                    'Refusing to persist a non-conforming hash.'
                );
            }
        });
    }

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
