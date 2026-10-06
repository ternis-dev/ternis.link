<x-layouts.public-dashboard title="Links — my.ternis.link">
    <x-pd.head
        title="Your Links"
        subtitle="href.nz, meinlink.at and href.yt — everything else lives on dash.ternis.link."
    >
        <x-slot:actions>
            <x-pd.button href="{{ route('public-dashboard.links.import') }}" variant="secondary" size="sm">Import CSV</x-pd.button>
            <x-pd.button href="{{ route('public-dashboard.links.export-all') }}" variant="secondary" size="sm">Export CSV</x-pd.button>
            <x-pd.button href="{{ route('public-dashboard.links.qr-zip') }}" variant="secondary" size="sm">QR ZIP</x-pd.button>
            <x-pd.button variant="primary" size="sm" x-data @click="$dispatch('open-link-creator')">+ New link</x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 text-sm text-neutral-500 dark:text-neutral-400">
        <p>{{ auth()->user()->name }} · {{ auth()->user()->plan?->name ?? 'free' }} plan</p>
    </div>

    <livewire:dashboard.link-table scope="public" theme="public" />
</x-layouts.public-dashboard>
