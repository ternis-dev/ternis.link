<x-layouts.public-dashboard title="Links — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Your Links"
        subtitle="Manage, monitor, and configure your href.nz, meinlink.at and href.yt links."
    >
        <x-slot:actions>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.import') }}" variant="secondary" size="sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Import CSV
            </x-pd.button>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.export-all') }}" variant="secondary" size="sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                Export CSV
            </x-pd.button>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.qr-zip') }}" variant="secondary" size="sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect width="6" height="6" x="3" y="3" rx="1"/><rect width="6" height="6" x="15" y="3" rx="1"/><rect width="6" height="6" x="3" y="15" rx="1"/><path d="M15 15h6v6h-6z"/></svg>
                QR ZIP
            </x-pd.button>
            <x-pd.button variant="primary" size="sm" x-data @click="$dispatch('open-link-creator')">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                + New link
            </x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-2">
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ auth()->user()->name }}</span>
            <span>·</span>
            <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/20">{{ ucfirst(auth()->user()->plan?->name ?? 'free') }} plan</span>
        </div>
    </div>

    <livewire:dashboard.link-table scope="public" theme="public" />
</x-layouts.public-dashboard>
