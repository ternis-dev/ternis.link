<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="moderation-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by slug or destination URL..."
            class="max-w-xs"
        />
        <x-ui.select name="moderation-status" wire:model.live="status" class="w-auto">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
            <option value="expired">Expired</option>
        </x-ui.select>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                <th wire:click="sort('slug')" class="sortable">
                    Slug
                    @if ($sortBy === 'slug') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                </th>
                <th>Destination</th>
                <th>Domain</th>
                <th>Owner</th>
                <th wire:click="sort('click_count')" class="sortable">
                    Clicks
                    @if ($sortBy === 'click_count') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                </th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($links as $link)
                <tr>
                    <td class="font-semibold">{{ $link->slug }}</td>
                    <td class="max-w-[280px] truncate">
                        <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">
                            {{ $link->destination_url }}
                        </a>
                    </td>
                    <td><code>{{ $link->domain->hostname ?? '—' }}</code></td>
                    <td class="text-xs text-neutral-500">
                        {{ $link->user?->email ?? 'Guest' }}
                    </td>
                    <td class="font-bold">{{ number_format($link->click_count) }}</td>
                    <td>
                        @if ($link->is_active && !$link->isExpired())
                            <x-ui.status state="active" />
                        @elseif ($link->isExpired())
                            <x-ui.status state="expired" />
                        @else
                            <x-ui.status state="disabled" />
                        @endif
                    </td>
                    <td>
                        @if ($link->is_active)
                            <x-ui.button wire:click="deactivate({{ $link->id }})" wire:confirm="Deactivate this link?" size="sm" variant="danger">Deactivate</x-ui.button>
                        @else
                            <x-ui.button wire:click="reactivate({{ $link->id }})" size="sm">Reactivate</x-ui.button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><x-ui.empty-state>No links found.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $links->links() }}
    </div>
</div>
