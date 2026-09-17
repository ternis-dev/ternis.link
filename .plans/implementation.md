# ternis.link — Implementation Specification

> This document provides a complete, buildable specification for the ternis.link Laravel application.
> Read alongside: [Project Plan](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/.plans/20260917T173102_initial-project-plan.md) · [Schema](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/.plans/schema.md)

---

## 1. Project Initialization

```bash
composer create-project laravel/laravel ternis-link
cd ternis-link
```

### 1.1 Required Packages

```bash
composer require livewire/livewire          # Blade + Livewire frontend
# No Passport/Socialite — SSO is implemented manually via Http client
```

### 1.2 Environment Variables (`.env`)

```env
APP_NAME="ternis.link"
APP_URL=https://href.nz

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ternis_link
DB_USERNAME=ternis
DB_PASSWORD=

# Redis (cache + queue)
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis

# Ternis Auth SSO
TERNIS_AUTH_BASE_URL=https://auth.ternis.net
TERNIS_AUTH_CLIENT_ID=
TERNIS_AUTH_CLIENT_SECRET=
TERNIS_AUTH_REDIRECT_URI=https://dash.ternis.link/auth/callback
TERNIS_AUTH_SCOPES="openid profile email ternis:sso ternis:member ternis:customer ternis:partner"

# Avatar CDN
TERNIS_AVATAR_BASE_URL=https://user.t-api.de

# Domain config
DOMAIN_PUBLIC=href.nz
DOMAIN_BUSINESS=href.re
DOMAIN_TERNIS=ternis.link
DOMAIN_DASHBOARD=dash.ternis.link
DOMAIN_ADMIN=admin.ternis.link
DOMAIN_API=links.t-api.de
```

---

## 2. Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── TernisAuthController.php
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       ├── LinkController.php
│   │   │       ├── ClickController.php
│   │   │       └── DomainController.php
│   │   ├── DashboardController.php
│   │   └── RedirectController.php
│   ├── Middleware/
│   │   ├── ResolveDomain.php
│   │   ├── AuthenticateApi.php
│   │   └── EnforceDomainAccess.php
│   └── Requests/
│       ├── StoreLinkRequest.php
│       └── UpdateLinkRequest.php
├── Livewire/
│   ├── Dashboard/
│   │   ├── LinkTable.php
│   │   ├── LinkForm.php
│   │   ├── LinkAnalytics.php
│   │   └── ApiKeyManager.php
│   └── Components/
│       └── UserAvatar.php
├── Models/
│   ├── User.php
│   ├── OAuthIdentity.php
│   ├── Link.php
│   ├── Click.php
│   ├── Domain.php
│   ├── Plan.php
│   ├── ApiKey.php
│   └── ApiVersion.php
├── Services/
│   ├── TernisAuthService.php
│   ├── SlugResolverService.php
│   ├── LinkService.php
│   ├── ClickTrackerService.php
│   └── SlugGeneratorService.php
├── Jobs/
│   └── RecordClick.php
├── Enums/
│   ├── UserRole.php
│   ├── DomainType.php
│   └── ApiVersionStatus.php
└── Providers/
    └── AppServiceProvider.php

config/
├── services.php          # ternis auth config
└── domains.php           # domain → type mapping

database/
├── migrations/
│   ├── 0001_create_plans_table.php
│   ├── 0002_create_users_table.php
│   ├── 0003_create_oauth_identities_table.php
│   ├── 0004_create_domains_table.php
│   ├── 0005_create_links_table.php
│   ├── 0006_create_clicks_table.php
│   ├── 0007_create_api_versions_table.php
│   └── 0008_create_api_keys_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── PlanSeeder.php
    ├── DomainSeeder.php
    └── ApiVersionSeeder.php

resources/views/
├── layouts/
│   ├── app.blade.php           # Base layout (nav, footer)
│   └── dashboard.blade.php     # Dashboard layout (sidebar)
├── auth/
│   └── login.blade.php         # "Login with Ternis Auth" button
├── dashboard/
│   ├── index.blade.php         # Dashboard home (stats overview)
│   ├── links/
│   │   ├── index.blade.php     # Link list (Livewire: LinkTable)
│   │   ├── create.blade.php    # Create link (Livewire: LinkForm)
│   │   └── show.blade.php      # Link detail + analytics (Livewire: LinkAnalytics)
│   └── api-keys/
│       └── index.blade.php     # API key management (Livewire: ApiKeyManager)
├── redirect/
│   └── not-found.blade.php     # 404 page for dead slugs
└── landing/
    └── index.blade.php         # Public landing page (href.nz)

routes/
├── web.php
├── api/
│   └── v1.php
└── channels.php
```

---

## 3. Configuration

### 3.1 `config/services.php` — Ternis Auth

```php
// Add to the existing services.php config array:

'ternis_auth' => [
    'base_url'      => env('TERNIS_AUTH_BASE_URL', 'https://auth.ternis.net'),
    'client_id'     => env('TERNIS_AUTH_CLIENT_ID'),
    'client_secret' => env('TERNIS_AUTH_CLIENT_SECRET'),
    'redirect_uri'  => env('TERNIS_AUTH_REDIRECT_URI'),
    'scopes'        => env('TERNIS_AUTH_SCOPES', 'openid profile email ternis:sso'),
    'avatar_base'   => env('TERNIS_AVATAR_BASE_URL', 'https://user.t-api.de'),
],
```

### 3.2 `config/domains.php` — Domain Registry

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Domain → Type Mapping
    |--------------------------------------------------------------------------
    | Maps incoming hostnames to their domain type for the ResolveDomain
    | middleware. Wildcard subdomains are supported via *.hostname patterns.
    */

    'map' => [
        // Public link shortener
        'href.nz'           => 'public',
        // Business (ternis official)
        'href.re'           => 'business',
        // Ternis family/partners
        'ternis.link'       => 'ternis',
        // thosted internal
        'links.thosted.de'  => 'ternis',
        'short.thosted.de'  => 'ternis',
        'go.thosted.de'     => 'ternis',
        // Go redirects
        'go.ternis.net'     => 'ternis',
        'go.ternis.dev'     => 'ternis',
        'go.ternis.org'     => 'ternis',
        'go.ternis.eu'      => 'ternis',
        // Special-purpose
        'links.t-api.de'    => 'api',
        'dash.ternis.link'  => 'dashboard',
        'admin.ternis.link' => 'admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard Subdomain Domains
    |--------------------------------------------------------------------------
    | These root domains allow *.root subdomains to be looked up in the
    | domains table for partner/family custom subdomains.
    */

    'wildcard_roots' => [
        'ternis.link',
        'href.re',
        'href.nz',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Type → Required Auth
    |--------------------------------------------------------------------------
    */

    'auth_required' => [
        'ternis'    => true,   // family/partner auth required
        'business'  => true,   // business auth required
        'public'    => false,  // anyone can access
        'api'       => false,  // per-route auth
        'dashboard' => true,   // SSO login required
        'admin'     => true,   // admin role required
    ],
];
```

---

## 4. Enums

### 4.1 `app/Enums/UserRole.php`

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'admin';
    case Partner = 'partner';
    case Family  = 'family';
    case User    = 'user';
}
```

### 4.2 `app/Enums/DomainType.php`

```php
<?php

namespace App\Enums;

enum DomainType: string
{
    case Ternis   = 'ternis';
    case Business = 'business';
    case Public   = 'public';
    case Partner  = 'partner';
}
```

### 4.3 `app/Enums/ApiVersionStatus.php`

```php
<?php

namespace App\Enums;

enum ApiVersionStatus: string
{
    case Active     = 'active';
    case Deprecated = 'deprecated';
    case Retired    = 'retired';
}
```

---

## 5. Models

### 5.1 `app/Models/User.php`

```php
<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    protected $fillable = [
        'sso_sub',
        'name',
        'email',
        'avatar_url',
        'sso_user_type',
        'role',
        'plan_id',
    ];

    protected $casts = [
        'role' => UserRole::class,
    ];

    // No password, no remember_token — SSO only

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
     * Get the avatar URL with optional size parameter.
     * Falls back to user.t-api.de which never returns broken images.
     */
    public function avatarUrl(int $size = 64): string
    {
        $base = config('services.ternis_auth.avatar_base', 'https://user.t-api.de');

        return "{$base}/{$this->sso_sub}.png?size={$size}";
    }
}
```

### 5.2 `app/Models/OAuthIdentity.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthIdentity extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'sso_claims',
        'claims_synced_at',
    ];

    protected $casts = [
        'access_token'     => 'encrypted',
        'refresh_token'    => 'encrypted',
        'token_expires_at' => 'datetime',
        'sso_claims'       => 'json',
        'claims_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }
}
```

### 5.3 `app/Models/Link.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    protected $fillable = [
        'slug',
        'destination_url',
        'domain_id',
        'user_id',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'expires_at'  => 'datetime',
        'click_count' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Increment click count atomically (denormalized counter).
     */
    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    /**
     * Scope: only active, non-expired links.
     */
    public function scopeAccessible($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
```

### 5.4 `app/Models/Click.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'link_id',
        'referrer',
        'user_agent',
        'ip_hash',
        'country_code',
        'city',
        'is_direct_url',
        'created_at',
    ];

    protected $casts = [
        'is_direct_url' => 'boolean',
        'created_at'    => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Boot: auto-set created_at since we disabled $timestamps.
     */
    protected static function booted(): void
    {
        static::creating(function (Click $click) {
            $click->created_at = $click->created_at ?? now();
        });
    }
}
```

### 5.5 `app/Models/Domain.php`

```php
<?php

namespace App\Models;

use App\Enums\DomainType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'hostname',
        'user_id',
        'type',
        'is_active',
        'verified_at',
    ];

    protected $casts = [
        'type'        => DomainType::class,
        'is_active'   => 'boolean',
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
```

### 5.6 `app/Models/Plan.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'min_slug_length',
        'custom_subdomain',
        'rate_limit_per_minute',
        'max_links_per_day',
    ];

    protected $casts = [
        'custom_subdomain' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allowsCustomSubdomain(): bool
    {
        return $this->custom_subdomain;
    }

    public function hasUnlimitedLinks(): bool
    {
        return $this->max_links_per_day === null;
    }
}
```

### 5.7 `app/Models/ApiKey.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
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
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'revoked_at'   => 'datetime',
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
        return !$this->isRevoked() && !$this->isExpired();
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
        return $this->key_prefix . '****';
    }
}
```

### 5.8 `app/Models/ApiVersion.php`

```php
<?php

namespace App\Models;

use App\Enums\ApiVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiVersion extends Model
{
    protected $primaryKey = 'version';
    public $incrementing = false;

    protected $fillable = [
        'version',
        'status',
        'deprecated_at',
        'changelog',
    ];

    protected $casts = [
        'status'        => ApiVersionStatus::class,
        'deprecated_at' => 'date',
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
```

---

## 6. Services

### 6.1 `app/Services/TernisAuthService.php`

Handles the full OAuth 2.0 / OIDC flow with Ternis Auth.

```php
<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\OAuthIdentity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TernisAuthService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $scopes;

    public function __construct()
    {
        $this->baseUrl      = config('services.ternis_auth.base_url');
        $this->clientId     = config('services.ternis_auth.client_id');
        $this->clientSecret = config('services.ternis_auth.client_secret');
        $this->redirectUri  = config('services.ternis_auth.redirect_uri');
        $this->scopes       = config('services.ternis_auth.scopes');
    }

    /**
     * Generate PKCE code verifier (43-128 char random string).
     */
    public function generateCodeVerifier(): string
    {
        return Str::random(64);
    }

    /**
     * Generate PKCE code challenge (Base64URL-encoded SHA-256 of verifier).
     */
    public function generateCodeChallenge(string $verifier): string
    {
        $hash = hash('sha256', $verifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * Build the authorization URL to redirect the user to Ternis Auth.
     */
    public function getAuthorizationUrl(string $state, string $codeChallenge): string
    {
        $params = http_build_query([
            'response_type'       => 'code',
            'client_id'           => $this->clientId,
            'redirect_uri'        => $this->redirectUri,
            'scope'               => $this->scopes,
            'state'               => $state,
            'code_challenge'      => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->baseUrl}/oauth/authorize?{$params}";
    }

    /**
     * Build a silent SSO check URL (prompt=none).
     * Returns auth code immediately if session exists, or ?error=login_required.
     */
    public function getSilentAuthUrl(string $state, string $codeChallenge): string
    {
        $params = http_build_query([
            'response_type'       => 'code',
            'client_id'           => $this->clientId,
            'redirect_uri'        => $this->redirectUri,
            'scope'               => $this->scopes,
            'state'               => $state,
            'code_challenge'      => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt'              => 'none',
        ]);

        return "{$this->baseUrl}/oauth/authorize?{$params}";
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
            'code_verifier' => $codeVerifier,
        ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Fetch user info from the OIDC userinfo endpoint.
     *
     * @return array Full userinfo claims (sub, name, email, picture, user_type, etc.)
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/oauth/userinfo");

        $response->throw();

        return $response->json();
    }

    /**
     * Find or create a local User from SSO claims, update OAuth tokens.
     * This is the core "SSO callback" logic.
     */
    public function findOrCreateUser(array $tokenData, array $userInfo): User
    {
        $user = User::updateOrCreate(
            ['sso_sub' => $userInfo['sub']],
            [
                'name'          => $userInfo['name'],
                'email'         => $userInfo['email'],
                'avatar_url'    => $userInfo['picture'] ?? null,
                'sso_user_type' => $userInfo['user_type'] ?? null,
                'role'          => $this->mapRole($userInfo),
            ]
        );

        // Assign default plan if new user
        if ($user->wasRecentlyCreated && !$user->plan_id) {
            $user->update(['plan_id' => Plan::where('name', 'free')->first()?->id]);
        }

        // Store/update OAuth tokens
        OAuthIdentity::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token'     => $tokenData['access_token'],
                'refresh_token'    => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'sso_claims'       => $userInfo,
                'claims_synced_at' => now(),
            ]
        );

        return $user->fresh();
    }

    /**
     * Map SSO user_type + claims to a local UserRole.
     */
    private function mapRole(array $userInfo): UserRole
    {
        $userType = $userInfo['user_type'] ?? null;

        // Ternis members: check if admin-level or family
        if ($userType === 'ternis_member') {
            $badge = $userInfo['ternis_member']['member_badge'] ?? null;
            // Core team members with admin badges get admin role
            if (in_array($badge, ['ternis-core', 'ternis-admin'])) {
                return UserRole::Admin;
            }
            return UserRole::Family;
        }

        // Verified partners
        if ($userType === 'partner' || !empty($userInfo['ternis_partner'])) {
            return UserRole::Partner;
        }

        // Everyone else (general users, paying customers)
        return UserRole::User;
    }
}
```

### 6.2 `app/Services/SlugResolverService.php`

Determines whether `href.nz/{input}` is a URL or a slug.

```php
<?php

namespace App\Services;

class SlugResolverService
{
    /**
     * Determine if the input is a URL (should direct-redirect) or a slug (should look up).
     *
     * Rules:
     * - Contains a dot (.), colon (:), or forward slash (/) → URL
     * - Matches slug charset [a-zA-Z0-9_-] only → slug
     *
     * @return 'url'|'slug'
     */
    public function classify(string $input): string
    {
        // If it contains dot, colon, or slash → it's a URL
        if (preg_match('/[.:\/]/', $input)) {
            return 'url';
        }

        // If it only contains valid slug characters → it's a slug
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
            return 'slug';
        }

        // Fallback: treat as URL (handles edge cases like encoded chars)
        return 'url';
    }

    /**
     * Normalize a URL for redirect (ensure it has a scheme).
     */
    public function normalizeUrl(string $url): string
    {
        // If no scheme, prepend https://
        if (!preg_match('#^https?://#i', $url)) {
            return 'https://' . $url;
        }

        return $url;
    }
}
```

### 6.3 `app/Services/SlugGeneratorService.php`

Generates random slugs based on the user's plan.

```php
<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Link;
use Illuminate\Support\Str;

class SlugGeneratorService
{
    /**
     * Valid slug characters: [a-zA-Z0-9_-]
     * No dots, colons, or slashes — this enables deterministic URL vs slug detection.
     */
    private const CHARSET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

    /**
     * Generate a unique slug for the given domain.
     *
     * @param int $length  Slug length (determined by user's plan min_slug_length)
     * @param int $domainId  Domain to check uniqueness against
     */
    public function generate(int $length, int $domainId): string
    {
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $slug = $this->randomSlug($length);

            // Check uniqueness within this domain
            $exists = Link::where('domain_id', $domainId)
                ->where('slug', $slug)
                ->exists();

            if (!$exists) {
                return $slug;
            }
        }

        // If all attempts collide, increase length by 1 and try again
        return $this->generate($length + 1, $domainId);
    }

    private function randomSlug(int $length): string
    {
        $slug = '';
        $charsetLength = strlen(self::CHARSET);

        for ($i = 0; $i < $length; $i++) {
            $slug .= self::CHARSET[random_int(0, $charsetLength - 1)];
        }

        return $slug;
    }
}
```

### 6.4 `app/Services/LinkService.php`

Business logic for link CRUD.

```php
<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;

class LinkService
{
    public function __construct(
        private SlugGeneratorService $slugGenerator,
    ) {}

    /**
     * Create a new short link.
     */
    public function create(
        string $destinationUrl,
        Domain $domain,
        ?User $user = null,
        ?string $customSlug = null,
        ?\DateTimeInterface $expiresAt = null,
    ): Link {
        $minLength = $user?->plan?->min_slug_length ?? 8;

        $slug = $customSlug ?? $this->slugGenerator->generate($minLength, $domain->id);

        return Link::create([
            'slug'            => $slug,
            'destination_url' => $destinationUrl,
            'domain_id'       => $domain->id,
            'user_id'         => $user?->id,
            'is_active'       => true,
            'expires_at'      => $expiresAt,
        ]);
    }

    /**
     * Resolve a slug to a link on the given domain.
     */
    public function resolveSlug(string $slug, Domain $domain): ?Link
    {
        return Link::accessible()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Update a link.
     */
    public function update(Link $link, array $data): Link
    {
        $link->update($data);

        return $link->fresh();
    }

    /**
     * Soft-deactivate a link (don't hard-delete, preserve analytics).
     */
    public function deactivate(Link $link): void
    {
        $link->update(['is_active' => false]);
    }
}
```

### 6.5 `app/Services/ClickTrackerService.php`

Records clicks (dispatches to queue for async processing).

```php
<?php

namespace App\Services;

use App\Jobs\RecordClick;
use App\Models\Link;
use Illuminate\Http\Request;

class ClickTrackerService
{
    /**
     * Track a click on a link (dispatched to queue).
     *
     * @param bool $isDirectUrl  True for href.nz/url/* redirects (admin-only visibility)
     */
    public function track(Link $link, Request $request, bool $isDirectUrl = false): void
    {
        RecordClick::dispatch(
            linkId:     $link->id,
            referrer:   $request->header('Referer'),
            userAgent:  $request->userAgent(),
            ipHash:     hash('sha256', $request->ip()),
            isDirectUrl: $isDirectUrl,
        );
    }
}
```

---

## 7. Jobs

### 7.1 `app/Jobs/RecordClick.php`

```php
<?php

namespace App\Jobs;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $linkId,
        public ?string $referrer,
        public ?string $userAgent,
        public ?string $ipHash,
        public bool $isDirectUrl = false,
    ) {}

    public function handle(): void
    {
        Click::create([
            'link_id'       => $this->linkId,
            'referrer'      => $this->referrer,
            'user_agent'    => $this->userAgent,
            'ip_hash'       => $this->ipHash,
            'country_code'  => null, // TODO: GeoIP lookup
            'city'          => null, // TODO: GeoIP lookup
            'is_direct_url' => $this->isDirectUrl,
        ]);

        // Increment denormalized counter
        Link::where('id', $this->linkId)->increment('click_count');
    }
}
```

---

## 8. Middleware

### 8.1 `app/Http/Middleware/ResolveDomain.php`

Detects the incoming domain and sets domain context on the request.

```php
<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveDomain
{
    /**
     * Resolve the incoming hostname to a domain type and optional Domain model.
     * Sets request attributes: domain_type, domain_model, domain_hostname.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();
        $domainMap = config('domains.map');
        $wildcardRoots = config('domains.wildcard_roots');

        // 1. Direct match in config
        if (isset($domainMap[$hostname])) {
            $request->attributes->set('domain_type', $domainMap[$hostname]);
            $request->attributes->set('domain_hostname', $hostname);

            // Load Domain model if it exists
            $domain = Domain::where('hostname', $hostname)->first();
            $request->attributes->set('domain_model', $domain);

            return $next($request);
        }

        // 2. Wildcard subdomain match (e.g., "foo.ternis.link")
        foreach ($wildcardRoots as $root) {
            if (str_ends_with($hostname, ".{$root}")) {
                // Look up in domains table (partner/family custom subdomain)
                $domain = Domain::where('hostname', $hostname)
                    ->where('is_active', true)
                    ->first();

                if ($domain) {
                    $request->attributes->set('domain_type', $domain->type->value);
                    $request->attributes->set('domain_hostname', $hostname);
                    $request->attributes->set('domain_model', $domain);

                    return $next($request);
                }

                // Subdomain not registered
                abort(404, "Unknown subdomain: {$hostname}");
            }
        }

        // 3. Check domains table for partner custom domains
        $domain = Domain::where('hostname', $hostname)
            ->where('is_active', true)
            ->first();

        if ($domain) {
            $request->attributes->set('domain_type', $domain->type->value);
            $request->attributes->set('domain_hostname', $hostname);
            $request->attributes->set('domain_model', $domain);

            return $next($request);
        }

        abort(404, "Unknown domain: {$hostname}");
    }
}
```

### 8.2 `app/Http/Middleware/EnforceDomainAccess.php`

Enforces auth requirements based on domain type.

```php
<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDomainAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $domainType = $request->attributes->get('domain_type');
        $authRequired = config("domains.auth_required.{$domainType}", false);

        if (!$authRequired) {
            return $next($request);
        }

        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Admin domain requires admin role
        if ($domainType === 'admin' && !$request->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        // Ternis domain requires family, partner, or admin role
        if ($domainType === 'ternis') {
            $role = $request->user()->role;
            if (!in_array($role, [UserRole::Admin, UserRole::Family, UserRole::Partner])) {
                abort(403, 'Ternis family or partner access required.');
            }
        }

        // Business domain requires admin role
        if ($domainType === 'business' && !$request->user()->isAdmin()) {
            abort(403, 'Business access required.');
        }

        return $next($request);
    }
}
```

### 8.3 `app/Http/Middleware/AuthenticateApi.php`

Validates API keys or SSO tokens for API routes.

```php
<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\TernisAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function __construct(
        private TernisAuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Authentication required.'], 401);
        }

        // Strategy 1: Local API key (starts with "tl_" prefix)
        if (str_starts_with($token, 'tl_')) {
            $keyHash = hash('sha256', $token);
            $apiKey = ApiKey::where('key_hash', $keyHash)->first();

            if (!$apiKey || !$apiKey->isValid()) {
                return response()->json(['error' => 'Invalid or expired API key.'], 401);
            }

            $apiKey->touchLastUsed();
            $request->setUserResolver(fn () => $apiKey->user);

            return $next($request);
        }

        // Strategy 2: SSO access token (validate against Ternis Auth userinfo, cached)
        $cacheKey = 'sso_token:' . hash('sha256', $token);

        $userInfo = Cache::remember($cacheKey, 300, function () use ($token) {
            try {
                return $this->authService->getUserInfo($token);
            } catch (\Exception) {
                return null;
            }
        });

        if (!$userInfo) {
            Cache::forget($cacheKey);
            return response()->json(['error' => 'Invalid access token.'], 401);
        }

        $user = User::where('sso_sub', $userInfo['sub'])->first();

        if (!$user) {
            return response()->json(['error' => 'User not found. Please log in via the dashboard first.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
```

---

## 9. Controllers

### 9.1 `app/Http/Controllers/Auth/TernisAuthController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TernisAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TernisAuthController extends Controller
{
    public function __construct(
        private TernisAuthService $authService,
    ) {}

    /**
     * Show login page with "Login with Ternis Auth" button.
     */
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Redirect the user to Ternis Auth for authorization.
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $codeVerifier = $this->authService->generateCodeVerifier();
        $codeChallenge = $this->authService->generateCodeChallenge($codeVerifier);

        // Store in session for callback verification
        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_code_verifier', $codeVerifier);

        $url = $this->authService->getAuthorizationUrl($state, $codeChallenge);

        return redirect()->away($url);
    }

    /**
     * Handle the OAuth callback from Ternis Auth.
     */
    public function callback(Request $request)
    {
        // Check for SSO errors (e.g. prompt=none → login_required)
        if ($request->has('error')) {
            if ($request->query('error') === 'login_required') {
                return redirect()->route('login');
            }

            return redirect()->route('login')
                ->with('error', 'Authentication failed: ' . $request->query('error_description', 'Unknown error'));
        }

        // Verify state
        $storedState = $request->session()->pull('oauth_state');
        if ($storedState !== $request->query('state')) {
            abort(403, 'Invalid OAuth state. Possible CSRF attack.');
        }

        $codeVerifier = $request->session()->pull('oauth_code_verifier');

        // Exchange code for tokens
        $tokenData = $this->authService->exchangeCode(
            $request->query('code'),
            $codeVerifier,
        );

        // Fetch user info
        $userInfo = $this->authService->getUserInfo($tokenData['access_token']);

        // Find or create local user
        $user = $this->authService->findOrCreateUser($tokenData, $userInfo);

        // Log in via Laravel session
        auth()->login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log out the user (local session only).
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

### 9.2 `app/Http/Controllers/RedirectController.php`

Handles all redirect logic across domains.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\ClickTrackerService;
use App\Services\LinkService;
use App\Services\SlugResolverService;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __construct(
        private SlugResolverService $slugResolver,
        private LinkService $linkService,
        private ClickTrackerService $clickTracker,
    ) {}

    /**
     * Handle /url/{url} — preferred direct URL redirect.
     * Clicks are stored but only visible to admins.
     */
    public function directUrl(Request $request, string $url)
    {
        $normalizedUrl = $this->slugResolver->normalizeUrl($url);
        $domain = $request->attributes->get('domain_model');

        if ($domain) {
            // Create a transient link record for analytics (or find existing)
            $link = $this->linkService->findOrCreateDirectUrlLink($normalizedUrl, $domain);
            $this->clickTracker->track($link, $request, isDirectUrl: true);
        }

        return redirect()->away($normalizedUrl, 302);
    }

    /**
     * Handle /go/{url} — alternative direct URL redirect.
     * Same behavior as /url/{url}.
     */
    public function goUrl(Request $request, string $url)
    {
        return $this->directUrl($request, $url);
    }

    /**
     * Handle /{input} — detect if URL or slug, then redirect.
     */
    public function resolve(Request $request, string $input)
    {
        $type = $this->slugResolver->classify($input);

        if ($type === 'url') {
            return $this->directUrl($request, $input);
        }

        // It's a slug — look it up
        $domain = $request->attributes->get('domain_model');

        if (!$domain) {
            abort(404);
        }

        $link = $this->linkService->resolveSlug($input, $domain);

        if (!$link) {
            return response()->view('redirect.not-found', ['slug' => $input], 404);
        }

        $this->clickTracker->track($link, $request);

        return redirect()->away($link->destination_url, 302);
    }
}
```

### 9.3 `app/Http/Controllers/DashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard home — overview stats.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_links'       => $user->links()->count(),
            'total_clicks'      => $user->links()->sum('click_count'),
            'links_this_month'  => $user->links()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'clicks_today'      => $user->links()
                ->join('clicks', 'links.id', '=', 'clicks.link_id')
                ->where('clicks.created_at', '>=', now()->startOfDay())
                ->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }

    /**
     * Links management page (Livewire: LinkTable).
     */
    public function links()
    {
        return view('dashboard.links.index');
    }

    /**
     * Create link page (Livewire: LinkForm).
     */
    public function createLink()
    {
        return view('dashboard.links.create');
    }

    /**
     * Link detail + analytics page (Livewire: LinkAnalytics).
     */
    public function showLink(int $linkId)
    {
        $link = auth()->user()->links()->findOrFail($linkId);

        return view('dashboard.links.show', compact('link'));
    }

    /**
     * API keys management page (Livewire: ApiKeyManager).
     */
    public function apiKeys()
    {
        return view('dashboard.api-keys.index');
    }
}
```

### 9.4 `app/Http/Controllers/Api/V1/LinkController.php`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function __construct(
        private LinkService $linkService,
    ) {}

    /**
     * GET /v1/links — List the authenticated user's links.
     */
    public function index(Request $request): JsonResponse
    {
        $links = $request->user()
            ->links()
            ->with('domain')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($links);
    }

    /**
     * POST /v1/links — Create a new short link.
     */
    public function store(StoreLinkRequest $request): JsonResponse
    {
        $domain = Domain::findOrFail($request->validated('domain_id'));

        $link = $this->linkService->create(
            destinationUrl: $request->validated('destination_url'),
            domain:         $domain,
            user:           $request->user(),
            customSlug:     $request->validated('slug'),
            expiresAt:      $request->validated('expires_at'),
        );

        return response()->json($link->load('domain'), 201);
    }

    /**
     * GET /v1/links/{link} — Get a specific link.
     */
    public function show(Request $request, Link $link): JsonResponse
    {
        // Ensure user owns this link
        if ($link->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        return response()->json($link->load('domain'));
    }

    /**
     * PUT /v1/links/{link} — Update a link.
     */
    public function update(UpdateLinkRequest $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $link = $this->linkService->update($link, $request->validated());

        return response()->json($link->load('domain'));
    }

    /**
     * DELETE /v1/links/{link} — Deactivate a link.
     */
    public function destroy(Request $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $this->linkService->deactivate($link);

        return response()->json(null, 204);
    }
}
```

### 9.5 `app/Http/Controllers/Api/V1/ClickController.php`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClickController extends Controller
{
    /**
     * GET /v1/links/{link}/clicks — Get click analytics for a link.
     */
    public function index(Request $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $clicks = $link->clicks()
            ->when(!$request->user()->isAdmin(), function ($query) {
                // Non-admins cannot see direct URL redirect clicks
                $query->where('is_direct_url', false);
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($clicks);
    }

    /**
     * GET /v1/links/{link}/clicks/summary — Aggregated click stats.
     */
    public function summary(Request $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $baseQuery = $link->clicks()
            ->when(!$request->user()->isAdmin(), fn ($q) => $q->where('is_direct_url', false));

        $summary = [
            'total_clicks'   => $baseQuery->count(),
            'unique_visitors' => $baseQuery->distinct('ip_hash')->count('ip_hash'),
            'top_referrers'  => (clone $baseQuery)
                ->selectRaw('referrer, COUNT(*) as count')
                ->whereNotNull('referrer')
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'top_countries'  => (clone $baseQuery)
                ->selectRaw('country_code, COUNT(*) as count')
                ->whereNotNull('country_code')
                ->groupBy('country_code')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'clicks_by_day'  => (clone $baseQuery)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->limit(30)
                ->get(),
        ];

        return response()->json($summary);
    }
}
```

---

## 10. Form Requests

### 10.1 `app/Http/Requests/StoreLinkRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id'       => ['required', 'exists:domains,id'],
            'slug'            => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:255'],
            'expires_at'      => ['nullable', 'date', 'after:now'],
        ];
    }
}
```

### 10.2 `app/Http/Requests/UpdateLinkRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_url' => ['sometimes', 'url', 'max:2048'],
            'is_active'       => ['sometimes', 'boolean'],
            'expires_at'      => ['nullable', 'date', 'after:now'],
        ];
    }
}
```

---

## 11. Routes

### 11.1 `routes/web.php`

```php
<?php

use App\Http\Controllers\Auth\TernisAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RedirectController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\ResolveDomain;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| All routes go through ResolveDomain middleware to detect host context.
|--------------------------------------------------------------------------
*/
Route::middleware(ResolveDomain::class)->group(function () {

    /*
    |----------------------------------------------------------------------
    | Auth routes (dash.ternis.link)
    |----------------------------------------------------------------------
    */
    Route::get('/login', [TernisAuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/redirect', [TernisAuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/callback', [TernisAuthController::class, 'callback'])->name('auth.callback');
    Route::post('/logout', [TernisAuthController::class, 'logout'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Dashboard routes (dash.ternis.link) — require SSO login
    |----------------------------------------------------------------------
    */
    Route::middleware(['auth', EnforceDomainAccess::class])->prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/links', [DashboardController::class, 'links'])->name('dashboard.links');
        Route::get('/links/create', [DashboardController::class, 'createLink'])->name('dashboard.links.create');
        Route::get('/links/{link}', [DashboardController::class, 'showLink'])->name('dashboard.links.show');
        Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
    });

    /*
    |----------------------------------------------------------------------
    | Redirect routes (href.nz, href.re, ternis.link, etc.)
    |----------------------------------------------------------------------
    */
    Route::middleware(EnforceDomainAccess::class)->group(function () {
        // Landing page
        Route::get('/', function () {
            $type = request()->attributes->get('domain_type');
            if ($type === 'dashboard') {
                return redirect()->route('dashboard');
            }
            return view('landing.index');
        })->name('home');

        // Direct URL redirects (preferred)
        Route::get('/url/{url}', [RedirectController::class, 'directUrl'])
            ->where('url', '.*')
            ->name('redirect.url');

        // Alternative direct URL redirect
        Route::get('/go/{url}', [RedirectController::class, 'goUrl'])
            ->where('url', '.*')
            ->name('redirect.go');

        // Slug or URL detection — MUST be last (catch-all)
        Route::get('/{input}', [RedirectController::class, 'resolve'])
            ->where('input', '[^/]+')
            ->name('redirect.resolve');
    });
});
```

### 11.2 `routes/api/v1.php`

```php
<?php

use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes — links.t-api.de/v1/*
|--------------------------------------------------------------------------
| All routes require API key or SSO token authentication.
*/

Route::middleware(AuthenticateApi::class)->group(function () {

    // Links CRUD
    Route::apiResource('links', LinkController::class);

    // Click analytics
    Route::get('links/{link}/clicks', [ClickController::class, 'index']);
    Route::get('links/{link}/clicks/summary', [ClickController::class, 'summary']);
});
```

### 11.3 `app/Providers/AppServiceProvider.php` — API Route Registration

```php
<?php

namespace App\Providers;

use App\Models\ApiVersion;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |------------------------------------------------------------------
        | Register versioned API routes.
        | Each version gets its own route file: routes/api/v1.php, v2.php, etc.
        | Retired versions keep full functionality (routes stay registered).
        |------------------------------------------------------------------
        */
        Route::prefix('v1')->group(base_path('routes/api/v1.php'));

        // Future versions:
        // Route::prefix('v2')->group(base_path('routes/api/v2.php'));

        /*
        |------------------------------------------------------------------
        | API root redirect → latest version.
        | links.t-api.de/ → links.t-api.de/v1/
        |------------------------------------------------------------------
        */
        Route::get('/', function () {
            $latest = ApiVersion::latestVersion();
            return redirect("/v{$latest}/", 302);
        })->name('api.root');
    }
}
```

---

## 12. Migrations

> See [schema.md](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/.plans/schema.md) for full column definitions.
> Migration order matters due to foreign key constraints.

### Migration Order

1. `create_plans_table` — no FK dependencies
2. `create_users_table` — FK → `plans.id`
3. `create_oauth_identities_table` — FK → `users.id`
4. `create_domains_table` — FK → `users.id`
5. `create_links_table` — FK → `domains.id`, `users.id`
6. `create_clicks_table` — FK → `links.id`
7. `create_api_versions_table` — no FK dependencies
8. `create_api_keys_table` — FK → `users.id`, `api_versions.version`

### Key Migration Notes

```php
// links table: composite unique index
$table->unique(['domain_id', 'slug']);

// links table: partial index on destination_url
$table->index([DB::raw('destination_url(191)')]);

// clicks table: NO updated_at (immutable)
$table->timestamp('created_at');
// (don't use $table->timestamps() — that adds updated_at)

// oauth_identities: encrypted columns
// Use $table->text() for access_token and refresh_token
// Encryption happens at the model level via Laravel's `encrypted` cast

// users table: no password column, no remember_token
// Use $table->uuid('sso_sub')->unique();
```

---

## 13. Seeders

### 13.1 `database/seeders/PlanSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'free',     'min_slug_length' => 8, 'custom_subdomain' => false, 'rate_limit_per_minute' => 10,  'max_links_per_day' => 50],
            ['name' => 'pro',      'min_slug_length' => 5, 'custom_subdomain' => false, 'rate_limit_per_minute' => 60,  'max_links_per_day' => 500],
            ['name' => 'business', 'min_slug_length' => 3, 'custom_subdomain' => true,  'rate_limit_per_minute' => 120, 'max_links_per_day' => null],
            ['name' => 'partner',  'min_slug_length' => 3, 'custom_subdomain' => true,  'rate_limit_per_minute' => 120, 'max_links_per_day' => null],
            ['name' => 'family',   'min_slug_length' => 1, 'custom_subdomain' => true,  'rate_limit_per_minute' => 300, 'max_links_per_day' => null],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
```

### 13.2 `database/seeders/DomainSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Enums\DomainType;
use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        $domains = [
            // Public domains
            ['hostname' => 'href.nz',          'type' => DomainType::Public],
            // Business domains
            ['hostname' => 'href.re',          'type' => DomainType::Business],
            // Ternis family domains
            ['hostname' => 'ternis.link',      'type' => DomainType::Ternis],
            ['hostname' => 'links.thosted.de', 'type' => DomainType::Ternis],
            ['hostname' => 'short.thosted.de', 'type' => DomainType::Ternis],
            ['hostname' => 'go.thosted.de',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.net',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.dev',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.org',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.eu',     'type' => DomainType::Ternis],
        ];

        foreach ($domains as $domain) {
            Domain::updateOrCreate(
                ['hostname' => $domain['hostname']],
                ['type' => $domain['type'], 'is_active' => true],
            );
        }
    }
}
```

### 13.3 `database/seeders/ApiVersionSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Enums\ApiVersionStatus;
use App\Models\ApiVersion;
use Illuminate\Database\Seeder;

class ApiVersionSeeder extends Seeder
{
    public function run(): void
    {
        ApiVersion::updateOrCreate(
            ['version' => 1],
            ['status' => ApiVersionStatus::Active],
        );
    }
}
```

---

## 14. Livewire Components

### 14.1 `app/Livewire/Dashboard/LinkTable.php`

```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\Link;
use Livewire\Component;
use Livewire\WithPagination;

class LinkTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    public function deactivate(int $linkId): void
    {
        $link = auth()->user()->links()->findOrFail($linkId);
        $link->update(['is_active' => false]);
    }

    public function render()
    {
        $links = auth()->user()
            ->links()
            ->with('domain')
            ->when($this->search, function ($query) {
                $query->where('slug', 'like', "%{$this->search}%")
                      ->orWhere('destination_url', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.dashboard.link-table', compact('links'));
    }
}
```

### 14.2 `app/Livewire/Dashboard/LinkForm.php`

```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\Domain;
use App\Services\LinkService;
use Livewire\Component;

class LinkForm extends Component
{
    public string $destination_url = '';
    public ?string $slug = null;
    public int $domain_id = 0;
    public ?string $expires_at = null;

    public ?string $createdSlug = null;
    public ?string $createdDomain = null;

    protected function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id'       => ['required', 'exists:domains,id'],
            'slug'            => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:255'],
            'expires_at'      => ['nullable', 'date', 'after:now'],
        ];
    }

    public function create(LinkService $linkService): void
    {
        $this->validate();

        $domain = Domain::findOrFail($this->domain_id);

        $link = $linkService->create(
            destinationUrl: $this->destination_url,
            domain:         $domain,
            user:           auth()->user(),
            customSlug:     $this->slug ?: null,
            expiresAt:      $this->expires_at ? new \DateTime($this->expires_at) : null,
        );

        $this->createdSlug = $link->slug;
        $this->createdDomain = $domain->hostname;

        // Reset form
        $this->destination_url = '';
        $this->slug = null;
        $this->expires_at = null;
    }

    public function render()
    {
        $domains = $this->getAvailableDomains();

        return view('livewire.dashboard.link-form', compact('domains'));
    }

    /**
     * Get domains the current user is allowed to create links on.
     */
    private function getAvailableDomains()
    {
        $user = auth()->user();

        return Domain::where('is_active', true)
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    // Public domains are always available
                    $q->where('type', 'public');

                    // User's own domains
                    $q->orWhere('user_id', $user->id);

                    // Ternis domains for family/admin
                    if ($user->isFamily() || $user->isAdmin()) {
                        $q->orWhere('type', 'ternis');
                    }

                    // Business domains for admin
                    if ($user->isAdmin()) {
                        $q->orWhere('type', 'business');
                    }
                });
            })
            ->orderBy('hostname')
            ->get();
    }
}
```

### 14.3 `app/Livewire/Dashboard/ApiKeyManager.php`

```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\ApiKey;
use App\Models\ApiVersion;
use Illuminate\Support\Str;
use Livewire\Component;

class ApiKeyManager extends Component
{
    public string $keyName = '';
    public ?string $newlyCreatedKey = null;

    protected array $rules = [
        'keyName' => ['required', 'string', 'max:255'],
    ];

    /**
     * Generate a new API key.
     */
    public function createKey(): void
    {
        $this->validate();

        // Generate a raw key with tl_ prefix
        $rawKey = 'tl_' . Str::random(48);

        ApiKey::create([
            'user_id'     => auth()->id(),
            'key_hash'    => hash('sha256', $rawKey),
            'key_prefix'  => substr($rawKey, 0, 8),
            'api_version' => ApiVersion::latestVersion(),
            'name'        => $this->keyName,
        ]);

        // Show the key to the user ONCE
        $this->newlyCreatedKey = $rawKey;
        $this->keyName = '';
    }

    public function revokeKey(int $keyId): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);
        $key->update(['revoked_at' => now()]);
    }

    public function dismissNewKey(): void
    {
        $this->newlyCreatedKey = null;
    }

    public function render()
    {
        $apiKeys = auth()->user()
            ->apiKeys()
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.dashboard.api-key-manager', compact('apiKeys'));
    }
}
```

---

## 15. Blade Views (Structure)

### 15.1 `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ternis.link' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
    <nav>
        <a href="/">ternis.link</a>
        @auth
            <div>
                <img src="{{ auth()->user()->avatarUrl(32) }}" alt="{{ auth()->user()->name }}" width="32" height="32">
                <span>{{ auth()->user()->name }}</span>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}">Login</a>
        @endauth
    </nav>

    <main>
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
```

### 15.2 `resources/views/layouts/dashboard.blade.php`

```blade
<x-layouts.app :title="$title ?? 'Dashboard'">
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar">
            <nav>
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                    Overview
                </a>
                <a href="{{ route('dashboard.links') }}" @class(['active' => request()->routeIs('dashboard.links*')])>
                    Links
                </a>
                <a href="{{ route('dashboard.api-keys') }}" @class(['active' => request()->routeIs('dashboard.api-keys')])>
                    API Keys
                </a>
            </nav>
        </aside>
        <section class="dashboard-content">
            {{ $slot }}
        </section>
    </div>
</x-layouts.app>
```

### 15.3 `resources/views/auth/login.blade.php`

```blade
<x-layouts.app title="Login">
    <div class="login-page">
        <h1>Sign in to ternis.link</h1>
        <p>Authenticate with your Ternis account to manage links and view analytics.</p>

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <a href="{{ route('auth.redirect') }}" class="btn btn-primary">
            Login with Ternis Auth
        </a>
    </div>
</x-layouts.app>
```

### 15.4 `resources/views/dashboard/index.blade.php`

```blade
<x-layouts.dashboard title="Dashboard">
    <h1>Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value">{{ $stats['total_links'] }}</span>
            <span class="stat-label">Total Links</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $stats['total_clicks'] }}</span>
            <span class="stat-label">Total Clicks</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $stats['links_this_month'] }}</span>
            <span class="stat-label">Links This Month</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $stats['clicks_today'] }}</span>
            <span class="stat-label">Clicks Today</span>
        </div>
    </div>
</x-layouts.dashboard>
```

### 15.5 Other Views (Livewire-powered)

```blade
{{-- resources/views/dashboard/links/index.blade.php --}}
<x-layouts.dashboard title="Links">
    <h1>Your Links</h1>
    <a href="{{ route('dashboard.links.create') }}" class="btn">Create Link</a>
    <livewire:dashboard.link-table />
</x-layouts.dashboard>

{{-- resources/views/dashboard/links/create.blade.php --}}
<x-layouts.dashboard title="Create Link">
    <h1>Create Short Link</h1>
    <livewire:dashboard.link-form />
</x-layouts.dashboard>

{{-- resources/views/dashboard/links/show.blade.php --}}
<x-layouts.dashboard title="Link Analytics">
    <h1>{{ $link->slug }}</h1>
    <p>{{ $link->destination_url }}</p>
    <livewire:dashboard.link-analytics :link="$link" />
</x-layouts.dashboard>

{{-- resources/views/dashboard/api-keys/index.blade.php --}}
<x-layouts.dashboard title="API Keys">
    <h1>API Keys</h1>
    <livewire:dashboard.api-key-manager />
</x-layouts.dashboard>

{{-- resources/views/redirect/not-found.blade.php --}}
<x-layouts.app title="Not Found">
    <div class="not-found">
        <h1>404</h1>
        <p>The short link <code>{{ $slug }}</code> was not found or has expired.</p>
        <a href="/">Go home</a>
    </div>
</x-layouts.app>

{{-- resources/views/landing/index.blade.php --}}
<x-layouts.app title="ternis.link — URL Shortener">
    <div class="landing">
        <h1>ternis.link</h1>
        <p>Fast, trackable short links with analytics.</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Get Started</a>
    </div>
</x-layouts.app>
```

---

## 16. Caddy Configuration

```caddyfile
# ==========================================================
# ternis.link Caddyfile
# ==========================================================
# All domains point to the same Laravel app.
# Laravel's ResolveDomain middleware handles host-based routing.
# ==========================================================

# Public link shortener
href.nz, *.href.nz {
    root * /var/www/ternis-link/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    encode gzip
}

# Business link shortener
href.re, *.href.re {
    root * /var/www/ternis-link/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    encode gzip
}

# Ternis family domains
ternis.link, *.ternis.link {
    root * /var/www/ternis-link/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    encode gzip
}

# thosted redirect domains
links.thosted.de, short.thosted.de, go.thosted.de {
    root * /var/www/ternis-link/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    encode gzip
}

# Go redirect domains
go.ternis.net, go.ternis.dev, go.ternis.org, go.ternis.eu {
    root * /var/www/ternis-link/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    encode gzip
}

# API domain
links.t-api.de {
    root * /var/www/ternis-link/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    encode gzip
}
```

> [!NOTE]
> Caddy automatically provisions and renews TLS certificates for all listed domains via Let's Encrypt. No manual certificate management needed.
