import AppKit
import SwiftUI
import TernisLinkCore

/// M0 entry point: menu-bar extra + status window. M1 builds the
/// quick-shortener into the popover; M2 adds the main window.
@main
struct TernisLinkApp: App {
    var body: some Scene {
        MenuBarExtra("ternis.link", systemImage: "link") {
            Text("ternis.link")
                .font(.headline)
            Text("Quick-shortener lands here in M1.")
                .font(.caption)
                .foregroundStyle(.secondary)
            Divider()
            Button("Quit ternis.link") {
                NSApplication.shared.terminate(nil)
            }
        }

        WindowGroup {
            StatusView()
        }
        .windowResizability(.contentSize)
    }
}

struct StatusView: View {
    @State private var status = "Checking…"

    var body: some View {
        VStack(spacing: 8) {
            Text("ternis.link for Mac").font(.headline)
            Text(status).font(.caption).foregroundStyle(.secondary)
        }
        .padding()
        .frame(minWidth: 280)
        .task {
            let auth = AuthManager()
            let signedIn = await auth.isSignedIn
            status = signedIn ? "Signed in ✓" : "Not signed in — M1 adds onboarding."
        }
    }
}
