<x-layouts.dashboard title="API Key — {{ $apiKey->name }}">
    <x-ui.page-header
        :title="$apiKey->name"
        :backHref="route('dashboard.api-keys')"
        backLabel="Back to API Keys"
    >
        <x-slot:subtitle>
            <code>{{ $apiKey->masked_key }}</code>
            <span class="text-neutral-400">·</span> v{{ $apiKey->api_version }}
            <span class="text-neutral-400">·</span> created {{ $apiKey->created_at->format('M d, Y') }}
            <span class="text-neutral-400">·</span> last used {{ $apiKey->last_used_at ? $apiKey->last_used_at->diffForHumans() : 'never' }}
            <span class="mt-1 block text-xs">
                {{ number_format($stats['total_links']) }} link(s) · {{ number_format($stats['total_clicks']) }} click(s)
                @if (! $apiKey->show_on_dashboard)
                    · hidden from the main links list
                @endif
            </span>
        </x-slot:subtitle>
    </x-ui.page-header>

    <livewire:dashboard.link-table :api-key-id="$apiKey->id" :key="'api-key-'.$apiKey->id" />
</x-layouts.dashboard>
