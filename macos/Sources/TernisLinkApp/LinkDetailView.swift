import AppKit
import Charts
import SwiftUI
import TernisLinkCore

/// Link detail: facts, QR, click analytics (Swift Charts), deactivation.
struct LinkDetailView: View {
    @Environment(SessionStore.self) private var session
    @Environment(\.dismiss) private var dismiss
    let link: APILink

    @State private var summary: ClickSummary?
    @State private var qrPNG: Data?
    @State private var loading = true
    @State private var errorMessage: String?
    @State private var confirmDeactivate = false
    @State private var deactivating = false

    private let api = LinksAPI()

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                facts
                Divider()
                analytics
                Divider()
                qrSection
                Divider()
                dangerZone
            }
            .padding()
        }
        .navigationTitle(link.slug)
        .task { await load() }
        .confirmationDialog("Deactivate this link?", isPresented: $confirmDeactivate, titleVisibility: .visible) {
            Button("Deactivate", role: .destructive) { deactivate() }
            Button("Cancel", role: .cancel) {}
        } message: {
            Text("It stops resolving, but its stats are preserved.")
        }
    }

    // MARK: - Facts

    private var facts: some View {
        VStack(alignment: .leading, spacing: 6) {
            if let short = link.shortUrl {
                Link(destination: short) {
                    Text(short.absoluteString).font(.title3).bold()
                }
                .contextMenu {
                    Button("Copy") { copy(short.absoluteString) }
                }
            }
            if let destination = URL(string: link.destinationUrl) {
                Link(destination: destination) {
                    Text(link.destinationUrl).font(.callout).foregroundStyle(.secondary)
                }
            }
            if let description = link.description, !description.isEmpty {
                Text(description).font(.callout)
            }
            if let tags = link.tags, !tags.isEmpty {
                HStack {
                    ForEach(tags, id: \.self) { tag in
                        Text(tag)
                            .font(.caption)
                            .padding(.horizontal, 8)
                            .padding(.vertical, 2)
                            .background(.quaternary)
                            .clipShape(.capsule)
                    }
                }
            }
            HStack {
                Label(link.isActive ?? true ? "Active" : "Inactive",
                      systemImage: (link.isActive ?? true) ? "checkmark.circle" : "pause.circle")
                if let created = link.createdAt {
                    Text("Created \(created.formatted(date: .abbreviated, time: .omitted))")
                        .foregroundStyle(.secondary)
                }
            }
            .font(.callout)
        }
    }

    // MARK: - Analytics

    private var analytics: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Analytics").font(.headline)
            if loading {
                ProgressView().controlSize(.small)
            } else if let summary {
                HStack(spacing: 24) {
                    StatBlock(value: "\(summary.totalClicks)", label: "Clicks")
                    StatBlock(value: "\(summary.uniqueVisitors)", label: "Visitors")
                }
                if !summary.clicksByDay.isEmpty {
                    Text("Clicks per day").font(.subheadline).foregroundStyle(.secondary)
                    Chart(summary.clicksByDay.compactMap { row in row.day.map { (day: $0, count: row.count) } }, id: \.day) { point in
                        BarMark(
                            x: .value("Day", point.day, unit: .day),
                            y: .value("Clicks", point.count)
                        )
                    }
                    .frame(height: 160)
                }
                if !summary.topReferrers.isEmpty {
                    Text("Top referrers").font(.subheadline).foregroundStyle(.secondary)
                    ForEach(summary.topReferrers.prefix(5), id: \.label) { row in
                        HStack {
                            Text(row.label).lineLimit(1)
                            Spacer()
                            Text("\(row.count)").foregroundStyle(.secondary)
                        }
                        .font(.callout)
                    }
                }
                if !summary.topCountries.isEmpty {
                    Text("Top countries").font(.subheadline).foregroundStyle(.secondary)
                    HStack {
                        ForEach(summary.topCountries.prefix(8), id: \.label) { row in
                            Text("\(flagless(row.label)) \(row.count)")
                                .font(.callout)
                                .padding(.horizontal, 8)
                                .padding(.vertical, 2)
                                .background(.quaternary)
                                .clipShape(.capsule)
                        }
                    }
                }
            } else if let errorMessage {
                Text(errorMessage).foregroundStyle(.red).font(.callout)
            }
        }
    }

    // MARK: - QR

    private var qrSection: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("QR Code").font(.headline)
            if let qrPNG, let image = NSImage(data: qrPNG) {
                HStack(alignment: .top, spacing: 12) {
                    Image(nsImage: image)
                        .resizable()
                        .frame(width: 140, height: 140)
                    VStack(alignment: .leading) {
                        Button("Copy PNG") {
                            NSPasteboard.general.clearContents()
                            NSPasteboard.general.setData(qrPNG, forType: .png)
                        }
                        .buttonStyle(.bordered)
                        Button("Save…") { saveQR(qrPNG) }
                            .buttonStyle(.bordered)
                    }
                }
            } else {
                ProgressView().controlSize(.small)
            }
        }
    }

    // MARK: - Danger zone

    private var dangerZone: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Danger Zone").font(.headline)
            Button("Deactivate Link", role: .destructive) {
                confirmDeactivate = true
            }
            .disabled(deactivating || !(link.isActive ?? true))
            if deactivating { ProgressView().controlSize(.small) }
        }
    }

    // MARK: - Actions

    private func load() async {
        guard let token = await session.bearer() else { return }
        loading = true
        errorMessage = nil
        async let summaryTask: ClickSummary? = {
            try? await api.clickSummary(linkID: link.id, token: token)
        }()
        async let qrTask: Data? = {
            guard let short = link.shortUrl else { return nil }
            return try? await api.qrPNG(for: short)
        }()
        summary = await summaryTask
        qrPNG = await qrTask
        if summary == nil {
            errorMessage = "Could not load analytics."
        }
        loading = false
    }

    private func deactivate() {
        deactivating = true
        Task {
            guard let token = await session.bearer() else {
                deactivating = false
                return
            }
            do {
                try await api.deleteLink(id: link.id, token: token)
                dismiss()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not deactivate the link."
            }
            deactivating = false
        }
    }

    private func copy(_ string: String) {
        NSPasteboard.general.clearContents()
        NSPasteboard.general.setString(string, forType: .string)
    }

    private func saveQR(_ data: Data) {
        let panel = NSSavePanel()
        panel.allowedContentTypes = [.png]
        panel.nameFieldStringValue = "qr-\(link.slug).png"
        if panel.runModal() == .OK, let url = panel.url {
            try? data.write(to: url)
        }
    }

    private func flagless(_ countryCode: String) -> String {
        countryCode.uppercased()
    }
}

struct StatBlock: View {
    let value: String
    let label: String

    var body: some View {
        VStack(alignment: .leading) {
            Text(value).font(.title).bold()
            Text(label).font(.caption).foregroundStyle(.secondary)
        }
    }
}
