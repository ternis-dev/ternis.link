---
title: my.href.nz gets its own look, login, and chart palette
date: 2026-10-05
description: Fresh-minimal public theme (paper canvas, indigo accents, hero band, pill nav), themed my.href.nz login, indigo analytics charts, and a dash banner pointing at public links.
---

The public dashboard no longer borrows the dash wardrobe. **my.href.nz** now renders a fresh-minimal theme of its own:

- **Paper canvas + indigo accents** — warm paper background, gradient hero band with greeting and domain chips, pill navigation, gradient lead stat, rounded cards, pill buttons, and restyled tables, forms, tags, pagination, and modals, in light and dark modes alike.
- **Themed login** — `my.href.nz/login` gets a matching page that starts SSO on the same host (no cross-host hop) and points newsletter/family users at `dash.ternis.link`.
- **Indigo analytics** — click charts and browser doughnuts paint in the indigo family under the public theme; dash and admin charts stay grayscale.
- **Dash cross-link banner** — `dash.ternis.link` overview and links pages now note how many of your links live on the public dashboard, with a direct link over.

Implementation notes: shared Livewire components take a `theme` prop that switches route prefixing (`public-dashboard.*` vs `dashboard.*`); all theme CSS is scoped under `.pd` so dash/admin markup is untouched.
