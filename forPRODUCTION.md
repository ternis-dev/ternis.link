# Production Rollout Guide

These rollout notes cover the changes introduced from commit `f65f3eb` through `7311887`.

> [!IMPORTANT]
> A Git push to `master` does not automatically deploy the application. Production deployments require running the deployment sequence on the production host, executing database migrations, recompiling Vite assets, clearing framework caches, and restarting queue workers and process supervisors.

---

## 1. Production Deployment Procedure

Execute the deployment script from the application root directory on the production server:

```bash
bash scripts/deploy.sh
php artisan queue:restart
```

### What `scripts/deploy.sh` does:
1. `git pull origin master`: Pulls the latest commits.
2. `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`: Updates PHP packages.
3. `php artisan migrate --force`: Applies pending database migrations.
4. `php artisan deploy:check`: Verifies critical environment keys, storage symlinks, and writable paths.
5. `npm ci && npm run build`: Compiles production assets (Vite manifest entries in `public/build/`).
6. `php artisan optimize:clear`: Clears stale route, view, and config caches.
7. `php artisan config:cache`: Rebuilds the configuration cache.
8. `php artisan view:clear`: Flushes cached Blade views.

> [!WARNING]
> Never skip `npm run build`: `public/build` is deliberately `.gitignore`d. Stale or missing manifests will cause 500 errors on pages requesting the updated JavaScript bundles (`stats.js`, `app.js`) and stylesheets.
>
> Ensure queue workers (`php artisan queue:work`) are managed by Supervisor or systemd so `php artisan queue:restart` gracefully reloads worker processes with the new codebase.

---

## 2. Database Migrations

Two additive, zero-downtime migrations must be applied (`php artisan migrate --force`):

1. **`2026_10_30_000001_add_bypass_ai_check_to_api_keys_table.php`**:
   - Adds indexed boolean column `bypass_ai_check` (default `false`) to `api_keys`.
   - Allows administrators to grant selected API keys an exemption from automated TypeSafe AI threat scanning.

2. **`2026_10_30_000002_add_health_check_fields_to_links_table.php`**:
   - Adds health monitoring fields to `links`:
     - `health_check_enabled` (boolean, indexed, default `false`)
     - `last_health_check_at` (timestamp, indexed, nullable)
     - `health_status` (string `healthy` | `degraded` | `unreachable`, indexed, nullable)
     - `health_status_code` (unsigned smallint, nullable)
     - `health_response_time_ms` (unsigned integer, nullable)
     - `health_error` (string 500 chars, nullable)

Both migrations are non-blocking on SQLite, PostgreSQL, and MySQL.

---

## 3. Scheduled Tasks (`routes/console.php`)

Ensure the system cron daemon is actively invoking the Laravel scheduler every minute:
```crontab
* * * * * cd /path/to/ternis.link && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler now manages:
- **`security:scan-existing --unscanned-only --limit=25 --delay-ms=500`** (*Hourly*):
  - Audits pre-existing links in small, rate-limited chunks against TypeSafe AI. Automatically activates when `config('ai_security.enabled')` is `true`.
- **`links:check-health --limit=100 --delay-ms=100`** (*Every 6 hours*):
  - Probes opted-in link destination URLs (`health_check_enabled = true`) to detect broken destinations and high latency.
- Existing daily jobs: `links:deactivate-expired`, `bio:deactivate-expired`, `privacy:prune-ips`, `privacy:prune-session-tokens`, and Spatie database backups.

---

## 4. Key Architectural Changes & Features

### A. Frontend Stats Caching & Web API Live Telemetry
- Public stats pages (`/pages/stats`, `/pages/stats/activity`, `/pages/stats/security`, `/pages/stats/domains`) preload HTML views in the browser client and poll `GET /pages/stats/data` every ~45 seconds.
- Uses HTTP conditional `ETag` / `304 Not Modified` headers to conserve server bandwidth.
- Automatically pauses polling when the browser tab is hidden using the Page Visibility API.
- Configured via [`config/stats.php`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/config/stats.php).

### B. Safe Preview Trigger (`/{slug}+`)
- Appending `+` to any active slug URL (e.g., `https://href.nz/demo+` or `https://clicked.at/launch+`) halts the automated redirect and presents a sandboxed safety preview card with target destination, safety breakdown, and AI threat triage score.
- Route regex updated to allow the trailing `+` character without interfering with vanity slugs.

### C. Optional Link Health Monitoring
- Users can enable health checks per link in the dashboard link form, link edit modal, or via API v1/v2 (`health_check_enabled: true`).
- Probes destination URLs with HTTP `HEAD` (falling back to `GET`), 5-second timeout, and max 3 redirects.
- In-place "Test" and "Re-check" controls in the dashboard [`LinkTable`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Livewire/Dashboard/LinkTable.php) provide real-time latency diagnostics.
- Sends throttled security alert notifications to the link owner (maximum 1 notification per 12 hours) if destination URLs become unreachable.

### D. Security Audit Log Export (Admin Threat Inspector)
- Administrators can export filtered AI threat evaluations and security audit records in **CSV** or **JSON** format at `http://admin.ternis.link/threats/export?format=csv|json`.
- Formula injection protection: all user-controlled text starting with `=`, `+`, `-`, `@`, `\t`, or `\r` is sanitized with a prepended apostrophe `'`.
- IP Privacy Invariant: Exports contain only salted SHA-256 IP hashes (`creator_ip_hash`), preventing raw IP leakage.

### E. Admin AI-Exempt API Keys & Threat Moderation
- Administrators can assign `bypass_ai_check` to specific API keys to skip automated AI threat triage for automated internal workflows or trusted pipelines.
- Threat Inspector (`admin.ternis.link/threats`) includes inline "Unquarantine" and "Rescan" actions.
- Link Moderation Livewire component supports filtering by "Quarantined" status.

---

## 5. Environment & Service Configuration Checklist

Verify the following environment variables in `.env`:

```env
# AI Security & Threat Detection (TypeSafe AI)
AI_SECURITY_ENABLED=true
TYPESAFE_API_KEY=your_typesafe_api_key
TYPESAFE_MODEL=jev-latest

# Bot Protection & Human Verification (Cloudflare Turnstile)
BOT_PROTECTION_ENABLED=true
TURNSTILE_SITE_KEY=your_turnstile_site_key
TURNSTILE_SECRET_KEY=your_turnstile_secret_key

# Host Pinning & Domains
DOMAIN_ADMIN=admin.ternis.link
DOMAIN_DASHBOARD=dash.ternis.link
DOMAIN_MEINLINK_DASHBOARD=dash.meinlink.at
DOMAIN_PROTECTED=protected.ternis.link
DOMAIN_API_V1=links.t-api.de
DOMAIN_PUBLIC=href.nz

# Queue & Cache
QUEUE_CONNECTION=database # or redis
CACHE_STORE=database      # or redis
```

---

## 6. Post-Deployment Smoke Tests

Execute these verification checks immediately following deployment:

1. **Database Schema**:
   ```bash
   php artisan migrate:status
   ```
   Confirm all migrations report `Ran: Yes`.

2. **Vite Assets & Manifest**:
   - Open `https://ternis.link/pages/stats` in a browser.
   - Inspect network panel: verify `stats.js` loads, live stat counts animate, and polling requests to `/pages/stats/data` return `200` (or `304 Not Modified`).

3. **Safe Preview Trigger**:
   - Open `https://href.nz/<any-valid-slug>+` in a browser.
   - Verify the safe preview card renders with destination URL and AI threat evaluation badge instead of redirecting.

4. **Link Health**:
   - Open the dashboard at `https://dash.ternis.link/links`.
   - Toggle on the "Health" column in the table column selector.
   - Click "Test" on an opted-in link; verify latency badge appears with green/amber dot and toast confirmation.
   - Run artisan dry-run check:
     ```bash
     php artisan links:check-health --limit=5 --dry-run
     ```

5. **Security Audit Log Export**:
   - Sign in as an administrator to `https://admin.ternis.link/threats`.
   - Click the "CSV" and "JSON" export buttons in the filter bar.
   - Confirm downloaded files contain correct headers, sanitized formula cells, and salted IP hashes.

6. **Queue & Background Workers**:
   - Check supervisor or queue status:
     ```bash
     php artisan queue:monitor default
     ```
   - Ensure jobs in `jobs` or `failed_jobs` are healthy and processing normally.
