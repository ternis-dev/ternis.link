<x-layouts.admin title="Link Moderation — ternis.link">
    <x-ui.page-header
        title="Link Moderation"
        subtitle="Deactivate abusive links or reactivate false positives. Analytics are preserved."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    />

    <x-ui.card>
        <livewire:admin.link-moderation />
    </x-ui.card>
</x-layouts.admin>
