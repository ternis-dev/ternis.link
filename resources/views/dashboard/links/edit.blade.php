<x-layouts.dashboard title="Edit Link — ternis.link">
    <x-ui.page-header
        title="Edit Short Link"
        subtitle="Change where it points, when it expires, or switch it off."
        :backHref="route('dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="max-w-2xl">
        <livewire:dashboard.link-edit-form :link="$link" />
    </div>
</x-layouts.dashboard>
