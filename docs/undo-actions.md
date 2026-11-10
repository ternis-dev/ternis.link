# Undo notifications

The shared `notify` toast supports an optional Undo action. Currently implemented for dashboard single-link deactivation and scheduled account deletion. Keyboard focus and hover pause dismissal, including with reduced motion; Undo notifications remain visible for 15 seconds.

Link deactivation Undo uses an opaque, account-bound, single-use server cache token valid for two minutes. The server rechecks ownership/admin permission and compares the stored row snapshot under a database row lock. Any intervening change makes the undo fail safely; removed/quarantined links cannot be restored. Expired links remain expired. Redirect caches are invalidated and reactivation is audited. Undo does not restore lost traffic while a link was inactive.

Account deletion Undo invokes the existing cancellation action; it does not reverse completed erasure or revoke already-sent notifications. Cancellation remains available from privacy settings after the toast disappears.

Do not add a cosmetic Undo to every database change. Each action needs a defined inverse, authorization, expiry, stale-state handling and tests. Recommended next candidates: domain deactivation, reversible visibility toggles and simple edits with prior-value snapshots. Bulk changes require per-item conflict reporting. Password removal, credential revocation, permanent deletion, sent messages and external side effects need separate recovery policies and must not advertise an inverse that cannot be safely performed.
