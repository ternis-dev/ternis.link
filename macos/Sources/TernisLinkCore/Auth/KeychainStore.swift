import Foundation
import Security

/// Keychain wrapper for the credential. Generic-password item, accessible
/// after first unlock; `service` is namespaced per credential slot so a
/// future multi-account feature can add slots without migration.
public enum KeychainStore: Sendable {
    public static let service = "link.ternis.desktop"
    public static let account = "credential"

    public enum Failure: Error {
        case secError(OSStatus)
        case corrupt
    }

    public static func load() throws -> Credential? {
        var item: CFTypeRef?
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne,
        ]
        let status = SecItemCopyMatching(query as CFDictionary, &item)
        if status == errSecItemNotFound { return nil }
        guard status == errSecSuccess, let data = item as? Data else {
            throw Failure.secError(status)
        }
        guard let credential = try? JSONDecoder.api.decode(Credential.self, from: data) else {
            throw Failure.corrupt
        }
        return credential
    }

    public static func save(_ credential: Credential) throws {
        let data = try JSONEncoder.api.encode(credential)
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
        ]
        let add: [String: Any] = query.merging([
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlock,
        ]) { _, new in new }

        let status = SecItemAdd(add as CFDictionary, nil)
        if status == errSecDuplicateItem {
            let updateStatus = SecItemUpdate(query as CFDictionary, [kSecValueData as String: data] as CFDictionary)
            guard updateStatus == errSecSuccess else { throw Failure.secError(updateStatus) }
            return
        }
        guard status == errSecSuccess else { throw Failure.secError(status) }
    }

    public static func delete() throws {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
        ]
        let status = SecItemDelete(query as CFDictionary)
        guard status == errSecSuccess || status == errSecItemNotFound else {
            throw Failure.secError(status)
        }
    }
}
