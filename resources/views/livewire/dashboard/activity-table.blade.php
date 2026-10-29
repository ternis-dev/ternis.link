<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-1 flex-wrap items-center gap-2">
            <x-ui.input
                name="activity-search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search activity or token name..."
                class="max-w-xs"
            />
            <x-ui.select name="source-filter" wire:model.live="sourceFilter" class="w-auto">
                <option value="all">All sources</option>
                <option value="api">API tokens only</option>
                <option value="web">Web UI only</option>
            </x-ui.select>
            @if ($apiKeys->isNotEmpty())
                <x-ui.select name="key-filter" wire:model.live="keyFilter" class="w-auto">
                    <option value="all">All API keys</option>
                    @foreach ($apiKeys as $key)
                        <option value="{{ $key->id }}">{{ $key->name }} ({{ $key->key_prefix }}…)</option>
                    @endforeach
                </x-ui.select>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    <th>{{ $availableColumns[$column] ?? $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    @foreach ($visibleColumns as $column)
                        @switch($column)
                            @case('when')
                                <td class="whitespace-nowrap text-xs text-neutral-500" title="{{ $entry->created_at }}">{{ $entry->created_at?->diffForHumans() }}</td>
                                @break

                            @case('action')
                                <td><x-ui.badge>{{ $entry->action }}</x-ui.badge></td>
                                @break

                            @case('subject')
                                <td class="text-xs">{{ $entry->subject_label ?? '—' }}</td>
                                @break

                            @case('by')
                                <td class="text-xs text-neutral-500">
                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $entry->actor?->email ?? 'System' }}</div>
                                    @if ($entry->tokenLabel())
                                        <div class="mt-0.5 inline-flex items-center gap-1 font-mono text-[11px] text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300 rounded px-1.5 py-0.5" title="Performed with API Token">
                                            <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m21 2-2 2m-1.5 1.5L14 9l-1.5-1.5L11 9l1.5 1.5L9 14l-4 4-3-3 4-4 3.5-3.5L8 5.5 9.5 4 11 5.5l3.5-3.5Z"/></svg>
                                            <span>{{ $entry->tokenLabel() }}</span>
                                        </div>
                                    @endif
                                </td>
                                @break
                        @endswitch
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}">
                        <x-ui.empty-state>No activity yet. Links you create and changes to your account will appear here.</x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if ($entries->hasPages())
        <div class="mt-6">{{ $entries->links() }}</div>
    @endif
</div>
