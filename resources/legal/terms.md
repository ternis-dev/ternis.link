# Terms of Service

> Last updated: 2026-11-03

These terms govern use of the Ternis link services described below. By creating links, managing an account or using our APIs, you agree to these terms. Mandatory rights remain unaffected.

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
| Documentation, abuse reporting and security verification | docs.ternis.link, protected.ternis.link, abuse.ternis.link |

It also covers supported subdomains and customer-connected domains when they serve this application. Independent destination websites and other services linked from this application have their own policies. Account sign-in uses Ternis Auth at auth.ternis.net; its own notices also apply to authentication.

## The service

ternis.link shortens long URLs into short links, with optional custom slugs, descriptions, tags, analytics and custom domains. The network also provides QR generation, bio pages, link groups, device targeting, password protection and APIs where available. Guest use needs no account and is subject to fair-use limits; current account and API quotas depend on the plan and endpoint. Protect your account, API keys and link passwords, and revoke credentials you suspect are compromised.

## Acceptable use

You may not use the service for:

- phishing, fraud, malware distribution or any unlawful purpose;
- spam or unsolicited bulk messaging;
- probing or attacking the service itself — **automated vulnerability scanning, credential stuffing and similar traffic is forbidden** and scanner-shaped destinations are rejected automatically;
- content that infringes intellectual property or personality rights;
- circumventing quotas, rate limits or access controls.

## Enforcement

We may deactivate or delete links, throttle or block accounts/IP ranges, and report severe abuse — without prior notice where delay would cause harm. Deactivated links keep their analytics; deleted data follows the [Privacy Policy](privacy).

## Automated protection, bot mitigation and appeals

We use automated rules, traffic-rate screening, bot mitigation systems, and, where enabled, AI to assess link destinations, request velocity, and client signatures. These checks can quarantine or deactivate links, enable bot protection, or require a verification challenge (via Cloudflare Turnstile or our self-hosted, privacy-preserving arithmetic CAPTCHA) through protected.ternis.link.

- **Link and Group Protection Policies:** Link creators and group owners can configure protection strength (`off`, `standard`, `strict`, or `custom_captcha`). Child links inherit group protection settings unless specifically overridden.
- **Automated Scraping and Client Screening:** Automated scraping tools, headless browsers, and command-line clients (e.g. cURL, Python requests, Scrapy) attempting to harvest short links or public resources (such as legal and policy documentation) are screened by verification gates.
- **Automated Bot Exemption (`allow_bots`):** Legitimate automated integrators (e.g. RSS readers, webhooks, uptime monitors, API consumers) can be exempted by setting `allow_bots = true` on the target link.
- **Appeals and False Positives:** Security classifications can be incorrect. You can request human review through the signed-in spam-appeal form or contact [abuse@ternis.dev](mailto:abuse@ternis.dev) with the affected URL and context. Security checks and verification confirm client integrity only and do not certify a destination as safe.

## Shared links and analytics

Only submit content and destinations you are entitled to use. Do not place passwords, access tokens or unnecessary personal data in public URLs or QR codes. QR codes can be copied and short links can be shared by recipients; link-level controls do not prevent copying content after access. Link owners remain responsible for their destination content and any privacy notices or permissions required for their use of analytics. Public network statistics contain aggregate data.

## Availability

A best-effort service without uptime guarantees. Expired, removed or quarantined links may show an error, warning or verification page instead of redirecting. We may change quotas, limits and features without prior notice.

## Liability

To the extent permitted by law, liability is limited to intent and gross negligence; mandatory consumer and product-liability rights are unaffected. You are responsible for the destinations you share.

## Changes and contact

We may update these terms without any notice on the service. Questions or legal inquiries: [legal@ternis.dev](mailto:legal@ternis.dev) or [platforms@ternis.dev](mailto:platforms@ternis.dev). Abuse reports: [abuse@ternis.dev](mailto:abuse@ternis.dev).
