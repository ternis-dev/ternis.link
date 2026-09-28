import AppKit
import SwiftUI
import TernisLinkCore

/// First-run: paste an API key or sign in with Ternis Auth (SSO).
struct OnboardingView: View {
    @Environment(SessionStore.self) private var session
    @State private var apiKey = ""
    @State private var busy = false
    @State private var errorMessage: String?
    @State private var presenter = BrowserPresenter()

    var body: some View {
        VStack(spacing: 16) {
            Text("ternis.link for Mac").font(.largeTitle).bold()
            Text("Shorten links and see how they perform — from your desktop.")
                .foregroundStyle(.secondary)
                .multilineTextAlignment(.center)

            VStack(alignment: .leading, spacing: 8) {
                Text("API key").font(.headline)
                SecureField("tl_…", text: $apiKey)
                    .textFieldStyle(.roundedBorder)
                    .onSubmit(signInWithKey)
                HStack {
                    Button("Sign In with Key") { signInWithKey() }
                        .buttonStyle(.borderedProminent)
                        .disabled(busy || apiKey.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                    Button("Get a key…") {
                        NSWorkspace.shared.open(URL(string: "https://dash.ternis.link/api-keys")!)
                    }
                    .disabled(busy)
                }
                Text("Keys start with tl_ and live in your Keychain — never anywhere else.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Divider()

            VStack(alignment: .leading, spacing: 8) {
                Text("Single sign-on").font(.headline)
                Button("Sign In with Ternis Auth") { signInWithSSO() }
                    .buttonStyle(.bordered)
                    .disabled(busy)
                Text("One-time setup: register this app at your provider as a public client (PKCE, grants: authorization_code + refresh_token) with this redirect URI:")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                HStack {
                    Text(AppConfig.ssoRedirectURI)
                        .font(.caption)
                        .monospaced()
                        .textSelection(.enabled)
                    Button("Copy") {
                        NSPasteboard.general.clearContents()
                        NSPasteboard.general.setString(AppConfig.ssoRedirectURI, forType: .string)
                    }
                    .buttonStyle(.borderless)
                }
            }

            if busy { ProgressView().controlSize(.small) }
            if let errorMessage {
                Text(errorMessage).font(.callout).foregroundStyle(.red).multilineTextAlignment(.center)
            }
        }
        .padding(32)
        .frame(width: 440)
    }

    private func signInWithKey() {
        errorMessage = nil
        busy = true
        Task {
            do {
                try await session.signInWithAPIKey(apiKey)
            } catch AuthManager.SignInError.invalidKey {
                self.errorMessage = "That key doesn't look right — keys start with tl_. Check for a copy-paste slip."
            } catch AuthManager.SignInError.rejected {
                self.errorMessage = "The server rejected this key — it may be revoked or mistyped. Create a fresh one and try again."
            } catch let apiError as APIError {
                self.errorMessage = apiError.userMessage
            } catch {
                self.errorMessage = "Could not reach ternis.link — check your connection."
            }
            busy = false
        }
    }

    private func signInWithSSO() {
        errorMessage = nil
        busy = true
        Task {
            do {
                let tokens = try await SSOAuthorizer().authorize(presentation: presenter)
                try await session.signInWithSSO(tokens: tokens)
            } catch SSOAuthorizer.Failure.cancelled {
                // User dismissed the browser — stay put, no error.
            } catch let failure as SSOAuthorizer.Failure {
                switch failure {
                case .cancelled:
                    break
                case .callback(let message), .tokenExchange(let message):
                    self.errorMessage = message
                case .noRefreshToken:
                    self.errorMessage = "SSO sign-in failed — try again or use an API key."
                }
            } catch {
                self.errorMessage = "SSO sign-in failed — try again or use an API key."
            }
            busy = false
        }
    }
}
