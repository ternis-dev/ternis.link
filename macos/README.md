# ternis.link for Mac

Native SwiftUI desktop client (dashboard window + hotkey quick-shortener), thin
client over `https://links.t-api.de/v1`. See
[`../.plans/20260928T175229_macos-app-plan.md`](../.plans/20260928T175229_macos-app-plan.md).

## Build & run

```bash
cd macos
swift build        # library + app (works with CLT-only toolchain)
swift test         # TernisLinkCoreTests (pure logic; Keychain covered by manual QA)
./Scripts/bundle.sh [--release]   # assemble dist/TernisLink.app (signed ad-hoc)
open dist/TernisLink.app
```

Always run the **bundled app**, never the raw `swift run` binary: without a
bundle + Info.plist, macOS never properly activates the process — key
events don't reach windows (can't type) and there's no Edit menu (can't
paste). The bundle also registers the `ternislink://` scheme needed for SSO.

Open the folder in Xcode for Previews and archiving (full Xcode required).

## Configure

- **API key mode**: paste a `tl_…` key (from `dash.ternis.link/api-keys`).
  Stored in Keychain, sent as `Bearer` only to the API base.
- **SSO mode**: register a public client at the provider (PKCE, grants
  `authorization_code` + `refresh_token`) with redirect URI
  `ternislink://oauth/callback`, set `AppConfig.ssoClientID` to match.
  Until then the SSO button reports the provider's refusal verbatim.

## Layout

- `Sources/TernisLinkCore` — testable library: `APIClient`, `LinksAPI`,
  `AccountAPI` (keys, notifications, activity, settings, domain delete),
  `Auth/` (`Credential`, `AuthManager`, `SSOAuthorizer`, `PKCE`,
  `KeychainStore`), `Persistence/HistoryStore`, models, typed `APIError`.
- `Sources/TernisLinkApp` — `@main` App: onboarding (key + SSO),
  dashboard shell (New Link, Links, API Keys, Domains, Activity,
  Notifications; `⌘1…⌘6`), quick-shortener (clipboard prefill,
  domain picker, QR preview, history), link detail (facts, Swift Charts
  analytics, QR copy/save, deactivate), domains (register/verify/remove),
  API keys (create copy-once/revoke), notifications (mark read),
  activity feed, Settings (theme/layout + email prefs via API),
  `⌃⌥⌘L` global hotkey.
- `Tests/` — swift-testing suites for PKCE vectors, error mapping, models,
  auth validation, history persistence.
