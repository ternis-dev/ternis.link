import Foundation

/// Build-time endpoints and provider registration. API base mirrors the
/// Chrome extension default; SSO talks to the provider directly (see plan
/// §10) using the desktop public client + PKCE — no server changes needed.
public enum AppConfig: Sendable {
    public static let apiBase = URL(string: "https://links.t-api.de/v1")!
    public static let providerBase = URL(string: "https://auth.ternis.net")!

    /// Major API version this client understands (checked against
    /// `GET /v1/` metadata before first use).
    public static let minAPIMajorVersion = 1

    /// Public OAuth client registered at the provider for this app.
    public static let ssoClientID = "ternis-link-macos"

    /// Private-use redirect URI (Info.plist `CFBundleURLSchemes` + provider
    /// allowlist must both contain the `ternislink` scheme).
    public static let ssoRedirectURI = "ternislink://oauth/callback"

    public static let ssoScopes = "openid profile email ternis:sso"
}
