<x-layouts.dashboard title="Links — ternis.link">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="font-size: 1.75rem; font-weight: 700;">Your Links</h1>
        <a href="{{ route('dashboard.links.create') }}" class="btn btn-primary">+ Create Link</a>
    </div>

    <livewire:dashboard.link-table />
</x-layouts.dashboard>
