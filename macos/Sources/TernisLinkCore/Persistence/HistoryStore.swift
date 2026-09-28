import Foundation

/// Local shorten history (last 50, newest first). File-backed JSON in
/// Application Support — device-local only, like the href.nz web tray.
@Observable @MainActor
public final class HistoryStore {
    public struct Entry: Codable, Sendable, Identifiable {
        public let id: String
        public let shortUrl: String
        public let destination: String
        public let createdAt: Date

        public init(shortUrl: String, destination: String) {
            self.id = UUID().uuidString
            self.shortUrl = shortUrl
            self.destination = destination
            self.createdAt = Date()
        }
    }

    public private(set) var entries: [Entry] = []

    private let fileURL: URL
    private static let fileName = "shorten-history.json"
    private static let cap = 50

    public init(directory: URL? = nil) {
        let base = directory ?? FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask)
            .first!
            .appending(path: "link.ternis.desktop", directoryHint: .isDirectory)
        try? FileManager.default.createDirectory(at: base, withIntermediateDirectories: true)
        self.fileURL = base.appending(path: Self.fileName, directoryHint: .notDirectory)
        load()
    }

    public func add(shortUrl: String, destination: String) {
        entries.insert(Entry(shortUrl: shortUrl, destination: destination), at: 0)
        entries = Array(entries.prefix(Self.cap))
        save()
    }

    public func clear() {
        entries = []
        save()
    }

    private func load() {
        guard let data = try? Data(contentsOf: fileURL),
              let decoded = try? JSONDecoder.api.decode([Entry].self, from: data)
        else { return }
        entries = decoded
    }

    private func save() {
        try? JSONEncoder.api.encode(entries).write(to: fileURL, options: .atomic)
    }
}
