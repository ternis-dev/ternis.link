import Foundation

/// Account endpoints backing M3: API keys, notifications, activity,
/// settings. Same auth pattern as `LinksAPI` — the caller supplies
/// the Bearer token so 401-refresh-retry stays in `AuthManager`.
public struct AccountAPI: Sendable {
    public let client: APIClient

    public init(client: APIClient = APIClient()) {
        self.client = client
    }

    // MARK: - API keys

    public func listKeys(token: String) async throws -> Paged<APIKey> {
        try await client.get("/api-keys", token: token)
    }

    public func createKey(name: String, token: String) async throws -> APIKey {
        struct Body: Encodable { let name: String }
        return try await client.send(path: "/api-keys", method: "POST", token: token, body: Body(name: name))
    }

    public func revokeKey(id: String, token: String) async throws {
        try await client.sendNoContent(path: "/api-keys/\(id)", method: "DELETE", token: token)
    }

    // MARK: - Notifications

    public func listNotifications(token: String) async throws -> Paged<AppNotification> {
        try await client.get("/notifications", token: token)
    }

    public func markNotificationRead(id: String, token: String) async throws -> AppNotification {
        struct Body: Encodable {}
        return try await client.send(path: "/notifications/\(id)/read", method: "POST", token: token, body: Body())
    }

    public func markAllNotificationsRead(token: String) async throws {
        struct Body: Encodable {}
        struct Response: Decodable { let message: String? }
        let _: Response = try await client.send(path: "/notifications/read", method: "POST", token: token, body: Body())
    }

    // MARK: - Activity

    public func listActivity(token: String) async throws -> Paged<ActivityEntry> {
        try await client.get("/activity", token: token)
    }

    // MARK: - Settings

    public func getSettings(token: String) async throws -> UserSettings {
        try await client.get("/settings", token: token)
    }

    public func updateSettings(_ patch: SettingsPatch, token: String) async throws -> UserSettings {
        try await client.send(path: "/settings", method: "PATCH", token: token, body: patch)
    }

    // MARK: - Domains (delete was missing from LinksAPI)

    public func deleteDomain(id: String, token: String) async throws {
        try await client.sendNoContent(path: "/domains/\(id)", method: "DELETE", token: token)
    }
}
