# Privacy Policy

> Last updated: 2026-09-24

This policy explains what data ternis.link collects when you shorten links, open short links, or use the dashboard — and your rights over that data.

**Controller:** Ternis / ternis.link ([legal@ternis.dev](mailto:legal@ternis.dev) / [platforms@ternis.dev](mailto:platforms@ternis.dev)). If you have questions about your data, contact us there.

## What we collect

**Short links you create.** Destination URL, short code, domain, and optional description, tags and expiry. Guest links additionally store a one-way hash of your IP address to enforce the daily fair-use quota — never the address itself.

**Link usage (clicks).** When someone opens a short link we count the visit and store coarse technical data: referrer, browser user-agent, a one-way IP hash (for unique-visitor counts), and an approximate country/city derived once and then kept without the address. For abuse investigation we additionally store the visitor IP **encrypted**, and it is **automatically deleted after 30 days** (see Retention).

**Accounts.** If you sign in with Ternis Auth SSO we store your SSO subject ID, name, email address, avatar URL and role/plan. There are no passwords — authentication happens at the SSO provider.

**Sessions and errors.** Login sessions and a minimal error log (no raw IPs — only hashes) to keep the service running and debuggable.

**Bot protection.** The guest shortener uses Cloudflare Turnstile. Cloudflare processes signals such as the visitor IP address, TLS fingerprint, user agent, site key, and page origin to distinguish people from bots. We validate Turnstile's short-lived, single-use token on the server; we do not retain that token.

## What we never store in plain text

IP addresses are never stored readable. Day-to-day features (quotas, unique counts, rate limits) run on **SHA-256 hashes, optionally mixed with a server-side secret**. Raw visitor/creator IPs exist only in encrypted form for the 30-day abuse window described above.

## Cookies

We use strictly necessary cookies only: the login session and framework security tokens (CSRF). No tracking, analytics or advertising cookies — on any domain. The external Turnstile widget is also subject to Cloudflare's [Turnstile Privacy Addendum](https://www.cloudflare.com/turnstile-privacy-policy/).

## Legal basis (GDPR)

- **Contract / requested service** (Art. 6(1)(b)): creating and resolving short links, your account.
- **Legitimate interests** (Art. 6(1)(f)): abuse prevention, security, capacity planning. We weighed this against your privacy by hashing IPs by default and encrypting plus auto-deleting the rest.
- **Consent** (Art. 6(1)(a)): only where explicitly asked — nothing on this service currently requires it.

## Retention

| Data | Kept for |
|---|---|
| Links and their settings | Until you delete/deactivate them (analytics are preserved on deactivation) |
| Encrypted visitor/creator IPs | **30 days**, then automatically and irreversibly deleted |
| IP hashes | As long as needed for quotas and deduplication |
| Accounts | Until you stop using SSO sign-in and ask for deletion |
| Error log entries | 90 days |

## Sharing

We do not sell data and share nothing for marketing. Data is processed on infrastructure operated by ternis.net. Cloudflare processes bot-protection signals as a processor when you use Turnstile, as described above. Abuse cases (spam, phishing, attacks launched through short links) may be reported to providers or authorities with the minimum data required.

## Your rights

Access, rectification, erasure, restriction, portability and objection (GDPR Art. 15–21), plus the right to complain to your supervisory authority. Because guest links carry no account, include the short URL(s) in erasure requests so we can find them. Contact: [legal@ternis.dev](mailto:legal@ternis.dev) or [platforms@ternis.dev](mailto:platforms@ternis.dev). Abuse reports: [abuse@ternis.dev](mailto:abuse@ternis.dev).

## Changes

Material changes will be announced on the service at least 14 days before they take effect. The date at the top shows the current version.
