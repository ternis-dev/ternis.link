<x-layouts.public-dashboard title="Create Link — my.href.nz">
    <x-ui.page-header
        title="Create Short Link"
        subtitle="Shorten a URL on href.nz, meinlink.at or href.yt."
        :backHref="route('public-dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="max-w-2xl">
        <livewire:dashboard.link-form scope="public" />
    </div>
</x-layouts.public-dashboard>
