# Browser Extension (v{{ $version }})

Shorten any tab in one click: toolbar popup, right-click menu, or omnibox (`tl` + Space).

- Download: [{{ $downloadUrl }}]({{ $downloadUrl }})@if(!$downloadReady) (build pending — run `php artisan extension:build`)@endif
- Version JSON: [/pages/extension/version](/pages/extension/version)
- Docs: https://docs.ternis.link/extension

## Install

1. Download + unzip the build.
2. Open `chrome://extensions`, enable Developer mode, Load unpacked → the folder.
3. Optional: pin to the toolbar.
4. Optional: add an API key (`tl_…` from dash.ternis.link/api-keys) in the extension settings for custom slugs + your domains. Without a key: guest mode (href.nz, auto codes, 50/day).

## Permissions

storage (key + history, on-device), activeTab (current URL on click), contextMenus (right-click shorten), notifications (confirm menu shortens), host links.t-api.de (the API — keys go here only).
