import Foundation
import TernisLinkCore

/// UI-facing session: owns `AuthManager` + `HistoryStore`, exposes sign-in
/// state, domains, and the shorten call with 401-eviction (dead credential
/// signs out instead of error-looping).
@Observable @MainActor
final class SessionStore {
    enum State: Equatable {
        case checking
        case signedOut
        case signedIn
    }

    private(set) var state: State = .checking
    private(set) var domains: [APIDomain] = []
    var selectedDomainID: String?
    private(set) var serverWarning: String?

    let history = HistoryStore()
    let auth = AuthManager()
    private let api = LinksAPI()

    func refresh() async {
        if await auth.credential != nil {
            state = .signedIn
            await loadDomains()
        } else {
            state = .signedOut
        }
        await checkVersion()
    }

    func signInWithAPIKey(_ key: String) async throws {
        try await auth.signInWithAPIKey(key)
        state = .signedIn
        await loadDomains()
    }

    func signInWithSSO(tokens: TokenSet) async throws {
        try await auth.signInWithTokens(tokens)
        state = .signedIn
        await loadDomains()
    }

    func signOut() async {
        try? await auth.signOut()
        domains = []
        selectedDomainID = nil
        state = .signedOut
    }

    func shorten(destination: String, slug: String?) async throws -> APILink {
        guard let token = await auth.bearer() else {
            await signOut()
            throw APIError.unauthorized("Signed out.")
        }
        let request = CreateLinkRequest(
            destinationUrl: destination,
            domainId: selectedDomainID ?? "",
            slug: slug
        )
        do {
            let link = try await api.createLink(request, token: token)
            if let short = link.shortUrl?.absoluteString {
                history.add(shortUrl: short, destination: destination)
            }
            return link
        } catch APIError.unauthorized {
            await signOut()
            throw APIError.unauthorized("Signed out — please sign in again.")
        }
    }

    func loadDomains() async {
        guard let token = await auth.bearer() else {
            await signOut()
            return
        }
        do {
            let page = try await api.listDomains(token: token)
            domains = page.data
            if selectedDomainID == nil {
                selectedDomainID = UserDefaults.standard.string(forKey: "lastDomainID")
                    .flatMap { id in page.data.contains(where: { $0.id == id }) ? id : nil }
                    ?? page.data.first(where: { $0.hostname == "href.nz" })?.id
                    ?? page.data.first?.id
            }
        } catch APIError.unauthorized {
            await signOut()
        } catch {
            // Domains stay empty; the shorten form surfaces APIError.userMessage.
        }
    }

    func rememberDomain(_ id: String) {
        selectedDomainID = id
        UserDefaults.standard.set(id, forKey: "lastDomainID")
    }

    private func checkVersion() async {
        do {
            let meta = try await api.version()
            serverWarning = meta.version < AppConfig.minAPIMajorVersion
                ? "Server API v\(meta.version) predates what this app supports."
                : nil
        } catch {
            serverWarning = nil
        }
    }
}
