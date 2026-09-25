<x-layouts.dashboard title="Activity — ternis.link">
    <x-ui.page-header
        title="Activity"
        subtitle="Actions you performed, plus admin and system actions on your stuff."
    />

    <x-ui.card>
        <x-ui.table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Action</th>
                    <th>Subject</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td class="whitespace-nowrap text-xs text-neutral-500" title="{{ $entry->created_at }}">{{ $entry->created_at?->diffForHumans() }}</td>
                        <td><x-ui.badge>{{ $entry->action }}</x-ui.badge></td>
                        <td class="text-xs">{{ $entry->subject_label ?? '—' }}</td>
                        <td class="text-xs text-neutral-500">{{ $entry->actor?->email ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4"><x-ui.empty-state>No activity yet. Links you create and changes to your account will appear here.</x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    @if ($entries->hasPages())
        <div class="mt-6">{{ $entries->links() }}</div>
    @endif
</x-layouts.dashboard>
