<x-layouts.public-dashboard title="Links — my.href.nz">
    <x-ui.page-header title="Your Links">
        <x-slot:subtitle>href.nz, meinlink.at &amp; href.yt — managed here. Other domains live on dash.ternis.link.</x-slot:subtitle>
        <x-slot:actions>
            <x-ui.button href="{{ route('public-dashboard.links.export-all') }}" size="sm" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button variant="primary" x-data @click="$dispatch('open-link-creator')">+ Create Link</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <livewire:dashboard.link-table scope="public" />
</x-layouts.public-dashboard>
