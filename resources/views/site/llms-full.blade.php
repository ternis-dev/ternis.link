# ternis.link (full)

> Privacy-first link shortener and click-insights service by Ternis — one app, many domains: public short links, business links, family links, dashboards, and an API. This file contains the full public content inline: page inventory, legal texts, API endpoints, and a live network snapshot.

## Public pages

- [Network stats]({{ $hosts['ternis'] }}/pages/stats): public aggregate analytics for the whole network (totals, per-day charts). ([Markdown]({{ $hosts['ternis'] }}/pages/stats.md))
- [Links per domain]({{ $hosts['ternis'] }}/pages/stats/domains): link and click counts by domain. ([Markdown]({{ $hosts['ternis'] }}/pages/stats/domains.md))
- [Top links]({{ $hosts['ternis'] }}/pages/stats/links): most-clicked public short links (slugs only, no destinations). ([Markdown]({{ $hosts['ternis'] }}/pages/stats/links.md))
@foreach ($pages as $slug => $title)
- [{{ $title }}]({{ $hosts['ternis'] }}/pages/legal/{{ $slug }}): ([Markdown]({{ $hosts['ternis'] }}/pages/legal/{{ $slug }}.md))
@endforeach
@foreach ($redirects as $slug => $url)
- [{{ ucfirst($slug) }}]({{ $url }}): canonical legal page on ternis.dev (external).
@endforeach
- [Public shortener]({{ $hosts['public'] }}): guest link shortening with a hand-drawn sketchbook landing page.
- [Business shortener]({{ $hosts['business'] }}): official business links.
- [Family & partners]({{ $hosts['ternis'] }}): landing page for family, relatives, and partners.

## Collections

- [Changelog]({{ $hosts['ternis'] }}/pages/changelog): every shipped change, newest first. ([Markdown]({{ $hosts['ternis'] }}/pages/changelog.md))
- [News]({{ $hosts['ternis'] }}/pages/news): announcements from the network. ([Markdown]({{ $hosts['ternis'] }}/pages/news.md))
- [Blog]({{ $hosts['ternis'] }}/pages/blog): notes on building a private-by-design shortener. ([Markdown]({{ $hosts['ternis'] }}/pages/blog.md))

## How it works

- Slugs match `[a-zA-Z0-9_-]`; anything with dots, colons, or slashes is treated as a direct URL (`/url/{url}`, `/go/{url}`).
- Guests shorten links without an account (auto-generated 8-char slugs, 50/day per-IP quota, `throttle:10,1`); signed-in users pick custom slugs (plan minimum length) and manage links, custom domains, and API keys from the dashboard.
- Every redirect is counted asynchronously (queued click tracking): referrer, browser, approximate country, unique visitors via SHA-256 IP hashes. Visitor IPs are stored encrypted for abuse investigation only and auto-deleted after 30 days. No tracking cookies.
- Bot protection on public forms is self-hosted Altcha proof-of-work — no third parties, no data leaves the servers.

## API v1

Base: `{{ $hosts['api'] }}/v1/`. Auth: `Authorization: Bearer <api-key>` (keys start with `tl_`). Every response carries `API-Version` + `API-Latest-Version`; errors are `{ message }` (`{ message, errors }` for validation); plan overages are `429` with `Retry-After`.

- `GET /v1/` — public version metadata.
- `POST /v1/links/public` — public guest link creation (system domains only, no custom slugs).
- `GET /v1/links` — list own links.
- `POST /v1/links` — create a link.
- `GET /v1/links/{link}` — show a link.
- `PATCH /v1/links/{link}` — update a link.
- `DELETE /v1/links/{link}` — delete a link.
- `GET /v1/links/{link}/clicks` — click rows.
- `GET /v1/links/{link}/clicks/summary` — aggregated click stats.
- `GET /v1/domains` — list domains.
- `POST /v1/domains` — register a custom hostname (plan-gated).
- `GET /v1/domains/{domain}` — show a domain.
- `POST /v1/domains/{domain}/verify` — verify DNS TXT ownership.
- `DELETE /v1/domains/{domain}` — remove a domain.

Machine-readable contract: `docs/api-v1-openapi.yaml` in the repo, served raw at `{{ $hosts['docs'] }}/api-v1-openapi.yaml`. Rendered developer docs (architecture, authentication, routing): `{{ $hosts['docs'] }}/`.

## Dashboards (login required)

- [User dashboard]({{ $hosts['dashboard'] }}): manage links, domains, API keys, analytics.
- [Admin console]({{ $hosts['admin'] }}): moderation and system overview (admin role required).

## Live network snapshot

Aggregate counts, refreshed every 10 minutes. No personal data.

- Links Created (All Time): {{ number_format($overview['total_links']) }}
- Active Links: {{ number_format($overview['active_links']) }}
- Removed Links: {{ number_format($overview['removed_links']) }}
- Total Clicks (All Time): {{ number_format($overview['total_clicks']) }}
- Links Today: {{ number_format($overview['links_today']) }}
- Clicks Today: {{ number_format($overview['clicks_today']) }}

## Full legal texts

The raw sources of the pages above, inline and verbatim.

@foreach ($pages as $slug => $title)
---

{!! $legal[$slug] ?? '' !!}

@endforeach
