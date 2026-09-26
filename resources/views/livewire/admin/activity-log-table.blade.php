<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="activity-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by action, subject or actor..."
            class="max-w-xs"
        />
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
            <x-ui.select name="action-filter" wire:model.live="actionFilter" class="w-auto">
                <option value="all">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}">{{ $action }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'when')
                        <th>When</th>
                    @elseif ($column === 'action')
                        <th>Action</th>
                    @elseif ($column === 'subject')
                        <th>Subject</th>
                    @elseif ($column === 'actor')
                        <th>Actor</th>
                    @elseif ($column === 'owner')
                        <th>Owner</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    @foreach ($visibleColumns as $column)
                        @if ($column === 'when')
                            <td class="whitespace-nowrap text-xs text-neutral-500" title="{{ $entry->created_at }}">{{ $entry->created_at?->diffForHumans() }}</td>
                        @elseif ($column === 'action')
                            <td><x-ui.badge>{{ $entry->action }}</x-ui.badge></td>
                        @elseif ($column === 'subject')
                            <td class="text-xs">{{ $entry->subject_label ?? '—' }}</td>
                        @elseif ($column === 'actor')
                            <td class="tl-sensitive text-xs text-neutral-500" title="{{ $entry->actor?->email ?? 'System' }}">{{ $entry->actor?->email ?? 'System' }}</td>
                        @elseif ($column === 'owner')
                            <td class="tl-sensitive text-xs text-neutral-500" title="{{ $entry->subjectOwner?->email ?? '—' }}">{{ $entry->subjectOwner?->email ?? '—' }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}"><x-ui.empty-state>No activity recorded yet.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $entries->links() }}
    </div>
</div>
