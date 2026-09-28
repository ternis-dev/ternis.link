import Foundation

/// Owns the credential: loads/saves Keychain, hands `APIClient` a Bearer
/// token, and heals expired SSO sessions (refresh → retry once, else sign
/// out — the desktop twin of `RefreshSsoToken`).
public actor AuthManager {
    public private(set) var credential: Credential?

    private let sso: SSOAuthorizer

    public init(sso: SSOAuthorizer = SSOAuthorizer()) {
        self.sso = sso
        if let stored = try? KeychainStore.load() {
            self.credential = stored
        }
    }

    public var isSignedIn: Bool { credential != nil }

    public func signInWithAPIKey(_ key: String) throws {
        let trimmed = key.trimmingCharacters(in: .whitespacesAndNewlines)
        guard trimmed.hasPrefix("tl_"), trimmed.count > 4 else {
            throw SignInError.invalidKey
        }
        let credential = Credential.apiKey(trimmed)
        try KeychainStore.save(credential)
        self.credential = credential
    }

    public func signInWithTokens(_ tokens: TokenSet) throws {
        let credential = Credential.sso(tokens)
        try KeychainStore.save(credential)
        self.credential = credential
    }

    public func signOut() throws {
        try KeychainStore.delete()
        credential = nil
    }

    /// Bearer token for the next request, refreshing SSO tokens when needed.
    /// Returns nil when signed out or the refresh fails (caller signs out).
    public func bearer() async -> String? {
        guard let credential else { return nil }
        if let fresh = credential.bearerIfFresh { return fresh }

        guard case .sso(let tokens) = credential else { return nil }
        do {
            let renewed = try await sso.refresh(tokens)
            let updated = Credential.sso(renewed)
            try KeychainStore.save(updated)
            self.credential = updated
            return renewed.accessToken
        } catch {
            return nil
        }
    }

    public enum SignInError: Error {
        case invalidKey
    }
}
