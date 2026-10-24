<x-layouts.public-dashboard title="Import Links — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Import Links"
        subtitle="Bulk-create href.nz, meinlink.at and href.yt links from pasted CSV rows."
        :backHref="\App\Support\PublicDashboard::route('links')"
        backLabel="Back to Links"
    />

    <div class="nd-card w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800/80 dark:bg-[#111720]">
        <livewire:dashboard.link-import scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard>
