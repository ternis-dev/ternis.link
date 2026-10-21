<x-layouts.dashboard title="Create Link — ternis.link">
    <x-ui.page-header
        title="Create Short Link"
        subtitle="Shorten a URL with customized branding and analytics."
        :backHref="route('dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="w-full">
        <livewire:dashboard.link-form scope="personal" />
    </div>
</x-layouts.dashboard>
