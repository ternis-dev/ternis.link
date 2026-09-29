---
title: Why your business needs separate domains for transactional and public links
date: 2026-09-29
description: Preserve domain reputation and avoid spam filters by isolating verified business redirects from casual public shorteners.
---

When automated billing systems send invoice PDFs, password reset emails, or delivery notifications, deliverability depends heavily on the reputation of every link inside the message. Spam filters inspect every hyperlink: if a single URL in your email points to a domain frequently abused by spammers or phishing campaigns, your message lands straight in the junk folder.

This is why sharing a single generic shortener domain between casual public links and mission-critical business communications is a massive risk.

## The domain reputation trap

Open public shorteners allow anonymous guests to shorten links without creating an account. While rate limits, Turnstile challenges, and junk scanners prevent overt abuse, security scanners and anti-spam heuristics still classify open shortener domains as high-risk by nature.

If you paste an open shortener link inside a transactional email sent to a corporate inbox, corporate gateway filters (such as Microsoft Defender for Office 365 or Google Workspace Protection) may quarantine the email immediately.

## Multi-domain architecture to the rescue

Our network is deliberately partitioned into distinct domain roles to isolate trust tiers:

1. **Public guest shortening**: [href.nz](https://href.nz) (English) and [meinlink.at](https://meinlink.at) (German) serve open guest links with fair-use daily quotas and automated safety checks.
2. **Official corporate & transactional redirects**: [href.re](https://href.re) is reserved strictly for authenticated business links. Public guest shortening is completely disabled. Only authenticated team members and provisioned API keys can issue redirects on this host.
3. **Personal and partner branding**: [ternis.link](https://ternis.link) provides curated short links for inner-circle operations, documentation, and network statistics.
4. **Custom branded domains**: Teams on paid plans can [bring their own domain](https://ternis.link/pages/blog/2026-09-28-bring-your-own-domain) (such as `go.yourcompany.com`) with DNS TXT verification, completely isolating their branding and link reputation from external traffic.

## Best practices for transactional messaging

When provisioning short links for automated systems:

- **Use href.re or a verified custom domain** for all transactional notifications, PDF invoices, and client portals.
- **Isolate integrations with dedicated API keys**: Create [one API key per integration](https://ternis.link/pages/blog/2026-09-29-one-api-key-per-integration) so that billing jobs, notification workers, and CI pipelines have distinct audit trails.
- **Leverage semantic custom slugs**: Use readable slugs like `href.re/inv-2026-1042` that give recipients immediate confidence before they click.

By matching the right host to the right message, you maintain pristine sender reputation and guarantee that critical transactional links reach their recipients.
