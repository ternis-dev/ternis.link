<x-layouts.admin title="Error Encounters — ternis.link">
    <x-ui.page-header
        title="Error Encounters"
        subtitle="Every exception the app rendered, newest first. 5xx entries also paged the admins."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    />

    <x-ui.card>
        <livewire:admin.error-encounter-table />
    </x-ui.card>
</x-layouts.admin>
