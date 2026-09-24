<x-layouts.dashboard title="Domain Moderation — ternis.link">
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('admin.dashboard') }}" style="font-size: 0.85rem; color: var(--text-muted); display: inline-block; margin-bottom: 0.5rem;">← Back to Admin Overview</a>
        <h1 style="font-size: 1.75rem; font-weight: 700;">Domain Moderation</h1>
        <p style="color: var(--text-secondary); margin-top: 0.25rem;">
            Disable abusive custom domains or reactivate false positives. System domains are protected. Links and analytics are preserved.
        </p>
    </div>

    <div class="card">
        <livewire:admin.domain-moderation />
    </div>
</x-layouts.dashboard>
