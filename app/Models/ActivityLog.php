<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasUlids;

    public const LINK_CREATED = 'link.created';

    public const LINK_UPDATED = 'link.updated';

    public const LINK_DEACTIVATED = 'link.deactivated';

    public const DOMAIN_REGISTERED = 'domain.registered';

    public const DOMAIN_CLAIMED = 'domain.claimed';

    public const DOMAIN_VERIFIED = 'domain.verified';

    public const DOMAIN_DEACTIVATED = 'domain.deactivated';

    public const API_KEY_CREATED = 'api_key.created';

    public const API_KEY_UPDATED = 'api_key.updated';

    public const API_KEY_REVOKED = 'api_key.revoked';

    public const AUTH_LOGIN = 'auth.login';

    public const AUTH_LOGOUT = 'auth.logout';

    public const AUTH_TOKEN_ISSUED = 'auth.token_issued';

    public const AUTH_TOKEN_REVOKED = 'auth.token_revoked';

    public const ADMIN_LINK_DEACTIVATED = 'admin.link.deactivated';

    public const ADMIN_LINK_REACTIVATED = 'admin.link.reactivated';

    public const ADMIN_LINK_REMOVED = 'admin.link.removed';

    public const ADMIN_LINK_RESTORED = 'admin.link.restored';

    public const ADMIN_LINK_DELETED = 'admin.link.deleted';

    public const ADMIN_BIO_DEACTIVATED = 'admin.bio.deactivated';

    public const ADMIN_BIO_REACTIVATED = 'admin.bio.reactivated';

    public const ADMIN_BIO_REMOVED = 'admin.bio.removed';

    public const ADMIN_BIO_RESTORED = 'admin.bio.restored';

    public const ADMIN_DOMAIN_DEACTIVATED = 'admin.domain.deactivated';

    public const ADMIN_DOMAIN_REACTIVATED = 'admin.domain.reactivated';

    public const ADMIN_USER_ROLE_CHANGED = 'admin.user.role_changed';

    public const ADMIN_USER_PLAN_CHANGED = 'admin.user.plan_changed';

    public const ADMIN_ALLOWED_CLIENT_CREATED = 'admin.allowed_client.created';

    public const ADMIN_ALLOWED_CLIENT_UPDATED = 'admin.allowed_client.updated';

    public const ADMIN_ALLOWED_CLIENT_DELETED = 'admin.allowed_client.deleted';

    public const SYSTEM_EXPIRED_LINKS_DEACTIVATED = 'system.expired_links_deactivated';

    public const SYSTEM_JUNK_LINKS_PURGED = 'system.junk_links_purged';

    /**
     * Append-only audit rows: created_at is set by the database,
     * updated_at does not exist.
     *
     * Deliberately NO retention window: activity history is kept
     * indefinitely (product decision — the trail must never be
     * deleted, not even after decades). There is intentionally no
     * prune command for this table, so no schedule or operator can
     * wipe it by accident. Note the GDPR tension: indefinite storage
     * relies on legitimate interest (Art. 6(1)(f) — security, abuse
     * prevention, user transparency); keep the balancing assessment
     * on file and honor erasure requests (Art. 17) individually.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_owner_id',
        'subject_label',
        'metadata',
        'ip_hash',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'subject_type', 'subject_id');
    }

    public function subjectOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_owner_id');
    }

    public function scopeForActor($query, ?string $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function isViaApi(): bool
    {
        return ($this->metadata['via'] ?? null) === 'api'
            || ! empty($this->metadata['api_key_id'])
            || in_array($this->metadata['auth_via'] ?? null, ['api-key', 'session-token'], true);
    }

    public function tokenLabel(): ?string
    {
        if (! empty($this->metadata['api_key_name'])) {
            $prefix = ! empty($this->metadata['api_key_prefix']) ? " ({$this->metadata['api_key_prefix']}…)" : '';

            return $this->metadata['api_key_name'].$prefix;
        }

        if (! empty($this->metadata['api_key_prefix'])) {
            return "API Key ({$this->metadata['api_key_prefix']}…)";
        }

        if (($this->metadata['auth_via'] ?? null) === 'api-key') {
            return 'API Key';
        }

        if (! empty($this->metadata['session_token_prefix'])) {
            return "Session Token ({$this->metadata['session_token_prefix']}…)";
        }

        if (($this->metadata['auth_via'] ?? null) === 'session-token') {
            return 'Session Token';
        }

        if (($this->metadata['via'] ?? null) === 'api') {
            return 'API Token';
        }

        return null;
    }

    /**
     * Everything visible to a user: actions they performed, actions
     * performed by their API tokens, plus actions others (admins)
     * performed on their stuff.
     */
    public function scopeVisibleTo($query, string|User $user)
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where(function ($q) use ($userId) {
            $q->where('actor_id', $userId)
                ->orWhere('subject_owner_id', $userId)
                ->orWhereIn('metadata->api_key_id', function ($sub) use ($userId) {
                    $sub->select('id')->from('api_keys')->where('user_id', $userId);
                });
        });
    }
}
