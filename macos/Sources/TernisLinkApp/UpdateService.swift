import Foundation
import TernisLinkCore

/// App update orchestration.
///
/// Production path (signed + notarized builds under Xcode): Sparkle's
/// `SPUStandardUpdaterController` drives silent updates off the same
/// appcast — add the Sparkle SPM dependency and flip the `canImport`
/// branch below. Sparkle ships as a binary artifact that this repo's
/// CLT-only CI cannot fetch (verified 2026-09-29), so the checked-in
/// path is the dependency-free `UpdateChecker`: fetch the first-party
/// appcast, compare against the bundle version, and hand the user the
/// download page. Same feed, same versions, no silent install without
/// a Developer ID signature — which is the honest behavior anyway.
@MainActor
@Observable
final class UpdateService {
    enum State: Equatable {
        case idle
        case checking
        case upToDate(current: String)
        case available(version: String, url: URL)
        case failed(String)
    }

    private(set) var state: State = .idle

    private let checker: UpdateChecker
    private let currentVersion: String
    private let lastCheckKey = "ternislink.lastUpdateCheck"

    init(checker: UpdateChecker = UpdateChecker(), currentVersion: String = Bundle.main.appVersion) {
        self.checker = checker
        self.currentVersion = currentVersion
    }

    /// Check now and publish the outcome (drives the Settings section).
    func check() async {
        state = .checking
        do {
            if let release = try await checker.newestRelease(newerThan: currentVersion) {
                state = .available(version: release.version, url: release.downloadURL)
            } else {
                state = .upToDate(current: currentVersion)
            }
            UserDefaults.standard.set(Date(), forKey: lastCheckKey)
        } catch {
            state = .failed("Could not reach the update feed.")
        }
    }

    /// Background check at launch, at most once per 24h. Silent unless
    /// an update is actually available (Settings surfaces the state).
    func checkAtLaunchIfDue() async {
        let last = UserDefaults.standard.object(forKey: lastCheckKey) as? Date
        if let last, Date().timeIntervalSince(last) < 24 * 3600, state != .idle {
            return
        }
        // First launch ever: check immediately so day-one users learn
        // the mechanism; afterwards once per day at most.
        if last == nil || Date().timeIntervalSince(last ?? .distantPast) >= 24 * 3600 {
            await check()
        }
    }
}

extension Bundle {
    /// Marketing version for display + update comparison. Falls back to
    /// the repo VERSION when running unbundled (swift run / previews).
    var appVersion: String {
        (infoDictionary?["CFBundleShortVersionString"] as? String)
            .flatMap { $0.isEmpty ? nil : $0 } ?? "0.4.0"
    }

    static var mainAppVersion: String { Bundle.main.appVersion }
}
