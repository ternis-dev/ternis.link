<div>
    @if ($canAdd)
        <x-ui.card title="Add Custom Domain" class="mb-8 max-w-2xl">
            <p class="mb-5 text-sm text-neutral-500 dark:text-neutral-400">
                Point a hostname you own at ternis.link, then verify ownership with a DNS TXT record.
                Verified domains become available in the link creation form.
            </p>

            @if ($justCreatedId && isset($instructions[$justCreatedId]))
                <x-ui.alert tone="success" class="mb-5">
                    <strong>Domain registered — one step left:</strong> publish the TXT record below, then press Verify.
                </x-ui.alert>
            @endif

            <form wire:submit="addDomain" class="flex flex-wrap items-end gap-3">
                <div class="min-w-60 flex-1">
                    <x-ui.input
                        label="Hostname *"
                        name="hostname"
                        type="text"
                        wire:model="hostname"
                        placeholder="e.g. links.example.com"
                        required
                    />
                </div>
                <x-ui.button type="submit" variant="primary">Add Domain</x-ui.button>
            </form>
        </x-ui.card>
    @else
        <x-ui.alert tone="error" class="mb-8 max-w-2xl">
            <strong>Custom domains are not included in your plan ({{ $planName }}).</strong><br>
            Upgrade to a plan with custom domains to register your own hostnames.
            You can still create links on the system domains below.
        </x-ui.alert>
    @endif

    <x-ui.card title="Your Domains" class="mb-8">
        <x-ui.table>
            <thead>
                <tr>
                    <th>Hostname</th>
                    <th>Status</th>
                    <th>Links</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ownDomains as $domain)
                    <tr>
                        <td><code>{{ $domain->hostname }}</code></td>
                        <td>
                            @if ($domain->isVerified())
                                <x-ui.status state="verified" />
                            @else
                                <x-ui.status state="pending" />
                            @endif
                        </td>
                        <td class="text-xs text-neutral-500">{{ $domain->links_count }}</td>
                        <td>
                            <div class="flex gap-2">
                                @if (! $domain->isVerified())
                                    <x-ui.button wire:click="verifyDomain({{ $domain->id }})" size="sm" variant="primary">Verify</x-ui.button>
                                @endif
                                <x-ui.button wire:click="removeDomain({{ $domain->id }})" wire:confirm="Remove {{ $domain->hostname }}? Links on it stop resolving, analytics are preserved." size="sm" variant="danger">Remove</x-ui.button>
                            </div>
                        </td>
                    </tr>
                    @if (! $domain->isVerified() && isset($instructions[$domain->id]))
                        <tr>
                            <td colspan="4" class="bg-neutral-50 dark:bg-neutral-950">
                                <div class="text-sm">
                                    <strong>Verify ownership</strong> — publish this DNS record, wait for propagation, then press Verify:
                                    <div class="my-2 rounded-lg border border-neutral-300 bg-white p-3 font-mono text-xs break-all dark:border-neutral-700 dark:bg-neutral-900">
                                        TXT&nbsp;&nbsp;{{ $instructions[$domain->id]['host'] }}<br>
                                        {{ $instructions[$domain->id]['value'] }}
                                    </div>
                                    <x-ui.button
                                        size="sm"
                                        onclick="navigator.clipboard.writeText(@js($instructions[$domain->id]['value'])).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy token', 2000); })"
                                    >Copy token</x-ui.button>
                                    @error("verify.{$domain->id}") <p class="mt-2 text-xs font-medium" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="4">
                            <x-ui.empty-state>No custom domains yet. Add one above to use your own hostname for short links.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    <x-ui.card title="System Domains">
        <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">
            Always available for link creation — no verification needed.
        </p>
        <x-ui.table>
            <thead>
                <tr>
                    <th>Hostname</th>
                    <th>Type</th>
                    <th>Links</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($systemDomains as $domain)
                    <tr>
                        <td><code>{{ $domain->hostname }}</code></td>
                        <td class="text-xs text-neutral-500">{{ $domain->type->value ?? $domain->type }}</td>
                        <td class="text-xs text-neutral-500">{{ $domain->links_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </x-ui.card>
</div>
