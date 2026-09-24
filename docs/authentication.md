# Authentication

SSO-only. There are no local passwords and no local registration.
Source: `app/Http/Controllers/Auth/TernisAuthController.php`,
`app/Services/TernisAuthService.php`, `app/Http/Middleware/RefreshSsoToken.php`,
`app/Http/Middleware/AuthenticateApi.php`, `config/services.php`, `config/auth.php`.

## Web SSO (OAuth 2.0 + PKCE)

Config (`config/services.php` → env):
`TERNIS_AUTH_BASE_URL` (default `https://auth.ternis.net`), `TERNIS_AUTH_CLIENT_ID`,
`TERNIS_AUTH_CLIENT_SECRET`, `TERNIS_AUTH_REDIRECT_URI`
(default `https://dash.ternis.link/auth/callback`),
`TERNIS_AUTH_SCOPES` (`openid profile email ternis:sso ternis:member ternis:customer ternis:partner`),
`TERNIS_AVATAR_BASE_URL` (`https://user.t-api.de`),
`TERNIS_AUTH_END_SESSION` + `end_session_path` (`/oauth/logout`) + `post_logout_redirect_uri`.

Flow (`TernisAuthService` + `TernisAuthController`):
1. `GET /auth/redirect` — generates PKCE verifier (64 random chars) + S256 challenge
   (`generateCodeVerifier` / `generateCodeChallenge`), stores `state` + verifier in session,
   redirects to `{base}/oauth/authorize?response_type=code&...&code_challenge...`.
2. `GET /auth/callback` — validates `state`, exchanges `code` + verifier at
   `{base}/oauth/token` (`exchangeCode`), fetches `{base}/oauth/userinfo` (`getUserInfo`),
   then `findOrCreateUser(tokenData, userInfo)`.
3. `findOrCreateUser` — `User::updateOrCreate(['sso_sub' => sub])` with
   `name/email/picture→avatar_url/user_type→sso_user_type`, `role = mapRole(userInfo)`;
   new users without a plan get `free`. Stores/updates `oauth_identities`
   (`access_token`, `refresh_token`, `token_expires_at`, `sso_claims`, `claims_synced_at`).
4. Stale/reused codes redirect to login with a friendly message instead of 500ing.
5. `GET /auth/silent` — same authorize URL plus `prompt=none` for iframe SSO checks
   (returns a code immediately when an SSO session exists, else `?error=login_required`).
6. `POST /logout` — local logout + optional RP-initiated logout at
   `getEndSessionUrl()` when `TERNIS_AUTH_END_SESSION=true`.
7. `GET /auth/demo` — **local/testing only** (`demoLogin`).

OAuth entry points are `throttle:10,1`. The flow must start **and** finish on the
dashboard host (session + PKCE live there; cookies can't cross `href.nz ↔ ternis.link`),
hence the `/login` + `/auth/*` redirect shims in `routes/web.php` (short-link hosts 302
to `config/domains.dashboard_host`, scheme-preserving).

Session guard: `config/auth.php` default `web` (session), provider `eloquent:User`.
No `remember_token` (SSO). `RefreshSsoToken` (`refresh.sso`) runs on dashboard/admin
routes: when `oauthIdentity->isTokenExpired()`, it calls `refreshAccessToken()`
(refresh grant → fresh userinfo → `syncUserFromClaims`); on failure it logs out,
invalidates the session, and redirects to `/login`. Demo users (no identity) pass through.

## Roles (`app/Enums/UserRole.php`, `TernisAuthService::mapRole`)

| SSO claims | Local `role` |
|------------|--------------|
| `user_type=ternis_member` + `ternis_member.member_badge ∈ {ternis-core, ternis-admin}` | `admin` |
| `user_type=ternis_member` (other badges) | `family` |
| `user_type=partner` or non-empty `ternis_partner` | `partner` |
| everything else | `user` |

Synced on every login and every token refresh. Helpers on `User`:
`isAdmin()`, `isPartner()`, `isFamily()`, `avatarUrl(size=64)` → `user.t-api.de/{sso_sub}.png`,
`usesTopNav()` (`nav_layout`), theme preference.

`EnforceDomainAccess` then gates hosts: `admin` host requires `isAdmin` (else 403);
`ternis` host requires `admin|family|partner`; `business` host requires admin;
guests on gated hosts go to same-host `/login`.

## API auth (`AuthenticateApi`, alias `auth.api`)

Bearer token, two accepted forms:
- `tl_*` API key — looked up by SHA-256 `key_hash` (`ApiKey::hashToken` + `verifyToken` +
  `isValid`: not revoked/expired), `touchLastUsed()` on success. Only the `key_prefix`
  (8 chars) is ever displayed again; plaintext is shown once at creation.
- SSO access token — validated via `TernisAuthService::getUserInfo`, cached 5 min
  (`sso_token:{sha256}`), resolved to `User by sso_sub`.

Failures return `401 { message }` JSON. Authenticated `/v1/*` is `links.t-api.de`-only
(`ensure.domain:api` runs before `auth.api`).
