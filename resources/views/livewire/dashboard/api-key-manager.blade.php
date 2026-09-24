<div>
    <x-ui.card title="Generate API Key" class="mb-8 max-w-2xl">
        <p class="mb-5 text-sm text-neutral-500 dark:text-neutral-400">
            API keys allow you to authenticate with <code>links.t-api.de</code> programmatically.
        </p>

        @if ($newlyCreatedKey)
            <x-ui.alert tone="success" class="mb-5">
                <strong>New API Key Generated:</strong><br>
                <div class="my-3 rounded-lg border border-neutral-300 bg-neutral-100 p-3 font-mono text-sm break-all dark:border-neutral-700 dark:bg-neutral-950">
                    {{ $newlyCreatedKey }}
                </div>
                <small class="mb-3 block">Make sure to copy your API key now. You won't be able to see it again!</small>
                <div class="flex gap-2">
                    <x-ui.button
                        size="sm"
                        variant="primary"
                        onclick="navigator.clipboard.writeText(@js($newlyCreatedKey)).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy key', 2000); })"
                    >Copy key</x-ui.button>
                    <x-ui.button wire:click="dismissNewKey" size="sm">I have saved my key</x-ui.button>
                </div>
            </x-ui.alert>
        @endif

        <form wire:submit="createKey" class="flex flex-wrap items-end gap-3">
            <div class="min-w-60 flex-1">
                <x-ui.input
                    label="Key Label / Name *"
                    name="keyName"
                    type="text"
                    wire:model="keyName"
                    placeholder="e.g. CLI Script, Production Server"
                    required
                />
            </div>
            <x-ui.button type="submit" variant="primary">Generate Key</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Active API Keys">
        <x-ui.table>
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Prefix</th>
                    <th>API Version</th>
                    <th>Created</th>
                    <th>Last Used</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($apiKeys as $key)
                    <tr>
                        <td class="font-semibold">{{ $key->name }}</td>
                        <td><code>{{ $key->masked_key }}</code></td>
                        <td>v{{ $key->api_version }}</td>
                        <td class="text-xs text-neutral-500">{{ $key->created_at->format('M d, Y') }}</td>
                        <td class="text-xs text-neutral-500">
                            {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td>
                            @if ($key->isValid())
                                <x-ui.status state="active" />
                            @else
                                <x-ui.status state="disabled" />
                            @endif
                        </td>
                        <td>
                            @if ($key->isValid())
                                <x-ui.button wire:click="revokeKey({{ $key->id }})" wire:confirm="Revoke this API key immediately?" size="sm" variant="danger">Revoke</x-ui.button>
                            @else
                                <span class="text-xs text-neutral-500">Revoked</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-ui.empty-state>No API keys yet. Generate one above to access the API.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</div>
