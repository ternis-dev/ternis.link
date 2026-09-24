<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="domain-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by hostname or owner email..."
            class="max-w-xs"
        />
        <x-ui.select name="domain-status" wire:model.live="status" class="w-auto">
            <option value="all">All domains</option>
            <option value="system">System</option>
            <option value="verified">Verified</option>
            <option value="pending">Pending DNS</option>
            <option value="disabled">Disabled</option>
        </x-ui.select>
    </div>

    @error('domain') <x-ui.alert tone="error" class="mb-4">{{ $message }}</x-ui.alert> @enderror

    <x-ui.table>
        <thead>
            <tr>
                <th wire:click="sort('hostname')" class="sortable">
                    Hostname
                    @if ($sortBy === 'hostname') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                </th>
                <th>Owner</th>
                <th>Type</th>
                <th>Links</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($domains as $domain)
                <tr>
                    <td><code>{{ $domain->hostname }}</code></td>
                    <td class="text-xs text-neutral-500">
                        {{ $domain->user?->email ?? 'System' }}
                    </td>
                    <td class="text-xs text-neutral-500">{{ $domain->type->value ?? $domain->type }}</td>
                    <td class="font-bold">{{ number_format($domain->links_count) }}</td>
                    <td>
                        @if ($domain->isSystemDomain())
                            <x-ui.status state="system" />
                        @elseif (! $domain->is_active)
                            <x-ui.status state="disabled" />
                        @elseif ($domain->isVerified())
                            <x-ui.status state="verified" />
                        @else
                            <x-ui.status state="pending" />
                        @endif
                    </td>
                    <td>
                        @if ($domain->isSystemDomain())
                            <span class="text-xs text-neutral-500">Protected</span>
                        @elseif ($domain->is_active)
                            <x-ui.button wire:click="deactivate('{{ $domain->id }}')" wire:confirm="Disable {{ $domain->hostname }}? Links on it stop resolving, analytics are preserved." size="sm" variant="danger">Disable</x-ui.button>
                        @else
                            <x-ui.button wire:click="reactivate('{{ $domain->id }}')" size="sm">Reactivate</x-ui.button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><x-ui.empty-state>No domains found.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $domains->links() }}
    </div>
</div>
