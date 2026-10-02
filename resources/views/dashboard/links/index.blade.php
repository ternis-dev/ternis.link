<x-layouts.dashboard title="Links — ternis.link">
    <x-ui.page-header title="Your Links">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.links.import') }}" size="sm" variant="secondary">Import CSV</x-ui.button>
            <x-ui.button href="{{ route('dashboard.links.export-all') }}" size="sm" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button variant="primary" x-data @click="$dispatch('open-link-creator')">+ Create Link</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <livewire:dashboard.link-table />
</x-layouts.dashboard>
