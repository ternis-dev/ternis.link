<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="error-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by message, path, host or exception..."
            class="max-w-xs"
        />
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
            <x-ui.select name="code-filter" wire:model.live="codeFilter" class="w-auto">
                <option value="all">All codes</option>
                <option value="4xx">4xx</option>
                <option value="5xx">5xx</option>
            </x-ui.select>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'when')
                        <th>When</th>
                    @elseif ($column === 'code')
                        <th>Code</th>
                    @elseif ($column === 'request')
                        <th>Request</th>
                    @elseif ($column === 'message')
                        <th>Error</th>
                    @elseif ($column === 'user')
                        <th>User</th>
                    @endif
                @endforeach
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    @foreach ($visibleColumns as $column)
                        @if ($column === 'when')
                            <td class="whitespace-nowrap text-xs text-neutral-500" title="{{ $entry->created_at }}">{{ $entry->created_at?->diffForHumans() }}</td>
                        @elseif ($column === 'code')
                            <td><x-ui.badge>{{ $entry->http_code }}</x-ui.badge></td>
                        @elseif ($column === 'request')
                            <td class="max-w-[280px] text-xs">
                                <span class="font-semibold">{{ $entry->method }}</span>
                                <code class="ml-1">{{ $entry->host }}</code>
                                <span class="block truncate text-neutral-500" title="{{ $entry->path }}">{{ $entry->path }}</span>
                            </td>
                        @elseif ($column === 'message')
                            <td class="max-w-[320px]">
                                <div class="truncate font-medium" title="{{ $entry->error_message }}">{{ $entry->error_message }}</div>
                                <div class="truncate text-xs text-neutral-500" title="{{ $entry->exception_class }}">{{ $entry->exception_class }}</div>
                            </td>
                        @elseif ($column === 'user')
                            <td class="tl-sensitive max-w-[200px] truncate text-xs text-neutral-500" title="{{ $entry->user?->email ?? 'Guest' }}">{{ $entry->user?->email ?? 'Guest' }}</td>
                        @endif
                    @endforeach
                    <td>
                        <x-ui.button wire:click="delete('{{ $entry->id }}')" wire:confirm="Delete this error record?" size="sm" variant="danger">Delete</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}"><x-ui.empty-state>No errors recorded. Quiet systems stay quiet.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $entries->links() }}
    </div>
</div>
