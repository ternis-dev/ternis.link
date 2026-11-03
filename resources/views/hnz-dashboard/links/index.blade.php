<x-layouts.hnz-dashboard title="Links — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        title="Your Links"
        subtitle="href.nz, meinlink.at and href.yt — everything else lives on dash.ternis.link."
    >
        <x-slot:actions>
            <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.import') }}" variant="secondary" size="sm">Import CSV</x-hnz.button>
            <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.export-all') }}" variant="secondary" size="sm">Export CSV</x-hnz.button>
            <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.qr-zip') }}" variant="secondary" size="sm">QR ZIP</x-hnz.button>
            <x-hnz.button variant="primary" size="sm" x-data @click="$dispatch('open-link-creator')">+ New link</x-hnz.button>
        </x-slot:actions>
    </x-hnz.head>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-xs text-neutral-500 dark:text-neutral-400">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>{{ auth()->user()->name }}</span>
            <span>·</span>
            <span class="rounded-full bg-violet-50 px-2 py-0.5 font-semibold text-violet-700 dark:bg-violet-400/10 dark:text-violet-300">
                {{ auth()->user()->plan?->name ?? 'Free Tier' }}
            </span>
        </div>
        <p>Shortened links redirect instantly with full analytics.</p>
    </div>

    <div class="rounded-3xl border border-neutral-200/80 bg-white/80 p-5 shadow-sm shadow-neutral-900/5 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80">
        <livewire:dashboard.link-table scope="public" theme="public" />
    </div>
</x-layouts.hnz-dashboard>
