<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Short-lived access token minted by the CORS-protected auth API of the
 * public dashboard (my.ternis.link / my.href.nz).
 *
 * A cross-origin client exchanges its session for one of these tokens
 * and then talks to the regular REST API with it. Only the SHA-256
 * digest is persisted — the raw `ts_…` value is shown once and cannot
 * be recovered from the database.
 */
class ApiSessionToken extends Model
{
    use HasUlids;

    /** Raw token prefix — lets the API tell session tokens apart. */
    public const PREFIX = 'ts_';

    protected $fillable = [
        'user_id',
        'token_hash',
        'token_prefix',
        'origin',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['token_hash'];

    /**
     * Token hashing scheme — mirrors ApiKey::hashToken so both token
     * types are stored the same way (SHA-256 hex digest).
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Constant-time check of a presented bearer token against this row.
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token_hash, self::hashToken($token));
    }

    protected static function booted(): void
    {
        // Guard against ever persisting something other than a digest.
        static::saving(function (ApiSessionToken $token): void {
            if (! preg_match('/^[0-9a-f]{64}$/', (string) $token->token_hash)) {
                throw new \RuntimeException(
                    'ApiSessionToken token_hash must be a SHA-256 hex digest (see hashToken). '.
                    'Refusing to persist a non-conforming hash.'
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        // Rate-limit the write: a busy client would otherwise cause one
        // UPDATE per API request just to move a timestamp. A minute of
        // granularity is enough to answer "when was this last used?".
        if ($this->last_used_at !== null && $this->last_used_at->gt(now()->subMinute())) {
            return;
        }

        $this->update(['last_used_at' => now()]);
    }

    /**
     * Display token as prefix with mask: "ts_abc1****"
     */
    public function getMaskedTokenAttribute(): string
    {
        return $this->token_prefix.'****';
    }
}
