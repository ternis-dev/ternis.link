# Start here

`ternis.link` is a link shortener with click analytics. Paste a long URL, get a short link, share it, and see how often it gets opened.

No account is needed to start: anyone can shorten links as a guest on [href.nz](https://href.nz). Signing in unlocks custom slugs, QR codes, click stats, API keys, and your own domains.

## The network at a glance

| Host | What it is |
|------|------------|
| [href.nz](https://href.nz) | Public shortener. Shorten links without an account. |
| [href.re](https://href.re) | Official business links. |
| [ternis.link](https://ternis.link) | Family, relatives, and partners — plus public network stats. |
| [dash.ternis.link](https://dash.ternis.link) | Your dashboard: links, stats, domains, API keys. |
| [admin.ternis.link](https://admin.ternis.link) | Admin console (admins only). |
| [docs.ternis.link](https://docs.ternis.link) | These guides. |
| [links.t-api.de](https://links.t-api.de) | The public API. |

## Guides

| Guide | What it covers |
|-------|----------------|
| [How it works](./architecture) | What happens when you shorten a link and when someone opens it. |
| [Links & URLs](./links) | Guest and member shortening, custom slugs, QR codes, direct links, expiry. |
| [Accounts & API keys](./authentication) | Password-free sign-in, what members get, using the API. |
| [Domains](./domains-routing) | Which host does what, plus your own custom domains. |

## For developers

The public API lives at `https://links.t-api.de/v1`, authenticated with personal API keys. The machine-readable contract is [OpenAPI 3.1](/api-v1-openapi.yaml). The [Accounts & API keys](./authentication) guide shows how to create a key and make your first request.
