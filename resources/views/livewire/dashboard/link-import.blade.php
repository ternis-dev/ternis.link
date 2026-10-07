<div>
    <x-ui.card title="Paste CSV rows">
        <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">
            One link per line: <code>destination_url,domain_hostname,slug,expires_at,description,tags</code>.
            Only the destination is required — empty domain defaults to {{ auth()->user()?->resolvedDefaultDomain($scope)?->hostname ?? (($scope ?? null) === 'public' ? 'href.nz' : 'clicked.at') }}, empty slug auto-generates.
            @if (($scope ?? null) === 'public')
                This importer accepts href.nz, meinlink.at, href.yt and qr.href.nz — other domains belong on dash.ternis.link.
            @endif
            Tags are semicolon-separated. Max {{ \App\Livewire\Dashboard\LinkImport::MAX_ROWS }} rows.
            Quote fields that contain commas.
        </p>

        <textarea wire:model="csv" rows="8" placeholder="https://example.com/long-page,href.nz,my-slug,,Launch page,launch;marketing" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 font-mono text-xs dark:border-neutral-700 dark:bg-neutral-950"></textarea>

        <div class="mt-3 flex gap-3">
            <x-ui.button wire:click="dryRunImport" wire:loading.attr="disabled" wire:target="dryRunImport,import" variant="secondary">Validate only</x-ui.button>
            <x-ui.button wire:click="import" wire:loading.attr="disabled" wire:target="dryRunImport,import" variant="primary" wire:confirm="Import these links?">Import links</x-ui.button>
        </div>
    </x-ui.card>

    @if ($results !== [])
        <x-ui.card title="Results" class="mt-6">
            <ul class="space-y-1 text-sm">
                @foreach ($results as $result)
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true">{{ $result['ok'] ? '✅' : '❌' }}</span>
                        <span class="shrink-0 font-mono text-xs text-neutral-500">row {{ $result['row'] }}</span>
                        <span>{{ $result['message'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
