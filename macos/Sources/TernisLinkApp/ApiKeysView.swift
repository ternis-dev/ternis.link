import AppKit
import SwiftUI
import TernisLinkCore

/// API key manager: list own keys, create (copy-once raw token),
/// revoke with confirmation. The raw `tl_…` token is shown once
/// and never persisted — same contract as the web manager.
struct ApiKeysView: View {
    @Environment(SessionStore.self) private var session
    @State private var keys: [APIKey] = []
    @State private var newName = ""
    @State private var freshKey: APIKey?
    @State private var loading = false
    @State private var busy = false
    @State private var errorMessage: String?
    @State private var revokeTarget: APIKey?

    private let api = AccountAPI()

    var body: some View {
        NavigationStack {
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    TextField("New key name (e.g. MacBook)", text: $newName)
                        .textFieldStyle(.roundedBorder)
                        .onSubmit(create)
                    Button("Create") { create() }
                        .buttonStyle(.borderedProminent)
                        .disabled(busy || newName.trimmingCharacters(in: .whitespaces).isEmpty)
                }
                .padding([.horizontal, .top])

                if let freshKey, let raw = freshKey.apiKey {
                    VStack(alignment: .leading, spacing: 6) {
                        Label("Copy this key now — it will never be shown again.", systemImage: "exclamationmark.triangle")
                            .font(.callout)
                            .foregroundStyle(.orange)
                        HStack {
                            Text(raw).font(.system(.body, design: .monospaced)).textSelection(.enabled)
                            Button("Copy") {
                                NSPasteboard.general.clearContents()
                                NSPasteboard.general.setString(raw, forType: .string)
                            }
                            .buttonStyle(.bordered)
                        }
                    }
                    .padding()
                    .background(.quaternary.opacity(0.5), in: RoundedRectangle(cornerRadius: 8))
                    .padding(.horizontal)
                }

                if loading && keys.isEmpty {
                    ProgressView().padding()
                } else if let errorMessage {
                    Text(errorMessage).foregroundStyle(.red).font(.callout).padding(.horizontal)
                } else if keys.isEmpty {
                    Text("No API keys yet — create one above.")
                        .foregroundStyle(.secondary)
                        .padding(.horizontal)
                } else {
                    List(keys) { key in
                        HStack {
                            Image(systemName: key.isRevoked ? "key.slash" : "key")
                                .foregroundStyle(key.isRevoked ? .secondary : .primary)
                            VStack(alignment: .leading) {
                                Text(key.displayName).font(.callout).bold()
                                if let created = key.createdAt {
                                    Text("Created \(created.formatted(date: .abbreviated, time: .omitted))")
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                }
                            }
                            Spacer()
                            if key.isRevoked {
                                Text("revoked").font(.caption).foregroundStyle(.secondary)
                            } else {
                                Button("Revoke", role: .destructive) { revokeTarget = key }
                                    .buttonStyle(.borderless)
                            }
                        }
                    }
                }
                Spacer()
            }
            .navigationTitle("API Keys")
            .toolbar {
                Button("Reload") { Task { await load() } }.disabled(loading)
            }
            .task { await load() }
            .alert("Revoke this key?", isPresented: Binding(
                get: { revokeTarget != nil },
                set: { if !$0 { revokeTarget = nil } }
            )) {
                Button("Revoke", role: .destructive) {
                    if let key = revokeTarget { revoke(key) }
                }
                Button("Cancel", role: .cancel) { revokeTarget = nil }
            } message: {
                Text("“\(revokeTarget?.name ?? "")” stops working immediately. Apps using it will need a new key.")
            }
        }
    }

    private func load() async {
        guard let token = await session.bearer() else { return }
        loading = true
        errorMessage = nil
        do {
            keys = try await api.listKeys(token: token).data
        } catch let apiError as APIError {
            errorMessage = apiError.userMessage
        } catch {
            errorMessage = "Could not load API keys."
        }
        loading = false
    }

    private func create() {
        let name = newName.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !name.isEmpty else { return }
        busy = true
        errorMessage = nil
        Task {
            guard let token = await session.bearer() else { busy = false; return }
            do {
                freshKey = try await api.createKey(name: name, token: token)
                newName = ""
                await load()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not create the key."
            }
            busy = false
        }
    }

    private func revoke(_ key: APIKey) {
        revokeTarget = nil
        busy = true
        Task {
            guard let token = await session.bearer() else { busy = false; return }
            do {
                try await api.revokeKey(id: key.id, token: token)
                freshKey = nil
                await load()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not revoke the key."
            }
            busy = false
        }
    }
}
