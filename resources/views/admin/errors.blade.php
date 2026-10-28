<x-layouts.admin title="Error Encounters — ternis.link">
    <x-ui.page-header
        title="Error Encounters"
        subtitle="Every exception the app rendered, newest first. 5xx entries also paged the admins."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.stats.show', ['section' => 'errors'])" variant="secondary" size="sm">
                Errors Telemetry &amp; Charts
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (!empty($heatmap))
        <x-ui.card class="mb-6">
            <x-ui.heatmap
                :grid="$heatmap"
                title="Error Encounter Heatmap (30d)"
                subtitle="Weekday × UTC hour distribution of 4xx/5xx exceptions over the last 30 days."
                unit="error"
                palette="red"
                :wrapped="false"
                compact
            />
        </x-ui.card>
    @endif

    <x-ui.card>
        <livewire:admin.error-encounter-table />
    </x-ui.card>
</x-layouts.admin>
