---
title: href.nz gets its own sketch-styled login page
date: 2026-09-26
description: Members arriving at href.nz/login now get a taped-card login page in the landing's look instead of a bare redirect.
---

`href.nz/login` used to bounce straight to the dashboard login with no explanation. Now it serves a login card in the sketchbook style of the landing page: what members get (custom slugs, shorter links, click stats), how the three-step sign-in works, and why the button hands over to the dashboard host (login sessions can't cross domains — the SSO handshake with PKCE has to start and finish where the session lives).

Notes for the curious:

- The button links to the dashboard SSO start; no credentials are ever entered on href.nz itself.
- Signed-in members hitting the page bounce straight to their dashboard.
- The page is `noindex, nofollow` and excluded from the sitemap — login pages are not content.
- Other short-link hosts (`href.re`, `ternis.link`) keep the plain redirect; only the public guest brand gets the full card.
