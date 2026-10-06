<x-layouts.public-dashboard title="Import Links — my.ternis.link">
    <x-pd.head
        title="Import Links"
        subtitle="Bulk-create href.nz, meinlink.at and href.yt links from pasted CSV rows."
        :backHref="route('public-dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="nd-card max-w-3xl rounded-xl border border-neutral-200 bg-white p-6 sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <livewire:dashboard.link-import scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard>
