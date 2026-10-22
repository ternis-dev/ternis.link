<x-layouts.hnz-dashboard title="API Key Links — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        :title="$apiKey->name"
        subtitle="Public links created with this key. Key management lives on dash.ternis.link."
        :backHref="\App\Support\PublicDashboard::route('links')"
        backLabel="Back to Links"
    >
        <x-slot:actions>
            <x-hnz.chip>{{ $apiKey->key_prefix }}…</x-pd.chip>
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-6 grid grid-cols-2 gap-4">
        <x-hnz.stat :value="number_format($stats['total_links'])" label="Key links" />
        <x-hnz.stat :value="number_format($stats['total_clicks'])" label="Key clicks" />
    </div>

    <livewire:dashboard.link-table :api-key-id="$apiKey->id" :key="'public-key-'.$apiKey->id" scope="public" theme="public" />
</x-layouts.hnz-dashboard>
