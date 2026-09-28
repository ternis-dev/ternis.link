import Foundation

/// What the app authenticates API calls with. API keys (`tl_…`) are
/// long-lived; SSO tokens expire and refresh via the provider.
public enum Credential: Codable, Sendable {
    case apiKey(String)
    case sso(TokenSet)

    /// The Bearer value for the next request, or nil when an SSO token
    /// needs a refresh first.
    public var bearerIfFresh: String? {
        switch self {
        case .apiKey(let key):
            return key
        case .sso(let tokens):
            return tokens.isExpired ? nil : tokens.accessToken
        }
    }
}
