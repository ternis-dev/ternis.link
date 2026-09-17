# ternis.link — Database Schema

> See also: [Initial Project Plan](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/.plans/20260917T173102_initial-project-plan.md)
> Date: 2026-09-17

---

## Entity-Relationship Diagram

```mermaid
erDiagram
    User ||--o{ Link : "creates"
    User ||--o{ Domain : "owns"
    User ||--o{ ApiKey : "has"
    User }o--|| Plan : "subscribes to"
    Domain ||--o{ Link : "hosts"
    Link ||--o{ Click : "tracks"
    ApiVersion ||--o{ ApiKey : "scopes"

    User {
        bigint id PK
        string name
        string email UK
        string password
        enum role "admin, partner, family, user"
        bigint plan_id FK
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    Plan {
        bigint id PK
        string name UK "free, pro, partner, family, business"
        int min_slug_length "free=8, pro=5, partner=3, etc."
        boolean custom_subdomain
        int rate_limit_per_minute
        int max_links_per_day "nullable = unlimited"
        timestamp created_at
        timestamp updated_at
    }

    Domain {
        bigint id PK
        string hostname UK "e.g. href.nz, custom.example.com"
        bigint user_id FK "nullable = system domain"
        enum type "ternis, business, public, partner"
        boolean is_active
        timestamp verified_at "nullable, for partner domains"
        timestamp created_at
        timestamp updated_at
    }

    Link {
        bigint id PK
        string slug "unique per domain"
        text destination_url
        bigint domain_id FK
        bigint user_id FK "nullable = anonymous"
        bigint click_count "denormalized counter"
        boolean is_active
        timestamp expires_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    Click {
        bigint id PK
        bigint link_id FK
        string referrer "nullable"
        string user_agent "nullable"
        string ip_hash "hashed, not raw IP"
        string country_code "nullable, 2-letter ISO"
        string city "nullable"
        boolean is_direct_url "true if href.nz/url/* redirect"
        timestamp created_at
    }

    ApiKey {
        bigint id PK
        bigint user_id FK
        string key_hash UK "hashed API key"
        string key_prefix "first 8 chars for identification"
        int api_version FK "which version this key was created for"
        string name "user-given label"
        timestamp last_used_at "nullable"
        timestamp expires_at "nullable"
        timestamp revoked_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    ApiVersion {
        int version PK "1, 2, 3, ..."
        enum status "active, deprecated, retired"
        date deprecated_at "nullable"
        text changelog "nullable"
        timestamp created_at
    }
```

---

## Tables Detail

### `users`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `bigint` | PK, auto-increment | |
| `name` | `varchar(255)` | not null | |
| `email` | `varchar(255)` | not null, unique | |
| `password` | `varchar(255)` | not null | bcrypt/argon2 |
| `role` | `enum` | not null, default `user` | `admin`, `partner`, `family`, `user` |
| `plan_id` | `bigint` | FK → `plans.id`, default free plan | |
| `email_verified_at` | `timestamp` | nullable | |
| `remember_token` | `varchar(100)` | nullable | Laravel default |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### `plans`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `bigint` | PK, auto-increment | |
| `name` | `varchar(255)` | not null, unique | `free`, `pro`, `partner`, `family`, `business` |
| `min_slug_length` | `int` | not null, default `8` | shorter = more premium |
| `custom_subdomain` | `boolean` | not null, default `false` | |
| `rate_limit_per_minute` | `int` | not null, default `10` | |
| `max_links_per_day` | `int` | nullable | null = unlimited |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### `domains`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `bigint` | PK, auto-increment | |
| `hostname` | `varchar(255)` | not null, unique | Full hostname, e.g. `href.nz` |
| `user_id` | `bigint` | FK → `users.id`, nullable | null = system-owned domain |
| `type` | `enum` | not null | `ternis`, `business`, `public`, `partner` |
| `is_active` | `boolean` | not null, default `true` | |
| `verified_at` | `timestamp` | nullable | DNS verification for partner domains |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### `links`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `bigint` | PK, auto-increment | |
| `slug` | `varchar(255)` | not null | Unique per domain (composite unique: `domain_id` + `slug`) |
| `destination_url` | `text` | not null | |
| `domain_id` | `bigint` | FK → `domains.id`, not null | |
| `user_id` | `bigint` | FK → `users.id`, nullable | null = anonymous creation |
| `click_count` | `bigint` | not null, default `0` | Denormalized for fast reads |
| `is_active` | `boolean` | not null, default `true` | |
| `expires_at` | `timestamp` | nullable | Auto-expire links |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

**Indexes:**
- `UNIQUE (domain_id, slug)` — slug uniqueness is scoped to domain
- `INDEX (destination_url(191))` — for duplicate URL detection
- `INDEX (user_id)` — for "my links" queries
- `INDEX (expires_at)` — for cleanup jobs

### `clicks`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `bigint` | PK, auto-increment | |
| `link_id` | `bigint` | FK → `links.id`, not null | |
| `referrer` | `varchar(2048)` | nullable | HTTP Referer header |
| `user_agent` | `varchar(512)` | nullable | |
| `ip_hash` | `varchar(64)` | nullable | SHA-256 of IP (privacy) |
| `country_code` | `char(2)` | nullable | ISO 3166-1 alpha-2 |
| `city` | `varchar(255)` | nullable | |
| `is_direct_url` | `boolean` | not null, default `false` | `true` for `href.nz/url/*` redirects (admin-only visibility) |
| `created_at` | `timestamp` | not null | No `updated_at` — clicks are immutable |

**Indexes:**
- `INDEX (link_id, created_at)` — time-series queries per link
- `INDEX (created_at)` — global analytics
- `INDEX (is_direct_url)` — filter admin-only clicks

> [!NOTE]
> This table will grow fast. Consider partitioning by `created_at` (monthly) once volume justifies it.

### `api_keys`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `bigint` | PK, auto-increment | |
| `user_id` | `bigint` | FK → `users.id`, not null | |
| `key_hash` | `varchar(64)` | not null, unique | SHA-256 of the actual key |
| `key_prefix` | `varchar(8)` | not null | For display: `tl_abc1****` |
| `api_version` | `int` | FK → `api_versions.version`, not null | Version the key was created under |
| `name` | `varchar(255)` | not null | User-defined label |
| `last_used_at` | `timestamp` | nullable | |
| `expires_at` | `timestamp` | nullable | |
| `revoked_at` | `timestamp` | nullable | Soft-revoke |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### `api_versions`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `version` | `int` | PK | `1`, `2`, `3`, … |
| `status` | `enum` | not null, default `active` | `active`, `deprecated`, `retired` |
| `deprecated_at` | `date` | nullable | When deprecation was announced |
| `changelog` | `text` | nullable | |
| `created_at` | `timestamp` | | |

> [!IMPORTANT]
> **Retired ≠ removed.** Retired API versions keep full functionality — their controllers and routes remain intact. The `retired` status is informational, signaling that no new features will be added and users should migrate.

---

## API Versioning File Structure

```
app/Http/Controllers/Api/
├── V1/
│   ├── LinkController.php
│   ├── ClickController.php
│   └── AuthController.php
├── V2/
│   ├── LinkController.php      ← can extend V1 or be independent
│   ├── ClickController.php
│   └── AuthController.php
└── ...

routes/
├── api/
│   ├── v1.php
│   ├── v2.php
│   └── ...
└── web.php
```

Each version gets its own controller namespace and route file. Retired versions are never deleted — they continue to serve requests indefinitely.

---

## Slug Validation Rules

```
Charset:  [a-zA-Z0-9_-]
Min length: determined by user's plan (min_slug_length)
Max length: 255
```

This charset guarantees that slugs **never contain dots, colons, or slashes**, enabling deterministic URL-vs-slug detection at `href.nz/{input}`.
