import SwiftUI
import TernisLinkCore

/// M0 entry point: a regular dashboard application (dock icon, main window —
/// deliberately NOT a menu-bar app). M2 fills the sidebar features.
@main
struct TernisLinkApp: App {
    var body: some Scene {
        WindowGroup {
            DashboardHomeView()
        }

        Settings {
            SettingsView()
        }
    }
}

struct DashboardHomeView: View {
    @State private var status = "Checking…"

    var body: some View {
        NavigationSplitView {
            List {
                Section("Workspace") {
                    Label("Links", systemImage: "link")
                    Label("API Keys", systemImage: "key")
                    Label("Domains", systemImage: "globe")
                }
                Section("Insights") {
                    Label("Activity", systemImage: "clock")
                    Label("Notifications", systemImage: "bell")
                }
            }
            .listStyle(.sidebar)
            .disabled(true)
            .navigationTitle("ternis.link")
        } detail: {
            VStack(spacing: 8) {
                Text("ternis.link for Mac").font(.headline)
                Text(status).font(.caption).foregroundStyle(.secondary)
                Text("Sidebar features land in M2 — this shell proves the app target.")
                    .font(.caption)
                    .foregroundStyle(.tertiary)
            }
            .padding()
            .frame(minWidth: 320, minHeight: 200)
        }
        .task {
            let auth = AuthManager()
            let signedIn = await auth.isSignedIn
            status = signedIn ? "Signed in ✓" : "Not signed in — M1 adds onboarding."
        }
    }
}

struct SettingsView: View {
    var body: some View {
        Text("Appearance, hotkey, and launch-at-login land here in M3.")
            .padding()
            .frame(minWidth: 280)
    }
}
