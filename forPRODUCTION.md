# Production rollout notes

These notes cover the changes introduced from `3b4a6e5` onward. A Git push does not deploy the application: the repository currently defines CI checks, not an automatic production deployment. Production availability, DNS, certificates and provider settings have not been verified from this workspace.

## Apply on the production server

Use the existing deployment process from the application checkout:

```sh
bash scripts/deploy.sh
php artisan queue:restart
```

The deploy script pulls `master`, installs Composer dependencies, runs `migrate --force` and `deploy:check`, installs Node dependencies, builds Vite assets, clears framework caches and rebuilds the configuration cache. Queue workers must be managed by a process supervisor so they restart after `queue:restart`. Reload PHP-FPM/OPcache if required by your deployment configuration. For staged releases, build and migrate before switching traffic using your existing release procedure.

Do not skip `npm run build`: `public/build` is ignored by Git. The new dashboard requires the `meinlink-dashboard.css` and `meinlink-dashboard.js` manifest entries. Deploy the committed SVG favicons too. Keep the existing APP_KEY; changing it invalidates encrypted data and credentials.

## Domains and configuration

- Route `dash.meinlink.at` to the application and provision valid HTTPS. The committed Caddy configuration already includes `meinlink.at, *.meinlink.at`; confirm the deployed proxy and DNS actually cover the new host. The web root must be `public/`.
- `DOMAIN_MEINLINK_DASHBOARD=dash.meinlink.at` is now documented in `.env.example`; this is also the default. Keep `DOMAIN_MEINLINK=meinlink.at` configured. If overriding the dashboard host, include that host in the domain map/SSO allowlist as well; changing the environment value alone does not register an arbitrary SSO destination.
- Keep `dash.ternis.link` working as the SSO identity host. The German dashboard uses the existing one-time cross-domain handoff. Do not change the shared session cookie to `.meinlink.at`: middleware uses a host-only cookie outside the identity domain. Verify the full login and logout round trip over HTTPS.
- Ensure `protected.ternis.link` resolves to this application with valid HTTPS. Keep `DOMAIN_PROTECTED` consistent with that host.
- Configure `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY`; allow the actual guest-form and protected-gateway hostnames in the Cloudflare widget configuration. Browser verification depends on these settings and `BOT_PROTECTION_ENABLED`. Missing keys must not be mistaken for a working human-verification deployment.
- Confirm `AI_SECURITY_ENABLED`, `TYPESAFE_API_KEY` and `TYPESAFE_MODEL` are set as intended. Use the existing secret-management process; never commit credentials.
- Keep the Laravel scheduler running every minute through the existing cron/service configuration. Scheduled commands prune encrypted IPs and expired session-token records. Keep queue workers running for click recording and link threat evaluation.

## Database and behavior changes

Run all pending migrations before serving the new code. This dashboard increment adds no migration, but an older production checkout may still need the bot-protection and threat-evaluation migrations from October 29. `deploy:check` is a required gate.

- URL scans from public forms, APIs and the admin sandbox now persist in `threat_evaluations`, including cache hits, invalid/blocked destinations and provider-unavailable/error outcomes. Link-evaluation failures are also recorded. Monitor database growth: these records currently have no automatic deletion schedule. Standalone scans use `action_taken=scanned`; their recommendations do not mean a link was quarantined.
- Public quarantine totals now include legacy quarantine-tagged links without a matching recorded action. This cannot reconstruct links deleted before logging existed. Totals describe recorded actions plus unmatched tagged links, rather than unique currently blocked links. The overview uses a new cache key; other aggregates can remain cached for ten minutes.
- Public stats now include Activity and Security pages with Markdown versions. Metric cards display counts beside labels. The protected gateway uses monochrome icons and updated verification guidance.
- `dash.meinlink.at` has its own German UI, restricted to the signed-in user's meinlink.at links: create/edit, sorting/search, pause/reactivation, QR downloads, statistics for 7/30/90 days and aggregate CSV export. Passwords are hashed through the existing service. Empty password fields preserve existing passwords; removal is explicit. Quarantined links cannot be edited/reactivated here; expired paused links need their expiry changed before activation.
- Terms and Privacy Policy now list configured domains and disclose AI checks, scan logging, verification cookies and attribution. HTML and Markdown read the same source files. Publish the required change notice through your normal process; verify the actual processor/transfer arrangements match the notices. Legal pages remain English and are labelled accordingly in the German UI.

## Smoke tests after rollout

1. Open `https://dash.meinlink.at/login`: German UI, styles and JavaScript load without manifest errors. Sign in through the identity host and confirm return to the German dashboard. Sign out and confirm access requires login again.
2. Create a meinlink.at link, copy it, inspect its details, edit it and download a PNG QR code. Other accounts' links and links on other domains must remain inaccessible.
3. Set a password, verify the destination requires it, then change/remove it. Enable browser verification and complete a real Turnstile check. Verify quarantined links stay blocked and expired links do not resume until expiry is fixed.
4. Check statistics across date ranges and download the CSV; confirm only the account's meinlink.at traffic appears.
5. Run a URL scan twice and inspect the admin threat log: both requests should have records, with the second marked cached where applicable. A recommendation must not increase quarantine-action totals. Confirm provider-unavailable results are clearly unverified.
6. Open `/pages/stats`, `/pages/stats/activity`, `/pages/stats/security`, the legal pages and their `.md` versions on ternis.link. Check the protected gateway icon and verification flow.
7. Inspect application/queue logs and failed jobs after rollout. Confirm scheduler and workers are alive. A successful Git push or CI build alone does not establish production health.
