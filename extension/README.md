# ternis.link — Chrome Extension

Vanilla Manifest V3 extension, no build step. Ships inside this monorepo under
`extension/`; the Laravel app serves the download page at
`https://ternis.link/pages/extension`.

## What it does

- Popup shortens the active tab: guest mode (`POST /v1/links/public` → `href.nz`)
  or authenticated mode (`POST /v1/links` with a `tl_…` API key, domain picker +
  optional custom slug).
- Right-click any page/link/selection → **Shorten with ternis.link**.
- Omnibox: type `tl <long-url>` + Enter to shorten.
- QR preview via `GET /v1/qr?url=…`, copy-to-clipboard, last-10 history.
- Settings (options page): API key, API base (default `https://links.t-api.de/v1`).

API keys are created at `dash.ternis.link/api-keys` and stored only in
`chrome.storage.sync` — never sent anywhere except the configured API base.

## Develop

1. Open `chrome://extensions`, enable **Developer mode**.
2. **Load unpacked** → select this `extension/` folder.
3. Click the toolbar icon, open settings (⚙), paste an API key for full
   features or use guest mode as-is.

## Package

```bash
php artisan extension:build     # validates manifest, writes public/extension/*.zip
php artisan extension:build --print  # show version + output path only
```

The zip excludes dev-only files and is what `/pages/extension/download`
serves. Version is the single source of truth in `manifest.json`.

## Repo layout (semi-monorepo)

- `/` — Laravel web app (Blade + Livewire, Vite).
- `/extension` — this Chrome extension (vanilla JS/CSS, zero deps).
- `/docs/extension.md` — user + API notes rendered on `docs.ternis.link`.
- `ExtensionController` + `resources/views/pages/extension/*` — download page.
