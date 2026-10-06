<x-layouts.public-dashboard title="API Key Links — my.ternis.link">
    <x-pd.head
        :title="$apiKey->name"
        subtitle="Public links created with this key. Key management lives on dash.ternis.link."
        :backHref="route('public-dashboard.links')"
        backLabel="Back to Links"
    >
        <x-slot:actions>
            <x-pd.chip>{{ $apiKey->key_prefix }}…</x-pd.chip>
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-6 grid grid-cols-2 gap-4">
        <x-pd.stat :value="number_format($stats['total_links'])" label="Key links" />
        <x-pd.stat :value="number_format($stats['total_clicks'])" label="Key clicks" />
    </div>

    <livewire:dashboard.link-table :api-key-id="$apiKey->id" :key="'public-key-'.$apiKey->id" scope="public" theme="public" />
</x-layouts.public-dashboard>
