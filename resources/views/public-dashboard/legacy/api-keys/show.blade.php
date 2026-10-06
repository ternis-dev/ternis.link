<x-layouts.public-dashboard-legacy title="API Key Links — my.ternis.link">
    <div class="mb-6">
        <a href="{{ route('public-dashboard.legacy.links') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">← Back to Links</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-bold tracking-tight">{{ $apiKey->name }}</h2>
            <span class="pd-domain-chip">{{ $apiKey->key_prefix }}…</span>
        </div>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Public links created with this key. Key management lives on dash.ternis.link.</p>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4">
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['total_links']) }}</div>
            <div class="pd-stat-label">Key links</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['total_clicks']) }}</div>
            <div class="pd-stat-label">Key clicks</div>
        </div>
    </div>

    <livewire:dashboard.link-table :api-key-id="$apiKey->id" :key="'public-key-'.$apiKey->id" scope="public" theme="public" />
</x-layouts.public-dashboard-legacy>
