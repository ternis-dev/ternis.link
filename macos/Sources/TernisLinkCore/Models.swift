import Foundation

// MARK: - Shared decoding

extension JSONDecoder {
    static var api: JSONDecoder {
        let decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        decoder.dateDecodingStrategy = .iso8601
        return decoder
    }
}

extension JSONEncoder {
    static var api: JSONEncoder {
        let encoder = JSONEncoder()
        encoder.keyEncodingStrategy = .convertToSnakeCase
        encoder.dateEncodingStrategy = .iso8601
        return encoder
    }
}

// MARK: - Links & domains

public struct APIDomain: Codable, Sendable, Identifiable, Hashable {
    public let id: String
    public let hostname: String
    public let userId: String?
    public let isActive: Bool?
    public let verifiedAt: Date?

    public var isSystem: Bool { userId == nil }
}

public struct APILink: Codable, Sendable, Identifiable, Hashable {
    public let id: String
    public let slug: String
    public let destinationUrl: String
    public let description: String?
    public let tags: [String]?
    public let isActive: Bool?
    public let expiresAt: Date?
    public let domain: APIDomain?

    public var shortUrl: URL? {
        guard let host = domain?.hostname else { return nil }
        return URL(string: "https://\(host)/\(slug)")
    }
}

public struct Paged<T: Codable & Sendable>: Codable, Sendable {
    public let data: [T]
    public let currentPage: Int
    public let lastPage: Int
    public let total: Int
}

public struct ClickSummary: Codable, Sendable {
    public let totalClicks: Int
    public let uniqueVisitors: Int
}

// MARK: - Version metadata (GET /v1/)

public struct VersionMetadata: Codable, Sendable {
    public let version: Int
    public let status: String
    public let latestVersion: Int
}

// MARK: - Auth

/// Token set from the provider (SSO) — stored in Keychain, never UserDefaults.
public struct TokenSet: Codable, Sendable {
    public let accessToken: String
    public let refreshToken: String?
    public let expiresAt: Date

    public init(accessToken: String, refreshToken: String?, expiresIn: Int) {
        self.accessToken = accessToken
        self.refreshToken = refreshToken
        self.expiresAt = Date().addingTimeInterval(TimeInterval(expiresIn))
    }

    public var isExpired: Bool { expiresAt <= Date() }
}

public struct CreateLinkRequest: Codable, Sendable {
    public let destinationUrl: String
    public let domainId: String
    public let slug: String?

    public init(destinationUrl: String, domainId: String, slug: String? = nil) {
        self.destinationUrl = destinationUrl
        self.domainId = domainId
        self.slug = slug.flatMap { $0.isEmpty ? nil : $0 }
    }
}
