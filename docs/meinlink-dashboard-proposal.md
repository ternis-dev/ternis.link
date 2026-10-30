# German dashboard at dash.meinlink.at

Status: initial dashboard implemented in this repository for `dash.meinlink.at`; deployment, DNS and TLS still require production verification.

Implemented: an independent German Blade UI and dedicated CSS/JavaScript; own meinlink.at link list, search/status filters, creation/editing, pause/reactivation, per-link seven-day click counts, PNG QR downloads, help and login. Ownership and hostname restrictions apply to every link endpoint. Quarantined links cannot be edited or reactivated. Existing cross-domain SSO is reused. Account-level settings remain an explicitly labelled link to the existing public dashboard.

The next iteration adds a German statistics page with 7/30/90-day ranges, zero-filled daily totals, top links and CSV export. The link list supports newest/oldest/most-clicked sorting. Creation shows the shared daily quota and field-specific errors. Quarantine states are consistent for tagged links and burst detections; expired paused links require an expiry change before activation. Clipboard fallback fields are reused instead of duplicated.

The remaining items below describe future extensions and deployment checks, rather than features all included in this first version.

## Recommendation

Build a German entry point on the existing public-dashboard backend. Keep the same Ternis Auth account, ownership checks, link service and quotas. Avoid a separate account system or a translated copy of the entire developer dashboard.

## First version

- German navigation: Übersicht, Meine Links, Link erstellen, Statistiken, Einstellungen, Hilfe.
- Default to meinlink.at links, with an explicit option to show the user's other public-network links. Apply ownership checks to every read and mutation; a hostname filter is not authorization.
- A single create form: Zieladresse, optional Wunschkürzel, then Kopieren and QR-Code herunterladen.
- Searchable link list with clear active, expired and quarantined states. Edit, copy, QR download and deactivate actions; confirm destructive deletion.
- Simple per-link click totals and date filters. Keep API keys, bulk tools and advanced controls behind an optional advanced section.
- German empty states, validation messages, date/number formatting and accessible mobile layouts.
- Visible Datenschutz, Nutzungsbedingungen, Impressum and support links. Provide reviewed German legal text before presenting the service as fully localized.

## Implementation requirements

1. Add an explicit dashboard hostname configuration and domain resolution. Do not rely on wildcard customer-domain lookup for this application host.
2. Reuse public-dashboard controllers and authenticated routes with host-aware URL generation. Existing helpers frequently target my.href.nz and must be audited for redirects, form actions and navigation.
3. Set the German locale per request and render lang="de". Extract the first-version strings into translation files; do not translate user content.
4. Reuse SSO with an approved callback and safe return-host allowlist. Cookies cannot be shared between ternis.link and meinlink.at: use the existing cross-domain handoff flow and test it explicitly.
5. Check DNS, TLS, reverse-proxy routing, session/CSRF behavior and configured auth origins before launch. Caddy already has a meinlink.at wildcard block, but production DNS and certificates are not verified here.
6. Add the new host to legal scope only when it is actually introduced. Keep existing dashboards available.

## Acceptance checks

Guest login returns to dash.meinlink.at; logout works; creation defaults to meinlink.at; list/edit/delete/analytics cannot access another user's links; all first-version UI and errors are German; navigation and form actions stay on the intended host; mobile and keyboard use work; legal and appeal links remain reachable.
