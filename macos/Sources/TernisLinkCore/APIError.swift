import Foundation

/// Typed failures for API calls. Mirrors the server contract
/// (`{ message }`, `{ message, errors }` for 422, `Retry-After` for 429).
public enum APIError: Error, Sendable {
    case unauthorized(String)
    case forbidden(String)
    case validation([String: [String]])
    case rateLimited(retryAfter: Int?)
    case versionRetired(latest: Int?)
    case server(status: Int, message: String)
    case network(Error)
    case decoding(Error)

    public var userMessage: String {
        switch self {
        case .unauthorized:
            return "Not signed in — check your API key or log in again."
        case .forbidden(let message):
            return message
        case .validation(let errors):
            return errors.values.flatMap { $0 }.first ?? "Invalid request."
        case .rateLimited(let retryAfter):
            if let seconds = retryAfter {
                return "Rate limited — try again in \(seconds)s."
            }
            return "Rate limited — try again shortly."
        case .versionRetired:
            return "This app version is no longer supported — please update."
        case .server(_, let message):
            return message
        case .network:
            return "No connection to ternis.link."
        case .decoding:
            return "Unexpected response from ternis.link."
        }
    }
}

struct ErrorBody: Decodable {
    let message: String?
    let errors: [String: [String]]?
}

struct RetiredBody: Decodable {
    let latestVersion: Int?
}
