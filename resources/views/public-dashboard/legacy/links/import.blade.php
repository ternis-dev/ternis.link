<x-layouts.public-dashboard-legacy title="Import Links — my.ternis.link">
    <div class="mb-6">
        <a href="{{ route('public-dashboard.legacy.links') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">← Back to Links</a>
        <h2 class="mt-2 font-display text-2xl font-bold tracking-tight">Import Links</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Bulk-create href.nz, meinlink.at &amp; href.yt links from pasted CSV rows.</p>
    </div>

    <div class="pd-card max-w-3xl rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <livewire:dashboard.link-import scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard-legacy>
