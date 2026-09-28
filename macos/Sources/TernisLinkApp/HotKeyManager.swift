import AppKit
import Carbon

extension Notification.Name {
    static let summonQuickShortener = Notification.Name("link.ternis.summonQuickShortener")
}

/// Global hotkey (default `⌃⌥⌘L`) that activates the app and focuses the
/// URL field, wherever you are. Fixed binding in M1; configurable in M3.
@MainActor
final class HotKeyManager {
    // Opaque Carbon refs: touched from deinit (nonisolated), safe because
    // registration/unregistration both happen on the main thread.
    nonisolated(unsafe) private var hotKeyRef: EventHotKeyRef?
    nonisolated(unsafe) private var handlerRef: EventHandlerRef?
    private var registered = false

    func register() {
        guard !registered else { return }
        registered = true

        var eventType = EventTypeSpec(
            eventClass: OSType(kEventClassKeyboard),
            eventKind: UInt32(kEventHotKeyPressed)
        )
        let context = Unmanaged.passUnretained(self).toOpaque()
        InstallEventHandler(
            GetApplicationEventTarget(),
            { _, _, userData -> OSStatus in
                guard let userData else { return noErr }
                let manager = Unmanaged<HotKeyManager>.fromOpaque(userData).takeUnretainedValue()
                Task { @MainActor in manager.pressed() }
                return noErr
            },
            1, &eventType, context, &handlerRef
        )

        // 'TLNK', ⌃⌥⌘L (kVK_ANSI_L).
        var hotID = EventHotKeyID(signature: OSType(0x544C4E4B), id: 1)
        let modifiers = UInt32(controlKey | optionKey | cmdKey)
        RegisterEventHotKey(UInt32(kVK_ANSI_L), modifiers, hotID, GetApplicationEventTarget(), 0, &hotKeyRef)
    }

    private func pressed() {
        NSApp.activate()
        NSApp.windows.first?.makeKeyAndOrderFront(nil)
        NotificationCenter.default.post(name: .summonQuickShortener, object: nil)
    }

    deinit {
        if let hotKeyRef { UnregisterEventHotKey(hotKeyRef) }
        if let handlerRef { RemoveEventHandler(handlerRef) }
    }
}
