<x-layouts.admin title="Link Moderation — ternis.link">
    <x-ui.page-header
        title="Link Moderation"
        subtitle="Deactivate abusive links or reactivate false positives. Analytics are preserved."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.stats.show', ['section' => 'links'])" variant="secondary" size="sm">
                Links Telemetry &amp; Charts
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (!empty($heatmap))
        <x-ui.card class="mb-6">
            <x-ui.heatmap
                :grid="$heatmap"
                title="Link Creation Heatmap (30d)"
                subtitle="Weekday × UTC hour distribution of new short links over the last 30 days."
                unit="link"
                palette="emerald"
                :wrapped="false"
                compact
            />
        </x-ui.card>
    @endif

    <x-ui.card>
        <livewire:admin.link-moderation />
    </x-ui.card>
</x-layouts.admin>
