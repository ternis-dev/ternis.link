<x-layouts.admin title="Audit Log — ternis.link">
    <x-ui.page-header
        title="Audit Log"
        subtitle="Every recorded user, admin and system action. Append-only."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.stats.show', ['section' => 'activities'])" variant="secondary" size="sm">
                Audit Telemetry &amp; Charts
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (!empty($heatmap))
        <x-ui.card class="mb-6">
            <x-ui.heatmap
                :grid="$heatmap"
                title="Audit Activity Heatmap (30d)"
                subtitle="Weekday × UTC hour distribution of recorded user, admin, and system actions."
                unit="event"
                palette="purple"
                :wrapped="false"
                compact
            />
        </x-ui.card>
    @endif

    <x-ui.card>
        <livewire:admin.activity-log-table />
    </x-ui.card>
</x-layouts.admin>
