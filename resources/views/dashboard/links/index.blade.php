<x-layouts.dashboard title="Links — ternis.link">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">{{ auth()->user()?->isAdmin() ? 'All Links' : 'Your Links' }}</h1>
            @if (auth()->user()?->isAdmin())
                <p style="color: var(--text-secondary); margin-top: 0.25rem; font-size: 0.9rem;">
                    Admin view — stats across all users. Click any link for full analytics.
                </p>
            @endif
        </div>
        <a href="{{ route('dashboard.links.create') }}" class="btn btn-primary">+ Create Link</a>
    </div>

    <livewire:dashboard.link-table />
</x-layouts.dashboard>
