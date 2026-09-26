<x-layouts.admin title="Audit Log — ternis.link">
    <x-ui.page-header
        title="Audit Log"
        subtitle="Every recorded user, admin and system action. Append-only."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    />

    <x-ui.card>
        <livewire:admin.activity-log-table />
    </x-ui.card>
</x-layouts.admin>
