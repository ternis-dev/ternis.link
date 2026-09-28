import SwiftUI
import TernisLinkCore

/// Links library: server-paged list with tag filter (API) + text search
/// (client-side, current page) + detail push.
struct LinksView: View {
    @Environment(SessionStore.self) private var session
    @State private var links: [APILink] = []
    @State private var page = 1
    @State private var lastPage = 1
    @State private var total = 0
    @State private var search = ""
    @State private var tag = ""
    @State private var appliedTag = ""
    @State private var loading = false
    @State private var errorMessage: String?

    private let api = LinksAPI()

    var visible: [APILink] {
        guard !search.isEmpty else { return links }
        let needle = search.lowercased()
        return links.filter {
            $0.slug.lowercased().contains(needle)
                || $0.destinationUrl.lowercased().contains(needle)
                || ($0.description?.lowercased().contains(needle) ?? false)
        }
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                HStack {
                    TextField("Search slug, URL, description…", text: $search)
                        .textFieldStyle(.roundedBorder)
                    TextField("tag", text: $tag)
                        .textFieldStyle(.roundedBorder)
                        .frame(maxWidth: 120)
                        .onSubmit { applyTag() }
                    Button("Filter") { applyTag() }
                }
                .padding([.horizontal, .top])

                if loading && links.isEmpty {
                    ProgressView().padding()
                } else if let errorMessage {
                    Text(errorMessage).foregroundStyle(.red).font(.callout).padding()
                } else if visible.isEmpty {
                    Text(appliedTag.isEmpty ? "No links yet — create one from New Link." : "No links tagged “\(appliedTag)”.")
                        .foregroundStyle(.secondary)
                        .padding()
                } else {
                    List(visible) { link in
                        NavigationLink(value: link) {
                            LinkRow(link: link)
                        }
                    }
                }

                HStack {
                    Text("\(total) links")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Spacer()
                    Button("‹ Prev") { Task { await go(to: page - 1) } }
                        .disabled(page <= 1 || loading)
                    Text("Page \(page) / \(max(lastPage, 1))")
                        .font(.caption)
                    Button("Next ›") { Task { await go(to: page + 1) } }
                        .disabled(page >= lastPage || loading)
                }
                .padding()
            }
            .navigationTitle("Links")
            .toolbar {
                Button("Reload") { Task { await go(to: 1) } }.disabled(loading)
            }
            .navigationDestination(for: APILink.self) { link in
                LinkDetailView(link: link)
            }
            .task { await go(to: 1) }
        }
    }

    private func applyTag() {
        appliedTag = tag.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        Task { await go(to: 1) }
    }

    private func go(to newPage: Int) async {
        guard let token = await session.bearer() else { return }
        loading = true
        errorMessage = nil
        do {
            let paged = try await api.listLinks(page: max(newPage, 1), tag: appliedTag.isEmpty ? nil : appliedTag, token: token)
            links = paged.data
            page = paged.currentPage
            lastPage = paged.lastPage
            total = paged.total
        } catch let apiError as APIError {
            errorMessage = apiError.userMessage
        } catch {
            errorMessage = "Could not load links."
        }
        loading = false
    }
}

struct LinkRow: View {
    let link: APILink

    var body: some View {
        HStack {
            Circle()
                .fill((link.isActive ?? true) ? Color.green : Color.gray)
                .frame(width: 8, height: 8)
            VStack(alignment: .leading) {
                Text(link.domain.map { "\($0.hostname)/\(link.slug)" } ?? link.slug)
                    .font(.callout)
                    .bold()
                    .lineLimit(1)
                Text(link.destinationUrl)
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .lineLimit(1)
            }
            Spacer()
            if let count = link.clickCount {
                Text("\(count)")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .help("\(count) clicks")
            }
        }
        .padding(.vertical, 2)
    }
}
