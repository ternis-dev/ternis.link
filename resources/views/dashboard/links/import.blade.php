<x-layouts.dashboard title="Import Links — ternis.link">
    <x-ui.page-header
        title="Import Links"
        subtitle="Bulk-create short links from pasted CSV rows."
        :backHref="route('dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="max-w-3xl">
        <livewire:dashboard.link-import />
    </div>
</x-layouts.dashboard>
