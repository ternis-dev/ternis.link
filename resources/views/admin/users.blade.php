<x-layouts.admin title="User Management — ternis.link">
    <x-ui.page-header
        title="User Management"
        subtitle="Adjust roles and plans. You cannot demote your own admin account."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.stats')" variant="secondary" size="sm">
                System Telemetry
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (!empty($heatmap))
        <x-ui.card class="mb-6">
            <x-ui.heatmap
                :grid="$heatmap"
                title="Account Signup Heatmap (30d)"
                subtitle="Weekday × UTC hour distribution of new member registrations over the last 30 days."
                unit="signup"
                palette="blue"
                :wrapped="false"
                compact
            />
        </x-ui.card>
    @endif

    <x-ui.card>
        <livewire:admin.user-table />
    </x-ui.card>
</x-layouts.admin>
