import Foundation

/// Dependency-free update check against the first-party Sparkle appcast
/// (`SUFeedURL`, served at `/pages/macos/appcast.xml`).
///
/// This is the fallback behind Sparkle's SPUUpdater (see UpdateService):
/// dev/ad-hoc bundles carry no EdDSA key, so Sparkle refuses silent
/// installs there — the checker still finds the release and hands the
/// user the download page. Pure logic + URLSession, fully unit-tested.
public struct UpdateChecker: Sendable {
    public struct Release: Sendable, Equatable {
        public let version: String
        public let downloadURL: URL
        public let notes: String?

        public init(version: String, downloadURL: URL, notes: String? = nil) {
            self.version = version
            self.downloadURL = downloadURL
            self.notes = notes
        }
    }

    public enum CheckError: Error, Sendable {
        case badFeed
        case transport(String)
    }

    private let feedURL: URL
    private let session: URLSession

    public init(feedURL: URL = URL(string: "https://ternis.link/pages/macos/appcast.xml")!, session: URLSession = .shared) {
        self.feedURL = feedURL
        self.session = session
    }

    /// Newest release newer than `currentVersion`, or nil when up to date
    /// (or when the feed carries nothing parseable).
    public func newestRelease(newerThan currentVersion: String) async throws -> Release? {
        let releases = try await fetchReleases()
        return releases
            .filter { Self.compareVersions($0.version, currentVersion) == .orderedDescending }
            .sorted { Self.compareVersions($0.version, $1.version) == .orderedDescending }
            .first
    }

    public func fetchReleases() async throws -> [Release] {
        let (data, _) = try await session.data(from: feedURL)
        return Self.parse(data: data)
    }

    // MARK: - Appcast parsing (RSS 2.0 + sparkle:enclosure)

    static func parse(data: Data) -> [Release] {
        let parser = AppcastParser()
        let xml = XMLParser(data: data)
        xml.delegate = parser
        xml.shouldResolveExternalEntities = false
        guard xml.parse() else { return [] }
        return parser.releases
    }

    // MARK: - Version comparison (numeric per dot-component)

    /// Compares dotted versions numerically (`1.10` > `1.9`, `0.4.0` ==
    /// `0.4`). Non-numeric suffixes compare as lower than the bare
    /// number (`1.0-beta` < `1.0`).
    static func compareVersions(_ lhs: String, _ rhs: String) -> ComparisonResult {
        let lParts = lhs.split(separator: ".", omittingEmptySubsequences: false).map(String.init)
        let rParts = rhs.split(separator: ".", omittingEmptySubsequences: false).map(String.init)
        for i in 0..<max(lParts.count, rParts.count) {
            let l = i < lParts.count ? lParts[i] : "0"
            let r = i < rParts.count ? rParts[i] : "0"
            if let ln = Int(l.prefix(while: \.isNumber)), let rn = Int(r.prefix(while: \.isNumber)) {
                if ln != rn { return ln < rn ? .orderedAscending : .orderedDescending }
                let lRest = l.dropFirst(String(ln).count)
                let rRest = r.dropFirst(String(rn).count)
                if lRest != rRest { return lRest.isEmpty ? .orderedDescending : (rRest.isEmpty ? .orderedAscending : (lRest < rRest ? .orderedAscending : .orderedDescending)) }
            } else if l != r {
                return l < r ? .orderedAscending : .orderedDescending
            }
        }
        return .orderedSame
    }
}

private final class AppcastParser: NSObject, XMLParserDelegate, @unchecked Sendable {
    private(set) var releases: [UpdateChecker.Release] = []
    private var inItem = false
    private var currentText = ""
    private var version: String?
    private var downloadURL: URL?
    private var notes: String?

    func parser(_ parser: XMLParser, didStartElement name: String, namespaceURI: String?, qualifiedName: String?, attributes: [String: String] = [:]) {
        if name == "item" {
            inItem = true
            version = nil
            downloadURL = nil
            notes = nil
        }
        // Sparkle enclosures arrive as <enclosure> with either plain or
        // sparkle:-namespaced attributes, depending on the parser.
        if inItem, name == "enclosure" {
            let v = attributes["sparkle:version"] ?? attributes["version"]
            let url = attributes["url"].flatMap(URL.init(string:))
            if let v, !v.isEmpty { version = v }
            if let url { downloadURL = url }
        }
        currentText = ""
    }

    func parser(_ parser: XMLParser, foundCharacters string: String) {
        currentText += string
    }

    func parser(_ parser: XMLParser, didEndElement name: String, namespaceURI: String?, qualifiedName: String?) {
        guard inItem else { return }
        let text = currentText.trimmingCharacters(in: .whitespacesAndNewlines)
        switch name {
        case "title" where version == nil:
            // Fallback: derive "1.2.3" from titles like "ternis.link 1.2.3".
            let candidate = text.split(separator: " ").last.map(String.init) ?? ""
            if !candidate.isEmpty, candidate.first?.isNumber == true { version = candidate }
        case "description":
            if !text.isEmpty { notes = text }
        case "item":
            if let version, !version.isEmpty, let downloadURL {
                releases.append(.init(version: version, downloadURL: downloadURL, notes: notes))
            }
            inItem = false
        default:
            break
        }
    }
}
