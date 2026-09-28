import SwiftUI
import TernisLinkCore

/// Regular dashboard application (dock icon, main window — not a menu-bar
/// app). Root switches between onboarding and the workspace by sign-in state.
@main
struct TernisLinkApp: App {
    @State private var session = SessionStore()
    @State private var hotkeys = HotKeyManager()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environment(session)
                .task { await session.refresh() }
                .onAppear { hotkeys.register() }
        }

        Settings {
            SettingsView()
        }
    }
}

struct RootView: View {
    @Environment(SessionStore.self) private var session

    var body: some View {
        switch session.state {
        case .checking:
            ProgressView().padding().frame(minWidth: 320, minHeight: 200)
        case .signedOut:
            OnboardingView()
        case .signedIn:
            DashboardHomeView()
        }
    }
}

struct DashboardHomeView: View {
    enum SidebarSection: Hashable {
        case newLink
        case links
    }

    @State private var selection: SidebarSection? = .newLink

    var body: some View {
        NavigationSplitView {
            List(selection: $selection) {
                Section("Workspace") {
                    Label("New Link", systemImage: "plus.circle").tag(SidebarSection.newLink)
                    Label("Links", systemImage: "list.bullet").tag(SidebarSection.links)
                    Label("API Keys", systemImage: "key").foregroundStyle(.secondary)
                    Label("Domains", systemImage: "globe").foregroundStyle(.secondary)
                }
                Section("Insights") {
                    Label("Activity", systemImage: "clock").foregroundStyle(.secondary)
                    Label("Notifications", systemImage: "bell").foregroundStyle(.secondary)
                }
            }
            .listStyle(.sidebar)
            .navigationTitle("ternis.link")
        } detail: {
            switch selection ?? .newLink {
            case .newLink:
                QuickShortenView()
                    .frame(minWidth: 480, minHeight: 520)
            case .links:
                LinksView()
                    .frame(minWidth: 560, minHeight: 520)
            }
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
