<x-layouts.admin title="Spam & Threat Appeals — Admin">
    <x-ui.page-header
        title="Spam & Threat Appeals"
        subtitle="Review appeals submitted by users or guests for links flagged or quarantined by TypeSafe AI."
    />

    {{-- Overview Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat :value="number_format($stats['pending'])" label="Pending Review" />
        <x-ui.stat :value="number_format($stats['approved'])" label="Appeals Approved" />
        <x-ui.stat :value="number_format($stats['rejected'])" label="Appeals Rejected" />
        <x-ui.stat :value="number_format($stats['total'])" label="Total Appeals" />
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.appeals', ['status' => 'pending']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            Pending ({{ $stats['pending'] }})
        </a>
        <a href="{{ route('admin.appeals', ['status' => 'approved']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            Approved ({{ $stats['approved'] }})
        </a>
        <a href="{{ route('admin.appeals', ['status' => 'rejected']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            Rejected ({{ $stats['rejected'] }})
        </a>
        <a href="{{ route('admin.appeals', ['status' => 'all']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'all' ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            All ({{ $stats['total'] }})
        </a>
    </div>

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-xs font-semibold uppercase text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                        <th class="pb-3 pr-4">Link & Destination</th>
                        <th class="pb-3 pr-4">Submitter</th>
                        <th class="pb-3 pr-4">Reason for Appeal</th>
                        <th class="pb-3 pr-4">Submitted</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse ($appeals as $appeal)
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                            <td class="py-4 pr-4">
                                @if ($appeal->link)
                                    <div class="font-mono text-xs font-bold text-neutral-900 dark:text-white">
                                        {{ ($appeal->link->domain->hostname ?? 'href.nz').'/'.$appeal->link->slug }}
                                    </div>
                                    <a href="{{ $appeal->link->destination_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block max-w-xs truncate text-xs text-neutral-500 hover:underline dark:text-neutral-400">
                                        {{ $appeal->link->destination_url }}
                                    </a>
                                    @if ($eval = $appeal->link->threatEvaluations?->sortByDesc('id')->first())
                                        <div class="mt-2 rounded border border-rose-200 bg-rose-50/70 p-2 text-[11px] text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                            <div class="font-bold flex items-center gap-1.5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                <span>AI Flag: <span class="uppercase">{{ $eval->verdict }}</span> (Threat: {{ $eval->threat_score }}/100)</span>
                                            </div>
                                            @if ($eval->reasoning)
                                                <p class="mt-0.5 text-rose-700 dark:text-rose-400 font-normal truncate" title="{{ $eval->reasoning }}">{{ $eval->reasoning }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs text-neutral-400">Deleted Link</span>
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-xs">
                                <span class="font-medium text-neutral-900 dark:text-white">{{ $appeal->contact_email }}</span>
                                @if ($appeal->user)
                                    <p class="text-[11px] text-neutral-400">User #{{ $appeal->user->id }}</p>
                                @else
                                    <p class="text-[11px] text-neutral-400">Guest</p>
                                @endif
                            </td>
                            <td class="py-4 pr-4 max-w-sm">
                                <p class="text-xs text-neutral-700 dark:text-neutral-300 whitespace-pre-line leading-relaxed">
                                    {{ $appeal->reason }}
                                </p>
                                @if ($appeal->admin_notes)
                                    <div class="mt-2 rounded border border-neutral-200 bg-neutral-50 p-2 text-[11px] text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400">
                                        <strong>Admin note:</strong> {{ $appeal->admin_notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-xs whitespace-nowrap text-neutral-500">
                                {{ $appeal->created_at->diffForHumans() }}
                            </td>
                            <td class="py-4 pr-4 whitespace-nowrap text-xs">
                                @if ($appeal->isPending())
                                    <span class="rounded bg-amber-100 px-2 py-0.5 font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                        Pending
                                    </span>
                                @elseif ($appeal->isApproved())
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        Approved
                                    </span>
                                    @if ($appeal->reviewer)
                                        <p class="mt-1 text-[10px] text-neutral-400">by {{ $appeal->reviewer->name ?? 'Admin' }}</p>
                                    @endif
                                @else
                                    <span class="rounded bg-rose-100 px-2 py-0.5 font-bold text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                        Rejected
                                    </span>
                                    @if ($appeal->reviewer)
                                        <p class="mt-1 text-[10px] text-neutral-400">by {{ $appeal->reviewer->name ?? 'Admin' }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="py-4 text-right whitespace-nowrap">
                                @if ($appeal->isPending())
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.appeals.approve', $appeal) }}" onsubmit="return confirm('Approve appeal and restore link?');">
                                            @csrf
                                            <button type="submit" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-500 transition-colors">
                                                Approve & Restore
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.appeals.reject', $appeal) }}" onsubmit="return confirm('Reject appeal and keep link quarantined?');">
                                            @csrf
                                            <button type="submit" class="rounded bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-500 transition-colors">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-neutral-400">Resolved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-neutral-500">
                                No spam appeals found matching status "{{ $status }}".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($appeals->hasPages())
            <div class="mt-6 border-t border-neutral-100 pt-4 dark:border-neutral-800">
                {{ $appeals->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.admin>
