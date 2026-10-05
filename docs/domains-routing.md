# Domains

Every host in the network does exactly one job. Pick the right one and the app behaves; pick the wrong one and you'll get a 404 rather than something half-working.

## The hosts

| Host | For | Login? |
|------|-----|--------|
| `href.nz` | Shortening links as a guest, in English. | Optional |
| `meinlink.at` | Shortening links as a guest, in German — same rules, own design. | Optional |
| `href.re` | Official business links. No guest form. | Required |
| `ternis.link` | Family, relatives, partners. Public network stats live here too. | Required |
| `dash.ternis.link` | Your dashboard: `clicked.at`, `ternis.link`, `href.re`, partner and custom-domain links, plus stats, domains, API keys. | Required |
| `my.href.nz` | Public-links dashboard: your `href.nz`, `meinlink.at` and `href.yt` links only. Account pages (API keys, domains, settings) stay on `dash.ternis.link`. | Required |
| `admin.ternis.link` | Admin console: moderation, users, system overview. | Admins only |
| `docs.ternis.link` | These guides. | Never |
| `links.t-api.de` | The public API (`/v1`). Keys or guest endpoints only. | Per endpoint |

Short links always resolve on the domain they were created on: `href.nz/abc123` and `go.example.com/abc123` are different links even with the same slug.

## Your own custom domains

Members can shorten links on their own hostnames (availability depends on the plan). The flow in the dashboard under Domains:

1. **Add** your hostname. Subdomain claims (like `go.yourname.example`) and full custom hostnames are both supported.
2. **Prove ownership** by adding the shown DNS `TXT` record to your domain.
3. **Verify** — the app checks the record and activates the domain.
4. Shorten links on it like anywhere else. Removing the domain later doesn't break already-created links' stats, but new links can't use it.

Custom domains you own are verified against you; system domains (`href.nz`, `meinlink.at`, `href.re`, …) are reserved and can't be claimed.

## Short links vs. app pages

On redirect hosts, single-segment paths are short-link slugs — that namespace belongs to links. App pages therefore live under reserved prefixes that never collide: `/url/…` and `/go/…` for direct redirects, `/preview/…` for sandbox previews, `/v1/…` for the API, and `/healthz` for the load-balancer probe. Everything else that looks like a slug gets looked up as one, and unknown slugs land on the "link not found" page.
