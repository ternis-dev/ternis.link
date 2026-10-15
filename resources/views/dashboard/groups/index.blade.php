<x-layouts.dashboard title="Link Groups — ternis.link">
    <x-ui.page-header title="Link Groups">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.groups.create') }}" variant="primary">+ New Group</x-ui.button>
        </x-slot:actions>
        <x-slot:subtitle>
            Bundle links into campaigns, clients, or topics. Click stats combine across the group — including nested subgroups.
        </x-slot:subtitle>
    </x-ui.page-header>

    <livewire:dashboard.group-tree />
</x-layouts.dashboard>
