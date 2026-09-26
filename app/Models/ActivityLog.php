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

    public const API_KEY_REVOKED = 'api_key.revoked';

    public const AUTH_LOGIN = 'auth.login';

    public const AUTH_LOGOUT = 'auth.logout';

    public const ADMIN_LINK_DEACTIVATED = 'admin.link.deactivated';

    public const ADMIN_LINK_REACTIVATED = 'admin.link.reactivated';

    public const ADMIN_LINK_REMOVED = 'admin.link.removed';

    public const ADMIN_LINK_RESTORED = 'admin.link.restored';

    public const ADMIN_DOMAIN_DEACTIVATED = 'admin.domain.deactivated';

    public const ADMIN_DOMAIN_REACTIVATED = 'admin.domain.reactivated';

    public const ADMIN_USER_ROLE_CHANGED = 'admin.user.role_changed';

    public const ADMIN_USER_PLAN_CHANGED = 'admin.user.plan_changed';

    public const SYSTEM_EXPIRED_LINKS_DEACTIVATED = 'system.expired_links_deactivated';

    public const SYSTEM_JUNK_LINKS_PURGED = 'system.junk_links_purged';

    /**
     * Append-only audit rows: created_at is set by the database,
     * updated_at does not exist.
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

    /**
     * Everything visible to a user: actions they performed plus
     * actions others (admins) performed on their stuff.
     */
    public function scopeVisibleTo($query, string $userId)
    {
        return $query->where(
            fn ($q) => $q->where('actor_id', $userId)->orWhere('subject_owner_id', $userId)
        );
    }
}
