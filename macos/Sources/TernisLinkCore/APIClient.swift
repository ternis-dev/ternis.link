import Foundation

/// Thin async HTTP client for `links.t-api.de/v1`. Callers pass a Bearer
/// token per request; refresh-and-retry lives in `AuthManager`.
public struct APIClient: Sendable {
    public let baseURL: URL
    public let session: URLSession

    public init(baseURL: URL = AppConfig.apiBase, session: URLSession = .shared) {
        self.baseURL = baseURL
        self.session = session
    }

    public func get<T: Decodable>(_ path: String, token: String) async throws -> T {
        try await send(path: path, method: "GET", token: token, body: nil as Data?)
    }

    public func send<T: Decodable, B: Encodable>(
        path: String,
        method: String,
        token: String,
        body: B?
    ) async throws -> T {
        let payload = try body.map { try JSONEncoder.api.encode($0) }
        return try await send(path: path, method: method, token: token, body: payload)
    }

    public func send<T: Decodable>(
        path: String,
        method: String,
        token: String,
        body: Data?
    ) async throws -> T {
        var request = URLRequest(url: baseURL.appending(path: path))
        request.httpMethod = method
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if let body {
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            request.httpBody = body
        }

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.network(error)
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.server(status: -1, message: "No response from ternis.link.")
        }

        guard (200..<300).contains(http.statusCode) else {
            throw Self.mapError(status: http.statusCode, headers: http.allHeaderFields, data: data)
        }

        do {
            return try JSONDecoder.api.decode(T.self, from: data)
        } catch {
            throw APIError.decoding(error)
        }
    }

    /// For endpoints answering 204/empty bodies (DELETE): success is the
    /// status code, there is nothing to decode.
    public func sendNoContent(path: String, method: String, token: String) async throws {
        var request = URLRequest(url: baseURL.appending(path: path))
        request.httpMethod = method
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let (data, response): (Data, URLResponse)
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.network(error)
        }

        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            let status = (response as? HTTPURLResponse)?.statusCode ?? -1
            let headers = (response as? HTTPURLResponse)?.allHeaderFields ?? [:]
            throw Self.mapError(status: status, headers: headers, data: data)
        }
    }

    public func raw(path: String, query: [URLQueryItem], token: String?) async throws -> (Data, HTTPURLResponse) {        var url = baseURL.appending(path: path)
        if !query.isEmpty {
            url = url.appending(queryItems: query)
        }
        var request = URLRequest(url: url)
        if let token {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw APIError.server(status: -1, message: "No response from ternis.link.")
        }
        return (data, http)
    }

    static func mapError(status: Int, headers: [AnyHashable: Any], data: Data) -> APIError {
        let body = try? JSONDecoder.api.decode(ErrorBody.self, from: data)
        switch status {
        case 401:
            return .unauthorized(body?.message ?? "Unauthenticated.")
        case 403:
            return .forbidden(body?.message ?? "Forbidden.")
        case 422:
            return .validation(body?.errors ?? [:])
        case 429:
            let retry = (headers["Retry-After"] as? String).flatMap(Int.init)
                ?? (headers["Retry-After"] as? Int)
            return .rateLimited(retryAfter: retry)
        case 410:
            let retired = try? JSONDecoder.api.decode(RetiredBody.self, from: data)
            return .versionRetired(latest: retired?.latestVersion)
        default:
            return .server(status: status, message: body?.message ?? "Request failed (\(status)).")
        }
    }
}
