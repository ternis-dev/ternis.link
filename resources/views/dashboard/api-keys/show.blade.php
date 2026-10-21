<x-layouts.dashboard title="API Key — {{ $apiKey->name }}">
    <x-ui.page-header
        :title="$apiKey->name"
        :backHref="route('dashboard.api-keys')"
        backLabel="Back to API Keys"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.links.export-all', ['api_key_id' => $apiKey->id]) }}" size="sm" variant="secondary">Export CSV</x-ui.button>
        </x-slot:actions>
        <x-slot:subtitle>
            <code>{{ $apiKey->masked_key }}</code>
            <span class="text-neutral-400">·</span> v{{ $apiKey->api_version }}
            <span class="text-neutral-400">·</span> created {{ $apiKey->created_at->format('M d, Y') }}
            <span class="text-neutral-400">·</span> last used {{ $apiKey->last_used_at ? $apiKey->last_used_at->diffForHumans() : 'never' }}
            <span class="text-neutral-400">·</span>
            @if ($apiKey->isExpired())
                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/10 ring-inset dark:bg-red-950/40 dark:text-red-400">
                    expired {{ $apiKey->expires_at->format('M d, Y') }}
                </span>
            @elseif ($apiKey->expires_at)
                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/10 ring-inset dark:bg-amber-950/40 dark:text-amber-400">
                    expires {{ $apiKey->expires_at->diffForHumans() }} ({{ $apiKey->expires_at->format('M d, Y') }})
                </span>
            @else
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/10 ring-inset dark:bg-emerald-950/40 dark:text-emerald-400">
                    never expires
                </span>
            @endif
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
