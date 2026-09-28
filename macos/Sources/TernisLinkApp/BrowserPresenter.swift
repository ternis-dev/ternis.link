import AppKit
import AuthenticationServices

/// Anchor for `ASWebAuthenticationSession` on macOS: the key window, falling
/// back to any visible app window.
@MainActor
final class BrowserPresenter: NSObject, ASWebAuthenticationPresentationContextProviding {
    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        NSApp.keyWindow ?? NSApp.windows.first ?? ASPresentationAnchor()
    }
}
