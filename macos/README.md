# ternis.link for Mac

Native SwiftUI desktop client (menu-bar quick-shorten + dashboard), thin
client over `https://links.t-api.de/v1`. See
[`../.plans/20260928T175229_macos-app-plan.md`](../.plans/20260928T175229_macos-app-plan.md).

## Build

```bash
cd macos
swift build        # library + app (works with CLT-only toolchain)
swift test         # TernisLinkCoreTests (pure logic; Keychain covered by manual QA)
```

Open the folder in Xcode for Previews and archiving (full Xcode required).

## Configure

- **API key mode**: paste a `tl_…` key (from `dash.ternis.link/api-keys`).
  Stored in Keychain, sent as `Bearer` only to the API base.
- **SSO mode**: register the desktop public client at the provider, set
  `AppConfig.ssoClientID`, allowlist `ternislink://oauth/callback`, and add
  the `ternislink` scheme to the app bundle (`CFBundleURLSchemes` — wired
  by `Scripts/package.sh` in M4).

## Layout

- `Sources/TernisLinkCore` — testable library: `APIClient`, `LinksAPI`,
  `Auth/` (`Credential`, `AuthManager`, `SSOAuthorizer`, `PKCE`,
  `KeychainStore`), models, typed `APIError`.
- `Sources/TernisLinkApp` — `@main` App: MenuBarExtra + status window (M0).
- `Tests/` — swift-testing suites for PKCE vectors, error mapping, models.
