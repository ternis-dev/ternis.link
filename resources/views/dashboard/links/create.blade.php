<x-layouts.dashboard title="Create Link — ternis.link">
    <x-ui.page-header
        title="Create Short Link"
        subtitle="Shorten a URL with customized branding and analytics."
        :backHref="route('dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="max-w-2xl">
        <livewire:dashboard.link-form />
    </div>
</x-layouts.dashboard>
