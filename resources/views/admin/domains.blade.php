<x-layouts.admin title="Domain Moderation — ternis.link">
    <x-ui.page-header
        title="Domain Moderation"
        subtitle="Disable abusive custom domains or reactivate false positives. System domains are protected. Links and analytics are preserved."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    />

    <x-ui.card>
        <livewire:admin.domain-moderation />
    </x-ui.card>
</x-layouts.admin>
