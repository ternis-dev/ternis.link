<x-layouts.dashboard title="Edit Link — ternis.link">
    <x-ui.page-header
        title="Edit Short Link"
        subtitle="Change where it points, when it expires, or switch it off."
        :backHref="$backHref ?? route('dashboard.links')"
        :backLabel="$backLabel ?? 'Back to Links'"
    />

    <div class="max-w-2xl">
        <livewire:dashboard.link-edit-form :link="$link" />
    </div>

    <div class="mt-6 max-w-2xl">
        <livewire:dashboard.link-targeting :link="$link" />
    </div>
</x-layouts.dashboard>
