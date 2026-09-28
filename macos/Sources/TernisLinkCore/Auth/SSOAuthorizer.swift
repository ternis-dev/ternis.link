import AuthenticationServices
import Foundation

/// SSO sign-in against the provider directly (plan §10): system browser via
/// `ASWebAuthenticationSession`, private-use scheme callback, code exchange,
/// refresh. The caller supplies presentation UI; this type owns the flow.
public final class SSOAuthorizer: NSObject, Sendable {
    public enum Failure: Error, Sendable {
        case cancelled
        case callback(String)
        case tokenExchange(String)
        case noRefreshToken
    }

    private let clientID: String
    private let redirectURI: String
    private let scopes: String
    private let session: URLSession

    public init(
        clientID: String = AppConfig.ssoClientID,
        redirectURI: String = AppConfig.ssoRedirectURI,
        scopes: String = AppConfig.ssoScopes,
        session: URLSession = .shared
    ) {
        self.clientID = clientID
        self.redirectURI = redirectURI
        self.scopes = scopes
        self.session = session
    }

    /// Runs the browser flow and returns fresh tokens. Throws
    /// `.cancelled` when the user dismisses the browser.
    @MainActor
    public func authorize(presentation: ASWebAuthenticationPresentationContextProviding) async throws -> TokenSet {
        let verifier = PKCE.makeVerifier()
        var components = URLComponents(url: AppConfig.providerBase.appending(path: "oauth/authorize"), resolvingAgainstBaseURL: false)!
        components.queryItems = [
            URLQueryItem(name: "response_type", value: "code"),
            URLQueryItem(name: "client_id", value: clientID),
            URLQueryItem(name: "redirect_uri", value: redirectURI),
            URLQueryItem(name: "scope", value: scopes),
            URLQueryItem(name: "code_challenge", value: PKCE.challenge(for: verifier)),
            URLQueryItem(name: "code_challenge_method", value: "S256"),
        ]
        guard let authURL = components.url else {
            throw Failure.callback("Could not build the sign-in URL.")
        }

        let callbackURL = try await withCheckedThrowingContinuation { continuation in
            let authSession = ASWebAuthenticationSession(
                url: authURL,
                callbackURLScheme: Self.scheme(of: self.redirectURI)
            ) { url, error in
                if let url {
                    continuation.resume(returning: url)
                } else if let sessionError = error as? ASWebAuthenticationSessionError,
                          sessionError.code == .canceledLogin {
                    continuation.resume(throwing: Failure.cancelled)
                } else {
                    continuation.resume(throwing: Failure.callback(error?.localizedDescription ?? "Sign-in failed."))
                }
            }
            authSession.presentationContextProvider = presentation
            authSession.prefersEphemeralWebBrowserSession = false
            if !authSession.start() {
                continuation.resume(throwing: Failure.callback("Could not open the browser."))
            }
        }

        guard let code = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false)?
            .queryItems?.first(where: { $0.name == "code" })?.value,
            !code.isEmpty
        else {
            throw Failure.callback("Sign-in returned no code.")
        }

        return try await exchange(code: code, verifier: verifier)
    }

    public func refresh(_ tokens: TokenSet) async throws -> TokenSet {
        guard let refreshToken = tokens.refreshToken else {
            throw Failure.noRefreshToken
        }
        var request = URLRequest(url: AppConfig.providerBase.appending(path: "oauth/token"))
        request.httpMethod = "POST"
        request.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")
        var components = URLComponents()
        components.queryItems = [
            URLQueryItem(name: "grant_type", value: "refresh_token"),
            URLQueryItem(name: "client_id", value: clientID),
            URLQueryItem(name: "refresh_token", value: refreshToken),
        ]
        request.httpBody = components.percentEncodedQuery?.data(using: .utf8)

        let (data, response) = try await session.data(for: request)
        let status = (response as? HTTPURLResponse)?.statusCode ?? -1
        guard status == 200 else {
            throw Failure.tokenExchange("The sign-in session expired (provider said \(status)).")
        }
        return try decodeTokens(from: data, fallbackRefresh: refreshToken)
    }

    // MARK: - Private

    private func exchange(code: String, verifier: String) async throws -> TokenSet {
        var request = URLRequest(url: AppConfig.providerBase.appending(path: "oauth/token"))
        request.httpMethod = "POST"
        request.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")
        var components = URLComponents()
        components.queryItems = [
            URLQueryItem(name: "grant_type", value: "authorization_code"),
            URLQueryItem(name: "client_id", value: clientID),
            URLQueryItem(name: "redirect_uri", value: redirectURI),
            URLQueryItem(name: "code", value: code),
            URLQueryItem(name: "code_verifier", value: verifier),
        ]
        request.httpBody = components.percentEncodedQuery?.data(using: .utf8)

        let (data, response) = try await session.data(for: request)
        let status = (response as? HTTPURLResponse)?.statusCode ?? -1
        guard status == 200 else {
            throw Failure.tokenExchange("The provider refused the sign-in (said \(status) — is this app's client registered with that redirect URI?).")
        }
        return try decodeTokens(from: data, fallbackRefresh: nil)
    }

    private func decodeTokens(from data: Data, fallbackRefresh: String?) throws -> TokenSet {
        struct Payload: Decodable {
            let accessToken: String
            let refreshToken: String?
            let expiresIn: Int?
        }
        do {
            let payload = try JSONDecoder.api.decode(Payload.self, from: data)
            return TokenSet(
                accessToken: payload.accessToken,
                refreshToken: payload.refreshToken ?? fallbackRefresh,
                expiresIn: payload.expiresIn ?? 3600
            )
        } catch {
            throw Failure.tokenExchange("Could not read the sign-in response.")
        }
    }

    static func scheme(of redirectURI: String) -> String {
        URL(string: redirectURI)?.scheme ?? "ternislink"
    }
}
