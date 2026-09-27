# How it works

Three things happen in the life of a short link: you create it, someone opens it, and the open gets counted. Everything else — slugs, QR codes, stats, custom domains — hangs off that flow.

## Creating a link

When you shorten a URL, the app stores two things together: the destination URL and a short slug, bound to one domain. The short link is simply that domain plus the slug: `href.nz/abc123` opens whatever destination was stored under slug `abc123` on `href.nz`.

- **Guests** get an auto-generated 8-character slug. No choice, no account, up to 50 links a day.
- **Members** can pick their own custom slug (minimum length depends on the plan), set an expiry date, and add a description and tags.

Slugs are unique per domain: the same slug can exist on `href.nz` and on your own domain pointing at different destinations.

## Opening a link

Opening a short link looks the slug up on that domain and redirects to the stored destination. If the link was deactivated, expired, or removed, it stops resolving and visitors see a "not found" page instead.

Every open is counted: the link's counter goes up by one and the visit is logged with referrer, browser, approximate region, and a hashed IP. There is deliberately no cookie and no cross-link tracking — an open is an anonymous event, not a profile.

## Direct links

Not everything needs a stored slug. Opening `href.nz/url/https://example.com/long/path` redirects straight to that destination and logs the visit as a direct-link open (visible in aggregates only). The `/go/` prefix works the same way, and on `href.nz` you can even paste the full `https://…` URL as the path.

## Stats

Members see per-link stats in the dashboard: opens over time, top referrers, browsers, and regions, plus a CSV export per link. Everyone can browse the public network stats on [ternis.link/pages/stats](https://ternis.link/pages/stats) — totals and per-day charts with no personal data in them.

Deleted links keep contributing to the totals: when an admin permanently deletes a link, its visit details are destroyed but its counts survive in anonymized aggregates, so the network stats never lose a creation.

## Privacy in one paragraph

Redirects store a salted hash of the visitor IP, never the address itself. A short-lived encrypted copy is kept for abuse forensics and irreversibly deleted after 30 days. Public stats are aggregates only — counts by day or domain, no personal data, ever.
