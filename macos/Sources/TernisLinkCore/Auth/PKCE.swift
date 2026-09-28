import CryptoKit
import Foundation

/// PKCE (RFC 7636) for the SSO code flow: high-entropy verifier,
/// `S256` challenge sent to the provider.
public enum PKCE: Sendable {
    public static func makeVerifier() -> String {
        let bytes = (0..<48).map { _ in UInt8.random(in: 0...255) }
        return Data(bytes).base64URLEncoded()
    }

    public static func challenge(for verifier: String) -> String {
        let digest = SHA256.hash(data: Data(verifier.utf8))
        return Data(digest).base64URLEncoded()
    }
}

extension Data {
    func base64URLEncoded() -> String {
        base64EncodedString()
            .replacingOccurrences(of: "+", with: "-")
            .replacingOccurrences(of: "/", with: "_")
            .replacingOccurrences(of: "=", with: "")
    }
}
