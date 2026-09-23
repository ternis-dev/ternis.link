<x-layouts.dashboard title="User Management — ternis.link">
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('admin.dashboard') }}" style="font-size: 0.85rem; color: var(--text-muted); display: inline-block; margin-bottom: 0.5rem;">← Back to Admin Overview</a>
        <h1 style="font-size: 1.75rem; font-weight: 700;">User Management</h1>
        <p style="color: var(--text-secondary); margin-top: 0.25rem;">
            Adjust roles and plans. You cannot demote your own admin account.
        </p>
    </div>

    <div class="card">
        <livewire:admin.user-table />
    </div>
</x-layouts.dashboard>
