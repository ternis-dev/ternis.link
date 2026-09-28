import AppKit
import SwiftUI
import TernisLinkCore

/// Core M1 screen: URL in, short link out. Clipboard prefill, domain picker,
/// optional custom slug, copy + QR result, device-local history.
struct QuickShortenView: View {
    @Environment(SessionStore.self) private var session
    @State private var destination = ""
    @State private var slug = ""
    @State private var busy = false
    @State private var errorMessage: String?
    @State private var result: APILink?
    @State private var showQR = false
    @State private var qrPNG: Data?
    @FocusState private var urlFocused: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            if let warning = session.serverWarning {
                Label(warning, systemImage: "exclamationmark.triangle")
                    .font(.callout)
                    .foregroundStyle(.orange)
            }

            Text("New short link").font(.title2).bold()

            TextField("https://example.com/…", text: $destination)
                .textFieldStyle(.roundedBorder)
                .focused($urlFocused)
                .onSubmit(shorten)
                .onAppear(perform: prefillFromClipboard)

            HStack {
                Picker("Domain", selection: domainBinding) {
                    ForEach(session.domains) { domain in
                        Text(domain.hostname).tag(Optional(domain.id))
                    }
                }
                .frame(maxWidth: 220)
                TextField("Custom slug (optional)", text: $slug)
                    .textFieldStyle(.roundedBorder)
                    .frame(maxWidth: 200)
            }

            HStack {
                Button("Shorten") { shorten() }
                    .buttonStyle(.borderedProminent)
                    .disabled(busy || !destination.hasPrefix("http"))
                    .keyboardShortcut(.return, modifiers: .command)
                if busy { ProgressView().controlSize(.small) }
            }

            if let errorMessage {
                Text(errorMessage).foregroundStyle(.red).font(.callout)
            }

            if let result, let short = result.shortUrl?.absoluteString {
                Divider()
                VStack(alignment: .leading, spacing: 8) {
                    Link(destination: result.shortUrl!) {
                        Text(short).font(.headline)
                    }
                    HStack {
                        Button("Copy") {
                            NSPasteboard.general.clearContents()
                            NSPasteboard.general.setString(short, forType: .string)
                        }
                        .buttonStyle(.bordered)
                        Button(showQR ? "Hide QR" : "Show QR") {
                            showQR.toggle()
                            Task { await loadQR(for: result) }
                        }
                        .buttonStyle(.bordered)
                        Button("Sign Out", role: .destructive) {
                            Task { await session.signOut() }
                        }
                        .buttonStyle(.borderless)
                    }
                    if showQR, let qrPNG, let image = NSImage(data: qrPNG) {
                        Image(nsImage: image)
                            .resizable()
                            .frame(width: 140, height: 140)
                    }
                }
            }

            Divider()
            HStack {
                Text("Recent").font(.headline)
                Spacer()
                if !session.history.entries.isEmpty {
                    Button("Clear") { session.history.clear() }
                        .buttonStyle(.borderless)
                }
            }
            List(session.history.entries) { entry in
                VStack(alignment: .leading) {
                    Text(entry.shortUrl).font(.callout).bold()
                    Text(entry.destination).font(.caption).foregroundStyle(.secondary).lineLimit(1)
                }
                .contextMenu {
                    Button("Copy") {
                        NSPasteboard.general.clearContents()
                        NSPasteboard.general.setString(entry.shortUrl, forType: .string)
                    }
                }
            }
            .frame(minHeight: 120)
        }
        .padding()
        .onReceive(NotificationCenter.default.publisher(for: .summonQuickShortener)) { _ in
            urlFocused = true
        }
        .task { await session.loadDomains() }
    }

    private var domainBinding: Binding<String?> {
        Binding(
            get: { session.selectedDomainID },
            set: { if let id = $0 { session.rememberDomain(id) } }
        )
    }

    private func prefillFromClipboard() {
        guard destination.isEmpty,
              let text = NSPasteboard.general.string(forType: .string)?
                .trimmingCharacters(in: .whitespacesAndNewlines),
              text.hasPrefix("http")
        else { return }
        destination = text
        urlFocused = true
    }

    private func shorten() {
        errorMessage = nil
        result = nil
        showQR = false
        qrPNG = nil
        busy = true
        Task {
            do {
                result = try await session.shorten(destination: destination.trimmingCharacters(in: .whitespacesAndNewlines), slug: slug)
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Something went wrong."
            }
            busy = false
        }
    }

    private func loadQR(for link: APILink) async {
        guard let short = link.shortUrl else { return }
        qrPNG = try? await LinksAPI().qrPNG(for: short)
    }
}
