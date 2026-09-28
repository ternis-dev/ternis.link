import SwiftUI
import TernisLinkCore

/// Chronological activity list: own actions plus actions others
/// performed on the user's stuff.
struct ActivityView: View {
    @Environment(SessionStore.self) private var session
    @State private var entries: [ActivityEntry] = []
    @State private var loading = false
    @State private var errorMessage: String?

    private let api = AccountAPI()

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                if loading && entries.isEmpty {
                    ProgressView().padding()
                } else if let errorMessage {
                    Text(errorMessage).foregroundStyle(.red).font(.callout).padding()
                } else if entries.isEmpty {
                    Text("No activity yet.")
                        .foregroundStyle(.secondary)
                        .padding()
                } else {
                    List(entries) { entry in
                        HStack(alignment: .top) {
                            Image(systemName: icon(for: entry.action))
                                .foregroundStyle(.secondary)
                                .frame(width: 20)
                            VStack(alignment: .leading, spacing: 2) {
                                Text(entry.action).font(.callout).bold()
                                Text("\(entry.actorName)\(entry.subjectLabel.map { " · \($0)" } ?? "")")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                    .lineLimit(2)
                                if let created = entry.createdAt {
                                    Text(created.formatted(date: .abbreviated, time: .shortened))
                                        .font(.caption2)
                                        .foregroundStyle(.tertiary)
                                }
                            }
                        }
                        .padding(.vertical, 2)
                    }
                }
                Spacer()
            }
            .navigationTitle("Activity")
            .toolbar {
                Button("Reload") { Task { await load() } }.disabled(loading)
            }
            .task { await load() }
        }
    }

    private func load() async {
        guard let token = await session.bearer() else { return }
        loading = true
        errorMessage = nil
        do {
            entries = try await api.listActivity(token: token).data
        } catch let apiError as APIError {
            errorMessage = apiError.userMessage
        } catch {
            errorMessage = "Could not load activity."
        }
        loading = false
    }

    private func icon(for action: String) -> String {
        if action.hasPrefix("link.") { return "link" }
        if action.hasPrefix("domain.") { return "globe" }
        if action.hasPrefix("api_key.") { return "key" }
        if action.hasPrefix("auth.") { return "person" }
        if action.hasPrefix("admin.") { return "shield" }
        return "clock"
    }
}
