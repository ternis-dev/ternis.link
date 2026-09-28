import Foundation

/// Endpoint wrappers over `APIClient`. Auth-agnostic: the caller (ultimately
/// `AuthManager`) supplies the Bearer token, so 401-refresh-retry stays in
/// one place.
public struct LinksAPI: Sendable {
    public let client: APIClient

    public init(client: APIClient = APIClient()) {
        self.client = client
    }

    // MARK: - Version gate

    public func version() async throws -> VersionMetadata {
        try await client.get("/", token: "")
    }

    // MARK: - Links

    public func listLinks(page: Int = 1, token: String) async throws -> Paged<APILink> {
        try await client.get("/links?page=\(page)", token: token)
    }

    public func createLink(_ request: CreateLinkRequest, token: String) async throws -> APILink {
        try await client.send(path: "/links", method: "POST", token: token, body: request)
    }

    public func getLink(id: String, token: String) async throws -> APILink {
        try await client.get("/links/\(id)", token: token)
    }

    public func deleteLink(id: String, token: String) async throws {
        let _: EmptyResponse = try await client.send(path: "/links/\(id)", method: "DELETE", token: token, body: nil as Data?)
    }

    public func clickSummary(linkID: String, token: String) async throws -> ClickSummary {
        try await client.get("/links/\(linkID)/clicks/summary", token: token)
    }

    /// QR PNG bytes for a short URL (public endpoint, no auth needed).
    public func qrPNG(for shortUrl: URL) async throws -> Data {
        let (data, http) = try await client.raw(
            path: "/qr",
            query: [
                URLQueryItem(name: "url", value: shortUrl.absoluteString),
                URLQueryItem(name: "format", value: "png"),
            ],
            token: nil
        )
        guard http.statusCode == 200 else {
            throw APIClient.mapError(status: http.statusCode, headers: http.allHeaderFields, data: data)
        }
        return data
    }

    // MARK: - Domains

    public func listDomains(token: String) async throws -> Paged<APIDomain> {
        try await client.get("/domains", token: token)
    }

    public func registerDomain(hostname: String, token: String) async throws -> APIDomain {
        struct Body: Encodable { let hostname: String }
        return try await client.send(path: "/domains", method: "POST", token: token, body: Body(hostname: hostname))
    }

    public func verifyDomain(id: String, token: String) async throws -> APIDomain {
        struct Body: Encodable {}
        struct Response: Decodable {
            let verified: Bool
            let domain: APIDomain?
        }
        let response: Response = try await client.send(path: "/domains/\(id)/verify", method: "POST", token: token, body: Body())
        guard let domain = response.domain else {
            throw APIError.server(status: 200, message: "Domain not verified yet.")
        }
        return domain
    }
}

struct EmptyResponse: Decodable {}
