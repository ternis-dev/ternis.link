<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\DomainUrls;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

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
        'public_dashboard_legacy',
        'notify_security_email',
        'notify_admin_security_email',
        'notify_server_error_email',
        'table_columns',
        'default_domain_id',
        'domain_order',
        'deletion_requested_at',
    ];

    public const NAV_LAYOUTS = ['side', 'top'];

    public const THEMES = ['system', 'light', 'dark'];

    public function usesTopNav(): bool
    {
        return $this->nav_layout === 'top';
    }

    protected $casts = [
        'role' => UserRole::class,
        'public_dashboard_legacy' => 'boolean',
        'notify_security_email' => 'boolean',
        'notify_admin_security_email' => 'boolean',
        'notify_server_error_email' => 'boolean',
        'table_columns' => 'array',
        'domain_order' => 'array',
        'deletion_requested_at' => 'datetime',
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

    public function defaultDomain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'default_domain_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function bulkOperations(): HasMany
    {
        return $this->hasMany(BulkOperation::class);
    }

    public function privacyExports(): HasMany
    {
        return $this->hasMany(PrivacyExport::class);
    }

    public function bioPages(): HasMany
    {
        return $this->hasMany(BioPage::class);
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
     * Falls back to the Ternis avatar CDN (user.t-cdn.de) which never
     * returns broken images (HTTP 200 fallback guarantee).
     */
    public function avatarUrl(int $size = 64): string
    {
        if (! empty($this->avatar_url)) {
            return $this->avatar_url;
        }

        $base = config('services.ternis_auth.avatar_base', 'https://user.t-cdn.de');

        return "{$base}/{$this->sso_sub}.png?size={$size}";
    }

    /**
     * Get the domains available to this user, optionally scoped to 'public',
     * ordered according to the user's domain_order preference (with unlisted
     * domains sorted naturally after).
     *
     * @return Collection<int, Domain>
     */
    public function availableDomains(?string $scope = null): Collection
    {
        $publicHosts = DomainUrls::publicDashboardHostnames();

        $query = Domain::where('is_active', true)
            ->when($scope === 'public', fn ($q) => $q->whereIn('hostname', $publicHosts))
            ->where(function ($query) {
                $query->whereNull('domains.user_id');

                if ($this->isAdmin()) {
                    $query->orWhereNotNull('domains.verified_at');
                } else {
                    $query->orWhere(function ($q) {
                        $q->where('domains.user_id', $this->id)
                            ->whereNotNull('domains.verified_at');
                    });
                }
            })
            ->when(! $this->isAdmin(), function ($query) {
                $query->where(function ($q) {
                    $q->where('type', 'public');
                    $q->orWhere('user_id', $this->id);

                    if ($this->isFamily() || $this->isAdmin()) {
                        $q->orWhere('type', 'ternis');
                    }

                    if ($this->isAdmin()) {
                        $q->orWhere('type', 'business');
                    }
                });
            });

        return $this->sortDomains($query->get());
    }

    /**
     * Sort a collection/iterable of Domain models according to this user's
     * domain_order preference, falling back to natural alphabetical by hostname.
     *
     * @param  iterable<Domain>  $domains
     * @return Collection<int, Domain>
     */
    public function sortDomains(iterable $domains): Collection
    {
        $order = (array) ($this->domain_order ?? []);
        if (empty($order)) {
            return collect($domains)->sortBy('hostname', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        $orderMap = [];
        foreach ($order as $idx => $identifier) {
            $orderMap[(string) $identifier] = $idx;
        }

        return collect($domains)->sort(function ($a, $b) use ($orderMap) {
            $posA = $orderMap[(string) $a->id] ?? $orderMap[(string) $a->hostname] ?? null;
            $posB = $orderMap[(string) $b->id] ?? $orderMap[(string) $b->hostname] ?? null;

            if ($posA !== null && $posB !== null) {
                return $posA <=> $posB;
            }
            if ($posA !== null) {
                return -1;
            }
            if ($posB !== null) {
                return 1;
            }

            return strnatcasecmp($a->hostname, $b->hostname);
        })->values();
    }

    /**
     * Resolve the effective default domain for this user under the given scope.
     */
    public function resolvedDefaultDomain(?string $scope = null): ?Domain
    {
        $available = $this->availableDomains($scope);

        if ($this->default_domain_id) {
            $default = $available->firstWhere('id', $this->default_domain_id);
            if ($default) {
                return $default;
            }
        }

        return $available->first();
    }
}
