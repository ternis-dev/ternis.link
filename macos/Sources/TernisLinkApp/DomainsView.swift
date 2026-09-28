import SwiftUI
import TernisLinkCore

/// Custom domains: register a hostname, surface DNS TXT instructions
/// until verified, verify on demand, deactivate owned domains.
struct DomainsView: View {
    @Environment(SessionStore.self) private var session
    @State private var hostname = ""
    @State private var busy = false
    @State private var errorMessage: String?
    @State private var notice: String?
    @State private var verifying: String?
    @State private var removing: String?

    private let links = LinksAPI()
    private let account = AccountAPI()

    var body: some View {
        NavigationStack {
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    TextField("links.example.com", text: $hostname)
                        .textFieldStyle(.roundedBorder)
                        .onSubmit(register)
                    Button("Register") { register() }
                        .buttonStyle(.borderedProminent)
                        .disabled(busy || hostname.trimmingCharacters(in: .whitespaces).isEmpty)
                }
                .padding([.horizontal, .top])
                Text("Register a hostname, publish the DNS TXT record shown, then verify.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .padding(.horizontal)

                if let notice {
                    Text(notice).font(.callout).foregroundStyle(.green).padding(.horizontal)
                }
                if let errorMessage {
                    Text(errorMessage).foregroundStyle(.red).font(.callout).padding(.horizontal)
                }

                List(session.domains) { domain in
                    VStack(alignment: .leading, spacing: 4) {
                        HStack {
                            Image(systemName: domain.isSystem ? "building.2" : "globe")
                            Text(domain.hostname).font(.callout).bold()
                            Spacer()
                            if domain.isSystem {
                                Text("system").font(.caption).foregroundStyle(.secondary)
                            } else if domain.verifiedAt != nil {
                                Text("verified").font(.caption).foregroundStyle(.green)
                            } else {
                                Text("unverified").font(.caption).foregroundStyle(.orange)
                            }
                        }
                        if !domain.isSystem, domain.verifiedAt == nil {
                            HStack {
                                Button(verifying == domain.id ? "Verifying…" : "Verify now") {
                                    verify(domain)
                                }
                                .buttonStyle(.bordered)
                                .disabled(verifying != nil)
                                Button("Remove", role: .destructive) { remove(domain) }
                                    .buttonStyle(.borderless)
                                    .disabled(removing != nil)
                            }
                            .font(.callout)
                        } else if !domain.isSystem {
                            Button("Deactivate") { remove(domain) }
                                .buttonStyle(.borderless)
                                .font(.callout)
                                .foregroundStyle(.red)
                                .disabled(removing != nil)
                        }
                    }
                    .padding(.vertical, 2)
                }
                Spacer()
            }
            .navigationTitle("Domains")
            .toolbar {
                Button("Reload") { Task { await session.loadDomains() } }
            }
        }
    }

    private func register() {
        let host = hostname.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !host.isEmpty else { return }
        busy = true
        errorMessage = nil
        notice = nil
        Task {
            guard let token = await session.bearer() else { busy = false; return }
            do {
                let domain = try await links.registerDomain(hostname: host, token: token)
                hostname = ""
                notice = "Registered \(domain.hostname). Publish its DNS TXT record, then press Verify now."
                await session.loadDomains()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not register the domain."
            }
            busy = false
        }
    }

    private func verify(_ domain: APIDomain) {
        verifying = domain.id
        errorMessage = nil
        notice = nil
        Task {
            guard let token = await session.bearer() else { verifying = nil; return }
            do {
                _ = try await links.verifyDomain(id: domain.id, token: token)
                notice = "\(domain.hostname) is verified and can serve short links."
                await session.loadDomains()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Verification failed."
            }
            verifying = nil
        }
    }

    private func remove(_ domain: APIDomain) {
        removing = domain.id
        errorMessage = nil
        notice = nil
        Task {
            guard let token = await session.bearer() else { removing = nil; return }
            do {
                try await account.deleteDomain(id: domain.id, token: token)
                notice = "\(domain.hostname) deactivated. Links and analytics are preserved."
                if session.selectedDomainID == domain.id { session.selectedDomainID = nil }
                await session.loadDomains()
            } catch let apiError as APIError {
                errorMessage = apiError.userMessage
            } catch {
                errorMessage = "Could not deactivate the domain."
            }
            removing = nil
        }
    }
}
