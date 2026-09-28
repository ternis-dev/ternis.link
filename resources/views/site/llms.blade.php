# ternis.link

> Privacy-first link shortener and click-insights service by Ternis — one app, many domains: public short links, business links, family links, dashboards, and an API.

ternis.link shortens long URLs into memorable short links and counts every redirect (referrer, browser, approximate region — privacy-preserving, no personal data). Authentication is SSO-only via Ternis Auth (OAuth 2.0 + PKCE); there are no local passwords. Guests can shorten links without an account on the public domain, subject to fair-use quotas and self-hosted proof-of-work bot protection.

## Public pages

- [Network stats]({{ $hosts['ternis'] }}/pages/stats): public aggregate analytics for the whole network (totals, per-day charts). ([Markdown]({{ $hosts['ternis'] }}/pages/stats.md))
- [Links per domain]({{ $hosts['ternis'] }}/pages/stats/domains): link and click counts by user-added domain. ([Markdown]({{ $hosts['ternis'] }}/pages/stats/domains.md))
@foreach ($pages as $slug => $title)
- [{{ $title }}]({{ $hosts['ternis'] }}/pages/legal/{{ $slug }}): ([Markdown]({{ $hosts['ternis'] }}/pages/legal/{{ $slug }}.md))
@endforeach
@foreach ($redirects as $slug => $url)
- [{{ ucfirst($slug) }}]({{ $url }}): canonical legal page on ternis.dev (external).
@endforeach
- [Public shortener]({{ $hosts['public'] }}): guest link shortening with a hand-drawn sketchbook landing page.
- [Austrian shortener](https://meinlink.at): guest link shortening in German, same rules, own red-sketch theme.
- [Business shortener]({{ $hosts['business'] }}): official business links.
- [Family & partners]({{ $hosts['ternis'] }}): landing page for family, relatives, and partners.

## Collections

- [Changelog]({{ $hosts['ternis'] }}/pages/changelog): every shipped change, newest first. ([Markdown]({{ $hosts['ternis'] }}/pages/changelog.md))
- [News]({{ $hosts['ternis'] }}/pages/news): announcements from the network. ([Markdown]({{ $hosts['ternis'] }}/pages/news.md))
- [Blog]({{ $hosts['ternis'] }}/pages/blog): notes on building a private-by-design shortener. ([Markdown]({{ $hosts['ternis'] }}/pages/blog.md))

## API

- [API v1]({{ $hosts['api'] }}/v1/): link CRUD, domains, click analytics. Authenticated with `Authorization: Bearer <api-key>` (keys start with `tl_`); public guest creation via `POST /v1/links/public` on system domains. Machine-readable contract: `docs/api-v1-openapi.yaml` in the repo.

## Developer docs

- [Docs home]({{ $hosts['docs'] }}/): architecture, authentication, domains & routing, links — rendered from the repo `docs/`, no login.
- [OpenAPI yaml]({{ $hosts['docs'] }}/api-v1-openapi.yaml): the v1 contract, served raw.

## Dashboards (login required)

- [User dashboard]({{ $hosts['dashboard'] }}): manage links, domains, API keys, analytics.
- [Admin console]({{ $hosts['admin'] }}): moderation and system overview (admin role required).

## Crawl & agent files

- [sitemap.xml]({{ $base }}/sitemap.xml): app pages on this host.
- [robots.txt]({{ $base }}/robots.txt): crawl policy for this host.

## Full content

- [llms-full.txt]({{ $base }}/llms-full.txt): this file plus the full legal texts, the API endpoint list, and a live network snapshot — everything inline, no further fetches needed.
