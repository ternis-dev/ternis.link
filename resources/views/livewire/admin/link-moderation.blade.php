<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="moderation-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by slug, destination URL, description or tag..."
            class="max-w-xs"
        />
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
            <x-ui.select name="moderation-status" wire:model.live="status" class="w-auto">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
                <option value="removed">Removed</option>
                <option value="expired">Expired</option>
            </x-ui.select>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'slug')
                        <th aria-sort="{{ $sortBy === 'slug' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sort('slug')" class="inline-flex cursor-pointer items-center gap-1 uppercase hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                                Slug
                                @if ($sortBy === 'slug') <span aria-hidden="true">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                            </button>
                        </th>
                    @elseif ($column === 'destination')
                        <th>Destination</th>
                    @elseif ($column === 'domain')
                        <th>Domain</th>
                    @elseif ($column === 'owner')
                        <th>Owner</th>
                    @elseif ($column === 'clicks')
                        <th aria-sort="{{ $sortBy === 'click_count' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sort('click_count')" class="inline-flex cursor-pointer items-center gap-1 uppercase hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                                Clicks
                                @if ($sortBy === 'click_count') <span aria-hidden="true">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                            </button>
                        </th>
                    @elseif ($column === 'status')
                        <th>Status</th>
                    @endif
                @endforeach
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($links as $link)
                <tr>
                    @foreach ($visibleColumns as $column)
                        @if ($column === 'slug')
                            <td class="font-semibold">{{ $link->slug }}</td>
                        @elseif ($column === 'destination')
                            <td class="max-w-[280px]">
                                <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="block truncate text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">
                                    {{ $link->destination_url }}
                                </a>
                                @if ($link->description)
                                    <p class="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-500" title="{{ $link->description }}">{{ $link->description }}</p>
                                @endif
                                @if (! empty($link->tags))
                                    <p class="mt-1 flex flex-wrap gap-1">
                                        @foreach (array_slice($link->tags, 0, 3) as $tag)
                                            <span class="inline-flex items-center rounded-full border border-neutral-300 px-1.5 py-px text-[11px] font-medium text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">{{ $tag }}</span>
                                        @endforeach
                                        @if (count($link->tags) > 3)
                                            <span class="text-[11px] text-neutral-400 dark:text-neutral-600">+{{ count($link->tags) - 3 }}</span>
                                        @endif
                                    </p>
                                @endif
                            </td>
                        @elseif ($column === 'domain')
                            <td><code>{{ $link->domain->hostname ?? '—' }}</code></td>
                        @elseif ($column === 'owner')
                            <td class="tl-sensitive text-xs text-neutral-500" title="{{ $link->user?->email ?? 'Guest' }}">
                                {{ $link->user?->email ?? 'Guest' }}
                            </td>
                        @elseif ($column === 'clicks')
                            <td class="font-bold">{{ number_format($link->click_count) }}</td>
                        @elseif ($column === 'status')
                            <td>
                                @if ($link->is_removed)
                                    <x-ui.status state="removed" />
                                @elseif ($link->is_active && !$link->isExpired())
                                    <x-ui.status state="active" />
                                @elseif ($link->isExpired())
                                    <x-ui.status state="expired" />
                                @else
                                    <x-ui.status state="disabled" />
                                @endif
                            </td>
                        @endif
                    @endforeach
                    <td>
                        <div class="flex gap-2">
                        @if ($link->is_removed)
                            <x-ui.button wire:click="restore('{{ $link->id }}')" size="sm">Restore</x-ui.button>
                            <x-ui.button wire:click="delete('{{ $link->id }}')" wire:confirm="Permanently delete this link? The row and its click details are destroyed — only anonymized aggregates survive in stats." size="sm" variant="danger">Delete</x-ui.button>
                        @else
                            @if ($link->is_active)
                                <x-ui.button wire:click="deactivate('{{ $link->id }}')" wire:confirm="Deactivate this link?" size="sm" variant="danger">Deactivate</x-ui.button>
                            @else
                                <x-ui.button wire:click="reactivate('{{ $link->id }}')" size="sm">Reactivate</x-ui.button>
                            @endif
                            <x-ui.button wire:click="remove('{{ $link->id }}')" wire:confirm="Remove this link? It stops resolving everywhere but stays in stats." size="sm" variant="danger">Remove</x-ui.button>
                        @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}"><x-ui.empty-state>No links found.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $links->links() }}
    </div>

    @error('delete')
        <x-ui.alert tone="error" class="mt-4">{{ $message }}</x-ui.alert>
    @enderror
</div>
