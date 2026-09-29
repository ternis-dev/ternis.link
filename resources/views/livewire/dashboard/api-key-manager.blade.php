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
            <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                <input type="checkbox" wire:model="newKeyShowOnDashboard" class="h-4 w-4 rounded" />
                Show its links on the dashboard
            </label>
            <x-ui.button type="submit" variant="primary">Generate Key</x-ui.button>
        </form>
        <p class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">
            Unchecked keys hide their links from the main list — they stay visible on a dedicated per-key page.
        </p>
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
                    <th>Dashboard</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($apiKeys as $key)
                    <tr>
                        <td class="font-semibold">
                            @if ($editingKeyId === $key->id)
                                <form wire:submit="saveKeyName" class="flex items-center gap-1.5">
                                    <input
                                        type="text"
                                        wire:model="editingKeyName"
                                        class="h-7 rounded border border-neutral-300 px-2 text-xs text-neutral-900 focus:border-neutral-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                        required
                                        autofocus
                                    />
                                    <x-ui.button type="submit" size="sm" variant="primary">Save</x-ui.button>
                                    <x-ui.button type="button" wire:click="cancelEditing" size="sm">Cancel</x-ui.button>
                                </form>
                            @else
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('dashboard.api-keys.show', $key->id) }}" class="underline-offset-2 hover:underline">{{ $key->name }}</a>
                                    @if ($key->isValid())
                                        <button
                                            type="button"
                                            wire:click="startEditing('{{ $key->id }}')"
                                            class="cursor-pointer text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
                                            title="Rename key"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td><code>{{ $key->masked_key }}</code></td>
                        <td>v{{ $key->api_version }}</td>
                        <td class="text-xs text-neutral-500">{{ $key->created_at->format('M d, Y') }}</td>
                        <td class="text-xs text-neutral-500">
                            {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td>
                            <button type="button" wire:click="toggleVisibility('{{ $key->id }}')" title="Toggle dashboard visibility" class="cursor-pointer text-xs underline underline-offset-2">
                                {{ $key->show_on_dashboard ? 'Shown' : 'Hidden' }}
                            </button>
                        </td>
                        <td>
                            @if ($key->isValid())
                                <x-ui.status state="active" />
                            @else
                                <x-ui.status state="disabled" />
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <x-ui.button href="{{ route('dashboard.api-keys.show', $key->id) }}" size="sm">Links</x-ui.button>
                                @if ($key->isValid())
                                    <x-ui.button wire:click="revokeKey('{{ $key->id }}')" wire:confirm="Revoke this API key immediately?" size="sm" variant="danger">Revoke</x-ui.button>
                                @else
                                    <span class="text-xs text-neutral-500">Revoked</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-ui.empty-state>No API keys yet. Generate one above to access the API.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</div>
