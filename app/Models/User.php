<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'sso_sub',
        'name',
        'email',
        'avatar_url',
        'sso_user_type',
        'role',
        'plan_id',
        'nav_layout',
        'theme',
        'notify_security_email',
        'notify_admin_security_email',
        'notify_server_error_email',
        'table_columns',
    ];

    public const NAV_LAYOUTS = ['side', 'top'];

    public const THEMES = ['system', 'light', 'dark'];

    public function usesTopNav(): bool
    {
        return $this->nav_layout === 'top';
    }

    protected $casts = [
        'role' => UserRole::class,
        'notify_security_email' => 'boolean',
        'notify_admin_security_email' => 'boolean',
        'notify_server_error_email' => 'boolean',
        'table_columns' => 'array',
    ];

    // No password, no remember_token — SSO only

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function setRememberToken($value): void
    {
        // No-op for SSO
    }

    public function oauthIdentity(): HasOne
    {
        return $this->hasOne(OAuthIdentity::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isPartner(): bool
    {
        return $this->role === UserRole::Partner;
    }

    public function isFamily(): bool
    {
        return $this->role === UserRole::Family;
    }

    /**
     * May this user pick the auto-generated slug length on the link
     * form? Admins always; everyone else needs it on their plan.
     */
    public function canChooseSlugLength(): bool
    {
        return $this->isAdmin() || (bool) $this->plan?->allowsSlugLengthChoice();
    }

    /**
     * May this user claim a personal {name}.ternis.link subdomain?
     * Inner circle only: admins, family and partners.
     */
    public function canClaimSubdomain(): bool
    {
        return $this->isAdmin() || $this->isFamily() || $this->isPartner();
    }

    /**
     * Get the avatar URL with optional size parameter.
     * Falls back to user.t-api.de which never returns broken images.
     */
    public function avatarUrl(int $size = 64): string
    {
        if (! empty($this->avatar_url)) {
            return $this->avatar_url;
        }

        $base = config('services.ternis_auth.avatar_base', 'https://user.t-api.de');

        return "{$base}/{$this->sso_sub}.png?size={$size}";
    }
}
