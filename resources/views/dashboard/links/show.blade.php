<x-layouts.dashboard title="Link Analytics — {{ $link->slug }}">
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
            <div>
                <a href="{{ route('dashboard.links') }}" style="font-size: 0.85rem; color: var(--text-muted); display: inline-block; margin-bottom: 0.5rem;">← Back to Links</a>
                <h1 style="font-size: 2rem; font-weight: 700;">{{ $link->domain->hostname ?? 'href.nz' }}/<span style="color: var(--primary);">{{ $link->slug }}</span></h1>
                <p style="color: var(--text-secondary); margin-top: 0.25rem;">
                    Target: <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer">{{ $link->destination_url }}</a>
                </p>
            </div>
            <a href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" target="_blank" class="btn btn-secondary btn-sm">Visit Link ↗</a>
        </div>
    </div>

    <livewire:dashboard.link-analytics :link="$link" />
</x-layouts.dashboard>
