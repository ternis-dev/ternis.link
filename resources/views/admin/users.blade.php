<x-layouts.dashboard title="User Management — ternis.link">
    <x-ui.page-header
        title="User Management"
        subtitle="Adjust roles and plans. You cannot demote your own admin account."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    />

    <x-ui.card>
        <livewire:admin.user-table />
    </x-ui.card>
</x-layouts.dashboard>
