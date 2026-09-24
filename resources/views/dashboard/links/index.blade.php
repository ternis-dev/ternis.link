<x-layouts.dashboard title="Links — ternis.link">
    <x-ui.page-header title="{{ auth()->user()?->isAdmin() ? 'All Links' : 'Your Links' }}">
        @if (auth()->user()?->isAdmin())
            <x-slot:subtitle>Admin view — stats across all users. Click any link for full analytics.</x-slot:subtitle>
        @endif
        <x-slot:actions>
            <x-ui.button variant="primary" x-data @click="$dispatch('open-link-creator')">+ Create Link</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <livewire:dashboard.link-table />
</x-layouts.dashboard>
