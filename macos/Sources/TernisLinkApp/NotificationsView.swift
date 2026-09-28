import SwiftUI
import TernisLinkCore

/// Notification inbox: newest first, mark-one / mark-all read.
struct NotificationsView: View {
    @Environment(SessionStore.self) private var session
    @State private var items: [AppNotification] = []
    @State private var loading = false
    @State private var errorMessage: String?

    private let api = AccountAPI()

    var unread: Int { items.filter(\.isUnread).count }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                if loading && items.isEmpty {
                    ProgressView().padding()
                } else if let errorMessage {
                    Text(errorMessage).foregroundStyle(.red).font(.callout).padding()
                } else if items.isEmpty {
                    Text("No notifications — quiet by default.")
                        .foregroundStyle(.secondary)
                        .padding()
                } else {
                    List(items) { item in
                        HStack(alignment: .top, spacing: 8) {
                            Circle()
                                .fill(item.isUnread ? Color.accentColor : Color.gray.opacity(0.4))
                                .frame(width: 8, height: 8)
                                .padding(.top, 5)
                            VStack(alignment: .leading, spacing: 2) {
                                Text(item.title).font(.callout).bold()
                                if let body = item.data?.body, !body.isEmpty {
                                    Text(body).font(.caption).foregroundStyle(.secondary).lineLimit(3)
                                }
                                if let created = item.createdAt {
                                    Text(created.formatted(date: .abbreviated, time: .shortened))
                                        .font(.caption2)
                                        .foregroundStyle(.tertiary)
                                }
                            }
                            Spacer()
                            if item.isUnread {
                                Button("Mark read") { markRead(item) }
                                    .buttonStyle(.borderless)
                                    .font(.callout)
                            }
                        }
                        .padding(.vertical, 2)
                    }
                }
                Spacer()
            }
            .navigationTitle(unread > 0 ? "Notifications (\(unread))" : "Notifications")
            .toolbar {
                Button("Mark all read") { markAllRead() }.disabled(unread == 0)
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
            items = try await api.listNotifications(token: token).data
        } catch let apiError as APIError {
            errorMessage = apiError.userMessage
        } catch {
            errorMessage = "Could not load notifications."
        }
        loading = false
    }

    private func markRead(_ item: AppNotification) {
        Task {
            guard let token = await session.bearer() else { return }
            do {
                let updated = try await api.markNotificationRead(id: item.id, token: token)
                if let index = items.firstIndex(where: { $0.id == item.id }) {
                    items[index] = updated
                }
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not mark the notification read."
            }
        }
    }

    private func markAllRead() {
        Task {
            guard let token = await session.bearer() else { return }
            do {
                try await api.markAllNotificationsRead(token: token)
                await load()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not mark notifications read."
            }
        }
    }
}
