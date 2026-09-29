import SwiftUI
import TernisLinkCore
#if canImport(AppKit)
import AppKit
#endif

/// Native settings: appearance, global hotkey hint, launch note,
/// plus server-synced theme/layout + email notification preferences.
struct SettingsView: View {
    @Environment(SessionStore.self) private var session
    @State private var settings: UserSettings?
    @State private var loading = false
    @State private var saving = false
    @State private var errorMessage: String?
    @State private var savedFlash = false
    @State private var updates = UpdateService()

    private let api = AccountAPI()

    var body: some View {
        Form {
            Section("Appearance") {
                Picker("Theme", selection: themeBinding) {
                    Text("System").tag("system")
                    Text("Light").tag("light")
                    Text("Dark").tag("dark")
                }
                Picker("Dashboard layout", selection: layoutBinding) {
                    Text("Sidebar").tag("side")
                    Text("Top").tag("top")
                }
                Text("Theme follows the web dashboard preference.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Section("Email notifications") {
                Toggle("Security alerts", isOn: securityBinding)
                Toggle("Admin security alerts", isOn: adminBinding)
                Toggle("Server error alerts", isOn: serverErrorBinding)
            }

            Section("Shortcuts") {
                LabeledContent("Quick shortener", value: "⌃⌥⌘L")
                LabeledContent("Sections", value: "⌘1…⌘6")
                Text("The global hotkey summons the quick-shortener from anywhere.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Section("Account") {
                Button("Sign Out", role: .destructive) {
                    Task { await session.signOut() }
                }
            }

            if let errorMessage {
                Text(errorMessage).foregroundStyle(.red).font(.callout)
            }
            if savedFlash {
                Text("Saved.").foregroundStyle(.green).font(.callout)
            }
        }
        .formStyle(.grouped)
        .frame(minWidth: 320)
        .task { await load() }
        .disabled(settings == nil)
    }

    private var themeBinding: Binding<String> {
        Binding(
            get: { settings?.theme ?? "system" },
            set: { patch(.init(theme: $0)) }
        )
    }

    private var layoutBinding: Binding<String> {
        Binding(
            get: { settings?.navLayout ?? "side" },
            set: { patch(.init(navLayout: $0)) }
        )
    }

    private var securityBinding: Binding<Bool> {
        Binding(
            get: { settings?.notifySecurityEmail ?? true },
            set: { patch(.init(notifySecurityEmail: $0)) }
        )
    }

    private var adminBinding: Binding<Bool> {
        Binding(
            get: { settings?.notifyAdminSecurityEmail ?? true },
            set: { patch(.init(notifyAdminSecurityEmail: $0)) }
        )
    }

    private var serverErrorBinding: Binding<Bool> {
        Binding(
            get: { settings?.notifyServerErrorEmail ?? true },
            set: { patch(.init(notifyServerErrorEmail: $0)) }
        )
    }

    private func load() async {
        guard let token = await session.bearer() else { return }
        loading = true
        errorMessage = nil
        do {
            settings = try await api.getSettings(token: token)
        } catch let apiError as APIError {
            errorMessage = apiError.userMessage
        } catch {
            errorMessage = "Could not load settings."
        }
        loading = false
    }

    /// Optimistic toggle: apply locally, persist, roll back on failure.
    private func patch(_ change: SettingsPatch) {
        guard var current = settings else { return }
        if let value = change.theme { current.theme = value }
        if let value = change.navLayout { current.navLayout = value }
        if let value = change.notifySecurityEmail { current.notifySecurityEmail = value }
        if let value = change.notifyAdminSecurityEmail { current.notifyAdminSecurityEmail = value }
        if let value = change.notifyServerErrorEmail { current.notifyServerErrorEmail = value }
        settings = current
        saving = true
        savedFlash = false
        Task {
            guard let token = await session.bearer() else { saving = false; return }
            do {
                settings = try await api.updateSettings(change, token: token)
                savedFlash = true
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
                await load()
            } catch {
                errorMessage = "Could not save settings."
                await load()
            }
            saving = false
        }
    }
}
