<div>
    <x-ui.card title="Generate API Key" class="mb-8">
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

        <form wire:submit="createKey" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                <div class="md:col-span-5">
                    <x-ui.input
                        label="Key Label / Name *"
                        name="keyName"
                        type="text"
                        wire:model="keyName"
                        placeholder="e.g. CLI Script, Production Server"
                        required
                    />
                </div>
                <div class="md:col-span-3">
                    <x-ui.select
                        label="API Version"
                        name="apiVersion"
                        wire:model="apiVersion"
                    >
                        @foreach ($availableVersions as $ver)
                            <option value="{{ $ver->version }}">v{{ $ver->version }}{{ $ver->version === $latestVersion ? ' (Latest)' : '' }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="md:col-span-4">
                    <x-ui.select
                        label="Expiration"
                        name="expirationChoice"
                        wire:model.live="expirationChoice"
                    >
                        <option value="never">Never expires</option>
                        <option value="30_days">In 30 days</option>
                        <option value="60_days">In 60 days</option>
                        <option value="90_days">In 90 days</option>
                        <option value="1_year">In 1 year</option>
                        <option value="custom">Custom date &amp; time</option>
                    </x-ui.select>
                </div>
            </div>

            @if ($expirationChoice === 'custom')
                <div class="max-w-xs">
                    <x-ui.input
                        label="Custom Expiration Date"
                        name="customExpiresAt"
                        type="datetime-local"
                        wire:model="customExpiresAt"
                    />
                </div>
            @endif

            <div class="pt-1">
                <x-ui.checkbox
                    wire:model="newKeyShowOnDashboard"
                    name="newKeyShowOnDashboard"
                    label="Show links on dashboard"
                    description="When checked, links created with this key appear on your main links list. Unchecked keys keep their links isolated on the key's dedicated page."
                />
            </div>

            <div class="pt-2">
                <x-ui.button type="submit" variant="primary">Generate Key</x-ui.button>
            </div>
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
                    <th>Expires</th>
                    <th>Last Used</th>
                    <th>Dashboard</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($apiKeys as $key)
                    <tr>
                        <td class="font-semibold">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('dashboard.api-keys.show', $key->id) }}" class="underline-offset-2 hover:underline">{{ $key->name }}</a>
                                @if ($key->isValid())
                                    <button
                                        type="button"
                                        wire:click="openSettings('{{ $key->id }}')"
                                        class="cursor-pointer text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
                                        title="Edit key settings"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td><code>{{ $key->masked_key }}</code></td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold {{ $key->api_version === $latestVersion ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400' }}">
                                    v{{ $key->api_version }}
                                </span>
                                @if ($key->isValid() && $key->api_version < $latestVersion)
                                    <button
                                        type="button"
                                        wire:click="updateVersion('{{ $key->id }}', {{ $latestVersion }})"
                                        class="cursor-pointer text-xs font-medium text-primary-600 hover:text-primary-700 underline underline-offset-2 dark:text-primary-400"
                                        title="Upgrade this key to v{{ $latestVersion }}"
                                    >
                                        Upgrade
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="text-xs text-neutral-500 whitespace-nowrap">{{ $key->created_at->format('M d, Y') }}</td>
                        <td class="text-xs whitespace-nowrap">
                            @if (! $key->expires_at)
                                <span class="text-neutral-400 dark:text-neutral-500">Never</span>
                            @elseif ($key->isExpired())
                                <span class="font-medium text-amber-600 dark:text-amber-400" title="{{ $key->expires_at->toRfc7231String() }}">
                                    Expired {{ $key->expires_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-neutral-600 dark:text-neutral-400" title="{{ $key->expires_at->toRfc7231String() }}">
                                    {{ $key->expires_at->format('M d, Y') }}
                                    <span class="text-neutral-400 text-[11px]">({{ $key->expires_at->diffForHumans() }})</span>
                                </span>
                            @endif
                        </td>
                        <td class="text-xs text-neutral-500 whitespace-nowrap">
                            {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td>
                            <button
                                type="button"
                                wire:click="toggleVisibility('{{ $key->id }}')"
                                title="Click to toggle whether links appear on main dashboard"
                                class="cursor-pointer inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $key->show_on_dashboard ? 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700' : 'bg-neutral-50 text-neutral-500 border border-neutral-200 hover:bg-neutral-100 dark:bg-neutral-900 dark:text-neutral-400 dark:border-neutral-700 dark:hover:bg-neutral-800' }}"
                            >
                                <span class="h-1.5 w-1.5 rounded-full {{ $key->show_on_dashboard ? 'bg-emerald-500' : 'bg-neutral-400' }}"></span>
                                {{ $key->show_on_dashboard ? 'Shown' : 'Hidden' }}
                            </button>
                        </td>
                        <td>
                            @if ($key->isRevoked())
                                <x-ui.status state="disabled" />
                            @elseif ($key->isExpired())
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span> Expired
                                </span>
                            @else
                                <x-ui.status state="active" />
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <x-ui.button href="{{ route('dashboard.api-keys.show', $key->id) }}" size="sm">Links</x-ui.button>
                                <x-ui.button wire:click="openSettings('{{ $key->id }}')" size="sm" variant="ghost">Settings</x-ui.button>
                                @if ($key->isValid())
                                    <x-ui.button wire:click="revokeKey('{{ $key->id }}')" wire:confirm="Revoke this API key immediately? Requests using this key will immediately fail." size="sm" variant="danger">Revoke</x-ui.button>
                                @else
                                    <span class="text-xs text-neutral-400 dark:text-neutral-500">Revoked</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-neutral-500 py-6">No API keys found. Generate one above to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    {{-- API Key Settings Modal --}}
    @if ($showingSettingsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modal-settings-title">
            <div class="fixed inset-0 bg-neutral-950/60 backdrop-blur-xs transition-opacity" wire:click="closeSettings"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-xl border border-neutral-200 bg-white p-6 shadow-xl dark:border-neutral-800 dark:bg-neutral-900 transition-all">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h3 id="modal-settings-title" class="font-display text-lg font-semibold tracking-tight text-neutral-900 dark:text-white">
                                API Key Settings
                            </h3>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                                Configure properties and behaviors for this API key.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeSettings"
                            class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-800 dark:hover:text-white"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    <form wire:submit="saveSettings" class="space-y-4">
                        <x-ui.input
                            label="Key Label / Name *"
                            name="settingsName"
                            type="text"
                            wire:model="settingsName"
                            required
                        />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-ui.select
                                label="Target API Version"
                                name="settingsApiVersion"
                                wire:model="settingsApiVersion"
                            >
                                @foreach ($availableVersions as $ver)
                                    <option value="{{ $ver->version }}">v{{ $ver->version }}{{ $ver->version === $latestVersion ? ' (Latest)' : '' }}</option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input
                                label="Expires At (optional)"
                                name="settingsExpiresAt"
                                type="datetime-local"
                                wire:model="settingsExpiresAt"
                                hint="Leave empty for no expiration"
                            />
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3.5 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <x-ui.checkbox
                                wire:model="settingsShowOnDashboard"
                                name="settingsShowOnDashboard"
                                label="Show links on dashboard"
                                description="When enabled, links created with this key appear on the main links list. Otherwise, they only show on this key's dedicated page."
                            />
                        </div>

                        <div class="flex justify-end gap-2.5 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                            <x-ui.button type="button" wire:click="closeSettings" variant="ghost">Cancel</x-ui.button>
                            <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
