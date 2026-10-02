# Links & URLs

## Shorten without an account

Open [href.nz](https://href.nz) or [href.nz/new](https://href.nz/new), paste any URL starting with `https://`, hit Shorten. You get back an 8-character link on the spot.

Guest rules, plainly stated:

- Slugs are always auto-generated — picking your own needs an account.
- Fair use is 50 links a day.
- If you paste the same destination twice, you'll be pointed at the existing short link instead of making a duplicate.
- Forgot the `https://`? The form offers it back in one click.

## Shorten as a member

Sign in and the training wheels come off:

- **Custom slugs** — pick your own, within your plan's minimum length. Taken slugs are rejected on the spot.
- **Shorter auto slugs** — generated links use your plan's length range.
- **Expiry dates** — links can stop resolving automatically.
- **Campaign tagging** — optional `utm_source` / `utm_medium` / `utm_campaign` appended at redirect time (your destination's own parameters win).
- **Descriptions and tags** — keep large collections searchable.
- **Social previews** — custom `og_title` / `og_description` / `og_image_url` so Slack/X/WhatsApp render your card; `/{slug}?debug=og` shows the crawler stub.
- **QR codes** — every short link has one: via the dashboard, or directly as `href.nz/qr/<url>` (PNG), `href.nz/qr/<url>/svg`, or `href.nz/<slug>.png` for an existing short link.
- **Click stats** — opens over time, referrers, browsers, regions, CSV export.

Manage everything from [dash.ternis.link/links](https://dash.ternis.link/links), where you can also deactivate a link (stops resolving, keeps stats) or let it expire on its own.

Need many at once? [Import CSV](https://dash.ternis.link/links/import) pastes `destination_url,domain_hostname,slug,expires_at,description,tags` rows (200 max) with per-row validation results. On the list itself, tick rows to activate or deactivate them in bulk.

## How a URL is handled

Paste a full URL and the app decides what you meant:

- Contains `.`, `:`, or `/`? It's treated as a **direct URL** and redirects without any slug lookup.
- Otherwise it must be a slug (`letters, numbers, _ -`) and gets looked up on that domain.
- Slugs starting with `v` + number (like `v1`) are reserved so API paths are never mistaken for links.

The explicit forms always work: `href.nz/url/<url>` (preferred) and `href.nz/go/<url>`. On `href.nz` you can also use the bare form `href.nz/https://example.com/…`.

## If a link stops working

A short link stops resolving when it is deactivated by its owner, expires, or is removed by an admin (usually abuse). Removed links stay in the owner's dashboard history with their stats; permanently deleted ones keep contributing to network totals as anonymized counts, as described in [How it works](./architecture).

## Bio pages

Point a verified custom domain at ternis.link and it can serve a link-in-bio page at `/` with sub-pages at `/{sub}` — built under [dash.ternis.link/bio](https://dash.ternis.link/bio), every button tap tracked with per-button CTR. Slugs shared with short links are first-write-wins: a sub-page can't take a live link slug and vice versa.
