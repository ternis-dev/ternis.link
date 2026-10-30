# Privacy Policy

> Last updated: 2026-10-30

This policy explains what data ternis.link collects when you shorten links, open short links, or use the dashboard — and your rights over that data.

**Controller:** Ternis / ternis.link ([legal@ternis.dev](mailto:legal@ternis.dev) / [platforms@ternis.dev](mailto:platforms@ternis.dev)). If you have questions about your data, contact us there.

## Services and domains covered

This document applies to the Ternis link network, including:

| Purpose | Domains |
|---|---|
| Public short links | href.nz, meinlink.at, clicked.at, href.yt |
| Business and network links | href.re, ternis.link, int.ternis.link |
| Additional network redirect domains | links.thosted.de, short.thosted.de, go.thosted.de, go.ternis.net, go.ternis.dev, go.ternis.org, go.ternis.eu |
| Link management and administration | my.href.nz, dash.meinlink.at, dash.ternis.link, admin.ternis.link |
| Legacy dashboard addresses | my.ternis.link, my.href.yt (redirect to the current public dashboard) |
| QR generation and APIs | qr.href.nz, qr.t-api.de, links.t-api.de |
| Documentation and security verification | docs.ternis.link, protected.ternis.link |

It also covers supported subdomains and customer-connected domains when they serve this application. Independent destination websites and other services linked from this application have their own policies. Account sign-in uses Ternis Auth at auth.ternis.net; its own notices also apply to authentication.

## What we collect

**Short links you create.** Destination URL, short code, domain, and optional description, tags and expiry. Guest links additionally store a one-way hash of your IP address to enforce the daily fair-use quota — never the address itself.

**API keys and link attribution.** If you create a link through the API with a personal key (`tl_…`), the link stores which key created it (key ID, name and prefix) so you can filter your links per key on the dashboard and in the API. API-token metadata (creation, visibility changes, revocation) is kept in the same activity history as your other actions. Hiding a key's links from the dashboard (`Show its links on the dashboard` off) only changes where they are listed — nothing is deleted or moved.

**API request log.** Requests to API routes with audit logging enabled (including link, QR and authentication APIs, whether authenticated or not) write one audit row: HTTP method, host, path (without query string), response status, duration in milliseconds, a one-way IP hash, a truncated user-agent string, and — for authenticated calls — your user ID and the API key ID used. Request bodies, query strings, tokens and raw IP addresses are never stored. The log exists for abuse investigation and capacity planning. There is currently no automatic deletion schedule for these audit rows. Erasure and objection requests are assessed individually; retaining evidence for a legal claim requires a specific applicable justification, rather than a blanket exclusion from your rights.

**Link usage (clicks).** When someone opens a short link we count the visit and store coarse technical data: referrer, browser user-agent, a one-way IP hash (for unique-visitor counts), and an approximate country/city derived once and then kept without the address. For abuse investigation we additionally store the visitor IP **encrypted**, and it is **automatically deleted after 30 days** (see Retention).

**QR codes, bio pages and link controls.** We process QR generation records (such as format, time and associated link), bio-page content and interactions, link groups, device-targeting settings and password-protection settings where you use these features. Link passwords are stored as hashes. A URL, referrer or content field may itself contain personal data; avoid including secrets or unnecessary personal information in links you submit.

**Standalone URL scans.** Public scan forms, scan APIs and the admin sandbox log each scan request in the security database, including cached results, invalid or blocked URLs and unavailable/error outcomes. Records include the submitted URL, result, recommendation, cache status, time, request host/path, user-agent, IP hash and signed-in account ID where available. A recommendation alone does not deactivate a link. These records follow the security-evaluation retention described below.

**AI-assisted security and moderation.** When enabled, TypeSafe AI receives the destination URL and hostname, available title/description, browser user-agent and account context (an account identifier, registration time and lifetime link count) for eligible link evaluations. Browser-signature evaluation can send a user-agent and requested path. We retain evaluation records containing the destination, associated account/link IDs, creator IP hash, user-agent where available, verdict, score, confidence, reasoning, action and metadata. Rules and AI results can trigger quarantine, link deactivation or bot protection; burst-rate checks can also deactivate links. To contest a decision, use the spam-appeal form when signed in or contact abuse@ternis.dev for human review. Human verification does not certify a destination as safe.

**Campaign and optional visitor attribution.** Redirect analytics can retain bounded query-parameter values and click tags supplied in a URL or request headers. Optional visitor identifiers are processed when attribution is enabled, with supported identifiers pseudonymized before storage and DNT/GPC controls applied by that feature. Query parameters and referrers can still contain personal information; hashing an identifier does not make all request data anonymous.

**Accounts.** If you sign in with Ternis Auth SSO we store your SSO subject ID, name, email address, avatar URL and role/plan. There are no passwords — authentication happens at the SSO provider.

**Sessions and errors.** Login sessions and a minimal error log (no raw IPs — only hashes) to keep the service running and debuggable.

**Bot protection.** Guest forms and protected-link verification can use Cloudflare Turnstile, including on protected.ternis.link. Cloudflare processes signals such as the visitor IP address, TLS fingerprint, user agent, site key, and page origin to distinguish people from bots. We validate Turnstile's short-lived, single-use token on the server; we do not retain that token.

## What we never store in plain text

Application IP-capture fields store hashes or encrypted addresses rather than readable IP addresses. IP addresses are necessarily processed to deliver network requests; infrastructure and external providers process technical connection data under their applicable arrangements. Day-to-day features (quotas, unique counts, rate limits) run on **SHA-256 hashes, optionally mixed with a server-side secret**. Raw visitor/creator IPs exist only in encrypted form for the 30-day abuse window described above.

## Cookies

We use strictly necessary cookies only: the login session and framework security tokens (CSRF). Protected-link checks can also set an encrypted, necessary human-verification cookie, normally valid for two hours. Browser storage can remember interface preferences such as color theme. We do not use advertising cookies or cookies for cross-site audience profiling. The external Turnstile widget is also subject to Cloudflare's [Turnstile Privacy Addendum](https://www.cloudflare.com/turnstile-privacy-policy/).

## Legal basis (GDPR)

- **Contract / requested service** (Art. 6(1)(b)): creating and resolving short links, your account.
- **Legitimate interests** (Art. 6(1)(f)): abuse prevention, security, capacity planning. We weighed this against your privacy by hashing IPs by default and encrypting plus auto-deleting the rest.
- **Consent** (Art. 6(1)(a)): where a feature explicitly requests it; it can be withdrawn for future processing.

## Retention

| Data | Kept for |
|---|---|
| Links and their settings | While the link is retained; deactivation does not delete the link or its analytics. Hard deletion can preserve aggregate tombstones without the destination URL |
| Encrypted visitor/creator IPs | **30 days**, then automatically and irreversibly deleted |
| API request log | No automatic deletion currently configured; subject to individual rights requests and any specifically justified retention |
| Activity history (your actions + admin actions on your stuff) | **Indefinitely** — never deleted automatically. Legal basis: legitimate interest in security, abuse prevention and transparency (Art. 6(1)(f)); only IP hashes (never raw addresses), truncated user-agents and labels are stored. Individual erasure requests (Art. 17) are honored on request — contact legal@ternis.dev |
| API-key attribution on links | As long as the link exists (survives key revocation; cleared only if the key row itself is deleted) |
| Security evaluations and appeals | No automatic deletion currently configured; retained for moderation and abuse review, subject to applicable rights and justified retention |
| Public-dashboard API session tokens | At most five active tokens per account; expired/revoked token records are pruned after 30 days |
| Generated account exports | Available for seven days |
| IP hashes and click records | May remain with historical analytics after encrypted IPs are pruned; hashes are pseudonymous, not necessarily anonymous |
| Accounts | Until you stop using SSO sign-in and ask for deletion |
| Error log entries | No automatic deletion schedule currently configured; subject to applicable rights and justified retention |

## Sharing

We do not sell data and share nothing for marketing. Data is processed on infrastructure operated by ternis.net. Cloudflare processes bot-protection signals under its Turnstile terms. TypeSafe AI receives the security inputs described above when AI checks are enabled. External processing is not limited to our own infrastructure. Where processing involves transfers outside the EEA, applicable transfer requirements and safeguards must be addressed; contact us for the arrangements applicable to your data. Abuse cases (spam, phishing, attacks launched through short links) may be reported to providers or authorities with the minimum data required.

## Your rights

Access, rectification, erasure, restriction, portability and objection (GDPR Art. 15–21), plus the right to complain to your supervisory authority. Self-service lives in your [dashboard settings](https://dash.ternis.link/settings): request a self-service export of your account data, or schedule complete account erasure (14-day grace period, cancellable, requires a fresh sign-in). Because guest links carry no account, include the short URL(s) in erasure requests so we can find them. These rights also apply to security and API logs. Any refusal or restriction must have an applicable legal basis, which we will explain. Where processing relies on consent, you can withdraw it; where it relies on legitimate interests, you can object. Contact: [legal@ternis.dev](mailto:legal@ternis.dev) or [platforms@ternis.dev](mailto:platforms@ternis.dev). Abuse reports: [abuse@ternis.dev](mailto:abuse@ternis.dev).

## Public statistics and destination websites

Public network statistics expose aggregate counts rather than individual links, account details, IPs or user-agents. Link owners can access analytics for their own links. After a redirect, the destination operator is responsible for its own collection, cookies and content.

## Changes

Material changes will be announced on the service at least 14 days before they take effect. The date at the top shows the current version.
