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
        case apiKeys
        case domains
        case activity
        case notifications
    }

    @State private var selection: SidebarSection? = .newLink

    var body: some View {
        NavigationSplitView {
            List(selection: $selection) {
                Section("Workspace") {
                    Label("New Link", systemImage: "plus.circle").tag(SidebarSection.newLink)
                    Label("Links", systemImage: "list.bullet").tag(SidebarSection.links)
                    Label("API Keys", systemImage: "key").tag(SidebarSection.apiKeys)
                    Label("Domains", systemImage: "globe").tag(SidebarSection.domains)
                }
                Section("Insights") {
                    Label("Activity", systemImage: "clock").tag(SidebarSection.activity)
                    Label("Notifications", systemImage: "bell").tag(SidebarSection.notifications)
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
            case .apiKeys:
                ApiKeysView()
                    .frame(minWidth: 480, minHeight: 520)
            case .domains:
                DomainsView()
                    .frame(minWidth: 480, minHeight: 520)
            case .activity:
                ActivityView()
                    .frame(minWidth: 480, minHeight: 520)
            case .notifications:
                NotificationsView()
                    .frame(minWidth: 480, minHeight: 520)
            }
        }
        .background(SectionJumper(selection: $selection))
    }
}

/// Invisible helper mapping ⌘1…⌘6 to sidebar sections.
private struct SectionJumper: View {
    @Binding var selection: DashboardHomeView.SidebarSection?

    var body: some View {
        HStack(spacing: 0) {
            ForEach(Array(sections.enumerated()), id: \.offset) { index, section in
                Button("") { selection = section }
                    .keyboardShortcut(KeyEquivalent(Character("\(index + 1)")), modifiers: .command)
                    .hidden()
            }
        }
        .frame(width: 0, height: 0)
        .hidden()
    }

    private var sections: [DashboardHomeView.SidebarSection] {
        [.newLink, .links, .apiKeys, .domains, .activity, .notifications]
    }
}
