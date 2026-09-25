<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="activity-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by action, subject or actor..."
            class="max-w-xs"
        />
        <x-ui.select name="action-filter" wire:model.live="actionFilter" class="w-auto">
            <option value="all">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}">{{ $action }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                <th>When</th>
                <th>Action</th>
                <th>Subject</th>
                <th>Actor</th>
                <th>Owner</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td class="whitespace-nowrap text-xs text-neutral-500" title="{{ $entry->created_at }}">{{ $entry->created_at?->diffForHumans() }}</td>
                    <td><x-ui.badge>{{ $entry->action }}</x-ui.badge></td>
                    <td class="text-xs">{{ $entry->subject_label ?? '—' }}</td>
                    <td class="text-xs text-neutral-500">{{ $entry->actor?->email ?? 'System' }}</td>
                    <td class="text-xs text-neutral-500">{{ $entry->subjectOwner?->email ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><x-ui.empty-state>No activity recorded yet.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $entries->links() }}
    </div>
</div>
