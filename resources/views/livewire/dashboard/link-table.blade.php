<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            <x-ui.input
                name="table-search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by slug, URL, description or tag..."
                class="max-w-md flex-1"
            />
            @if ($availableTags !== [])
                <x-ui.select name="table-tag" wire:model.live="tag" class="w-auto" aria-label="Filter by tag">
                    <option value="">All tags</option>
                    @foreach ($availableTags as $availableTag)
                        <option value="{{ $availableTag }}">{{ $availableTag }}</option>
                    @endforeach
                </x-ui.select>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
            <x-ui.button href="{{ route('dashboard.links.create') }}" variant="primary">+ Create Link</x-ui.button>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'slug')
                        <th wire:click="sort('slug')" class="sortable">
                            Short Link
                            @if ($sortBy === 'slug') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </th>
                    @elseif ($column === 'destination')
                        <th>Destination URL</th>
                    @elseif ($column === 'domain')
                        <th>Domain</th>
                    @elseif ($column === 'clicks')
                        <th wire:click="sort('click_count')" class="sortable">
                            Clicks
                            @if ($sortBy === 'click_count') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </th>
                    @elseif ($column === 'status')
                        <th>Status</th>
                    @elseif ($column === 'created')
                        <th wire:click="sort('created_at')" class="sortable">
                            Created
                            @if ($sortBy === 'created_at') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </th>
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
                            <td>
                                <a href="{{ route('dashboard.links.show', $link->id) }}" class="font-semibold underline-offset-2 hover:underline">
                                    {{ $link->slug }}
                                </a>
                            </td>
                        @elseif ($column === 'destination')
                            <td class="max-w-[300px]">
                                <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="block truncate text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">
                                    {{ $link->destination_url }}
                                </a>
                                @if ($link->description)
                                    <p class="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-500" title="{{ $link->description }}">{{ $link->description }}</p>
                                @endif
                                @if (! empty($link->tags))
                                    <p class="mt-1 flex flex-wrap gap-1">
                                        @foreach (array_slice($link->tags, 0, 3) as $tag)
                                            <button type="button" wire:click="$set('tag', @js($tag))" title="Filter by {{ $tag }}" class="inline-flex cursor-pointer items-center rounded-full border border-neutral-300 px-1.5 py-px text-[11px] font-medium text-neutral-600 transition-colors hover:border-neutral-900 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-white dark:hover:text-white">{{ $tag }}</button>
                                        @endforeach
                                        @if (count($link->tags) > 3)
                                            <span class="text-[11px] text-neutral-400 dark:text-neutral-600">+{{ count($link->tags) - 3 }}</span>
                                        @endif
                                    </p>
                                @endif
                            </td>
                        @elseif ($column === 'domain')
                            <td>
                                <code>{{ $link->domain->hostname ?? 'href.nz' }}</code>
                            </td>
                        @elseif ($column === 'clicks')
                            <td class="font-bold">{{ number_format($link->click_count) }}</td>
                        @elseif ($column === 'status')
                            <td>
                                @if ($link->is_active && !$link->isExpired())
                                    <x-ui.status state="active" />
                                @elseif ($link->isExpired())
                                    <x-ui.status state="expired" />
                                @else
                                    <x-ui.status state="disabled" />
                                @endif
                            </td>
                        @elseif ($column === 'created')
                            <td class="text-xs whitespace-nowrap text-neutral-500">
                                {{ $link->created_at->format('M d, Y') }}
                            </td>
                        @endif
                    @endforeach
                    <td>
                        <div class="flex gap-2">
                            <x-ui.button href="{{ route('dashboard.links.show', $link->id) }}" size="sm">Analytics</x-ui.button>
                            <x-ui.button href="{{ route('dashboard.links.edit', $link->id) }}" size="sm">Edit</x-ui.button>
                            @if ($link->is_active)
                                <x-ui.button wire:click="deactivate('{{ $link->id }}')" wire:confirm="Deactivate this link?" size="sm" variant="danger">Deactivate</x-ui.button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}">
                        <x-ui.empty-state>No short links found. <a href="{{ route('dashboard.links.create') }}" class="underline underline-offset-2">Create your first short link!</a></x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $links->links() }}
    </div>
</div>
