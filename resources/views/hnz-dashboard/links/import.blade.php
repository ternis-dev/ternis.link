<x-layouts.hnz-dashboard title="Import Links — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        title="Import Links"
        subtitle="Bulk-create href.nz, meinlink.at and href.yt links from pasted CSV rows."
        :backHref="\App\Support\PublicDashboard::route('links')"
        backLabel="Back to Links"
    />

    <div class="nd-card w-full rounded-xl border border-neutral-200 bg-white p-6 sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <livewire:dashboard.link-import scope="public" theme="public" />
    </div>
</x-layouts.hnz-dashboard>
