<div>
    <div class="mb-4 flex flex-wrap items-center justify-end gap-3">
        @include('livewire.partials.column-customizer')
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
                                <td class="text-xs text-neutral-500">{{ $entry->actor?->email ?? 'System' }}</td>
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
