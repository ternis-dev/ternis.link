import Foundation

// MARK: - Shared decoding

extension JSONDecoder {
    static var api: JSONDecoder {
        let decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        // Laravel serializes datetimes with fractional seconds
        // (2026-09-20T10:00:00.000000Z), which .iso8601 chokes on.
        // Formatters are created per call: ISO8601DateFormatter is not
        // Sendable, so no shared static under Swift 6.
        decoder.dateDecodingStrategy = .custom { decoder in
            let container = try decoder.singleValueContainer()
            let string = try container.decode(String.self)
            let fractional = ISO8601DateFormatter()
            fractional.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
            if let date = fractional.date(from: string)
                ?? ISO8601DateFormatter().date(from: string) {
                return date
            }
            throw DecodingError.dataCorruptedError(
                in: container,
                debugDescription: "Expected ISO8601 date, got \(string)."
            )
        }
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
    public let clickCount: Int?
    public let expiresAt: Date?
    public let createdAt: Date?
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
    public let topReferrers: [CountedItem]
    public let topCountries: [CountedItem]
    public let clicksByDay: [DayCount]
}

/// `{referrer,count}` or `{country_code,count}` row — label picks whichever
/// key the endpoint returned.
public struct CountedItem: Codable, Sendable {
    public let label: String
    public let count: Int

    enum Keys: String, CodingKey {
        case referrer, countryCode, count
    }

    public init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: Keys.self)
        label = try container.decodeIfPresent(String.self, forKey: .referrer)
            ?? container.decodeIfPresent(String.self, forKey: .countryCode)
            ?? "—"
        count = try container.decode(Int.self, forKey: .count)
    }

    public func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: Keys.self)
        try container.encode(label, forKey: .referrer)
        try container.encode(count, forKey: .count)
    }
}

public struct DayCount: Codable, Sendable {
    public let date: String
    public let count: Int

    static let formatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(secondsFromGMT: 0)
        return formatter
    }()

    public var day: Date? { Self.formatter.date(from: date) }
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
