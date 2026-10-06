<x-layouts.public-dashboard-legacy title="Links — my.ternis.link">
    <div class="mb-6">
        <h2 class="font-display text-2xl font-bold tracking-tight">Your Links</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">href.nz, meinlink.at &amp; href.yt — everything else lives on dash.ternis.link.</p>
    </div>

    <div class="pd-card mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ auth()->user()->name }} · {{ auth()->user()->plan?->name ?? 'free' }} plan</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('public-dashboard.legacy.links.import') }}" class="rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">Import CSV</a>
            <a href="{{ route('public-dashboard.legacy.links.export-all') }}" class="rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">Export CSV</a>
            <a href="{{ route('public-dashboard.legacy.links.qr-zip') }}" class="rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">QR ZIP</a>
            <button type="button" x-data @click="$dispatch('open-link-creator')" class="cursor-pointer rounded-full bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-indigo-500">+ New link</button>
        </div>
    </div>

    <livewire:dashboard.link-table scope="public" theme="public" />
</x-layouts.public-dashboard-legacy>
