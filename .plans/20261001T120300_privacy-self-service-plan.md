# ternis.link — Privacy Self-Service Plan

> Date: 2026-10-01
> Status: DRAFT — for review before implementation
> Scope: GDPR export + account deletion + retention controls (no new legal copy — uses existing `resources/legal/privacy.md`)

## 1. Mission

Users can today export per-link CSVs but cannot see everything stored about them or leave cleanly. Add one-click full export + self-serve deletion/anonymization with the existing tombstone/analytics guarantees intact.

## 2. Goals / Non-Goals

Goals:
- `GET /settings/privacy` dashboard page: what-we-store summary, export button, deletion flow (confirm + 14-day grace + cancel).
- Full export ZIP: `profile.json`, `links.csv`, `clicks-summary.csv` (aggregates only, no raw visitor IPs), `domains.csv`, `api-keys.csv` (metadata, no digests), `activity.csv`, `notifications.csv`.
- Deletion: soft-schedule (`users.deletion_requested_at`, 14-day grace, login cancels) then hard-anonymize: user row removed, `links.user_id → null` + `creator_ip_* → null` (guest-style), `LinkTombstone` aggregates kept, `OAuthIdentity` + `ApiKey` + notifications/activity purged, clicks keep `ip_hash` (already one-way) but lose link→user join.
- Retention knobs: `links:prune-guest-ips` (encrypted IP column TTL 30d already implied — enforce), `api-request-logs:prune` (keep 12mo), bulk-op result prune (7d, shared with bulk plan).

Non-goals (v1):
- No lawyer features: no DPA generator, no cookie banner (no ad trackers by design), no per-click raw-data export (would leak visitor hashes).
- No admin-triggered user purge UI (admins use existing moderation + artisan with double confirm).
- No re-write of `privacy.md` legal text (docs link to behavior, copy unchanged).

## 3. Schema

- `users`: add `deletion_requested_at datetime nullable` + index. No other user columns (SSO-only already — nothing to add).
- `links`: no columns (reuse `creator_ip_hash/creator_ip_encrypted` nulling).
- New table `privacy_exports` (ULID): `id, user_id FK cascade, status enum(pending,processing,done,failed)`, `path varchar nullable`, `expires_at datetime`, `created_at`. Result ZIP on `local` disk, 7-day expiry.
- Config `config/privacy.php`: `export_ttl_days=7`, `deletion_grace_days=14`, `api_log_retention_months=12`, `guest_ip_retention_days=30`.

## 4. API Surface (`links.t-api.de/v1`)

- `POST /v1/account/export` → `202 {privacy_export_id, status}` (one running export per user, 429/409 otherwise).
- `GET /v1/account/exports`, `GET /v1/account/exports/{id}` (status + `download_url` signed, owner-only).
- `GET /v1/account/export/download/{id}` — signed, single-use-ish (expiry-checked), `Content-Disposition: attachment`.
- `POST /v1/account/deletion` (body `{confirm: "DELETE-ME"}`) → schedules, returns `deletion_at`; `DELETE /v1/account/deletion` cancels (also auto-cancelled by any successful login during grace).
- `GET /v1/account/data-summary` → counts + retention dates (powers the settings page without leaking rows).
- All responses `{ message }` shaped; OpenAPI + `docs/api.md#privacy` section.

## 5. Jobs / Commands

- `BuildPrivacyExport` (queue): gathers in chunks (1000 links/page), writes CSVs via `league/csv`-free `fputcsv` to temp dir, zips (`ZipArchive`), stores to `storage/app/privacy/{id}.zip`, marks done, notifies inbox. Never includes: `creator_ip_encrypted`, raw IPs, `api_keys.key_hash`, SSO `access/refresh_token`, other users' rows.
- `ProcessAccountDeletions` (daily scheduler): finds `deletion_requested_at ≤ now()-grace`, runs `AccountErasureService::erase(User)` in transaction: null PII on links, delete keys/identities/notifications/activity/exports/bulk-ops, delete avatar cache ref, finally delete user row. Logs one `ActivityLog::ACCOUNT_DELETED` (system actor) before user row goes.
- `PrunePrivacyArtifacts` (daily): deletes expired export ZIPs + bulk results + `api_request_logs` older than retention + `creator_ip_encrypted` older than 30d (set null, keep hash for quota).
- Artisan: `privacy:erase-user {email|sso_sub} --force` (admin, double-confirm, same service).

## 6. Dashboard / Livewire

- `settings/privacy` section (new `PrivacySettings` Livewire component on existing settings page — no new top-level nav to avoid layout churn):
  - "Your data" card: counts (links, domains, keys, clicks received) + retention lines from config.
  - Export card: button → polls `privacy_exports` status → download link (expires date shown).
  - Danger zone: two-step delete (checkbox consequences + type-to-confirm + SSO re-auth via `RefreshSsoToken` fresh-login check ≤15min) → scheduled banner with `deletion_at` + Cancel button.
- Emails: reuse existing `notify_security_email` preference — deletion scheduled/cancelled/completed each send one security mail (no new preference key).

## 7. Security / Privacy Details

- Export ZIP: owner-only signed route, `throttle:10,1`, logged to `ApiRequestLog` (existing middleware) + activity `account.exported`.
- Deletion auth: requires fresh SSO session (≤15min, check `claims_synced_at` or session `auth_time`); API-key Bearer alone cannot schedule deletion (403 `fresh-login-required`) — prevents stolen-key account wipe.
- Grace login cancels automatically in `TernisAuthController::callback` (if `deletion_requested_at` set → null + notify).
- Admins cannot download user exports; `admin.ternis.link/errors` shows only aggregate counts (no export contents).
- `PreviewController`/redirect paths untouched (no user data in hot path).

## 8. Tests

- `PrivacyExportTest`: ZIP contains all 7 files, excludes secrets (no `key_hash`, no tokens, no raw IP), aggregates match dashboard, second concurrent export → 409, expiry prune works.
- `AccountDeletionTest`: schedule → login cancels; schedule → grace elapses → erase: user gone, links anonymized (`user_id null`, IPs null), tombstones/click aggregates intact, keys/identities gone, cannot login-as-deleted (SSO re-provision creates fresh user — assert new id).
- `FreshLoginGateTest`: stale session + API key → 403; fresh SSO → 202.
- `RetentionPruneTest`: old `api_request_logs` / encrypted IPs / export ZIPs pruned, hashes kept.

## 9. Milestones

- M1: config + migrations (`users.deletion_requested_at`, `privacy_exports`) + `data-summary` + settings UI skeleton.
- M2: export job + download routes + prune command + tests.
- M3: deletion schedule/cancel/erase + fresh-login gate + grace-cancel + docs (`docs/links.md`, `privacy.md` behavior note, `docs/api.md`) + full suite.

## 11. Risks

- SSO re-provision resurrecting deleted users → accepted and documented (new account, no history link — privacy-correct).
- Large-account export OOM → chunked queries + temp files, never `::all()`; cap export at 100k links (paginate, note truncation in `README.txt` inside ZIP).
