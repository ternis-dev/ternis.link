<x-layouts.admin title="Abuse Reports — Admin">
    <x-ui.page-header
        title="Abuse & Threat Reports"
        subtitle="Review community, automated, and security researcher reports for phishing, malware, spam, and illicit links."
    />

    {{-- Overview Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-ui.stat :value="number_format($stats['pending'])" label="Pending Triage" />
        <x-ui.stat :value="number_format($stats['investigating'])" label="Investigating" />
        <x-ui.stat :value="number_format($stats['resolved'])" label="Resolved" />
        <x-ui.stat :value="number_format($stats['dismissed'])" label="Dismissed" />
        <x-ui.stat :value="number_format($stats['total'])" label="Total Reports" />
    </div>

    {{-- Filter Tabs and Search --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.abuse-reports', ['status' => 'pending']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
                Pending ({{ $stats['pending'] }})
            </a>
            <a href="{{ route('admin.abuse-reports', ['status' => 'investigating']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'investigating' ? 'bg-indigo-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
                Investigating ({{ $stats['investigating'] }})
            </a>
            <a href="{{ route('admin.abuse-reports', ['status' => 'resolved']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
                Resolved ({{ $stats['resolved'] }})
            </a>
            <a href="{{ route('admin.abuse-reports', ['status' => 'dismissed']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'dismissed' ? 'bg-neutral-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
                Dismissed ({{ $stats['dismissed'] }})
            </a>
            <a href="{{ route('admin.abuse-reports', ['status' => 'all']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'all' ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
                All ({{ $stats['total'] }})
            </a>
        </div>

        <form method="GET" action="{{ route('admin.abuse-reports') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Search URL, email, reason..."
                class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs text-neutral-900 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
            >
            @if ($search !== '')
                <a href="{{ route('admin.abuse-reports', ['status' => $status]) }}" class="text-xs text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">Clear</a>
            @endif
        </form>
    </div>

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-xs font-semibold uppercase text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                        <th class="pb-3 pr-4">Reported Link</th>
                        <th class="pb-3 pr-4">Category</th>
                        <th class="pb-3 pr-4">Reporter</th>
                        <th class="pb-3 pr-4">Reason & Evidence</th>
                        <th class="pb-3 pr-4">Reported</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 text-right">Moderation Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                            <td class="py-4 pr-4">
                                <div class="max-w-xs break-all font-mono text-xs font-bold text-neutral-900 dark:text-white">
                                    {{ $report->reported_url }}
                                </div>
                                @if ($report->link)
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span class="rounded bg-neutral-200/80 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                            System Link: /{{ $report->link->slug }}
                                        </span>
                                        @if (! $report->link->is_active)
                                            <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ $report->link->destination_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block max-w-xs truncate text-[11px] text-neutral-500 hover:underline dark:text-neutral-400">
                                        Destination: {{ $report->link->destination_url }}
                                    </a>
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-xs whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 font-semibold text-xs {{ in_array($report->category, ['phishing', 'malware'], true) ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200' }}">
                                    {{ $report->categoryLabel() }}
                                </span>
                            </td>
                            <td class="py-4 pr-4 text-xs">
                                <div class="font-medium text-neutral-900 dark:text-white">{{ $report->reporter_email }}</div>
                                @if ($report->reporter_name)
                                    <div class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ $report->reporter_name }}</div>
                                @endif
                            </td>
                            <td class="py-4 pr-4 max-w-sm">
                                <p class="text-xs text-neutral-700 dark:text-neutral-300 whitespace-pre-line leading-relaxed">
                                    {{ $report->reason }}
                                </p>
                                @if ($report->admin_notes)
                                    <div class="mt-2 rounded border border-neutral-200 bg-neutral-50 p-2 text-[11px] text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400">
                                        <strong>Admin note:</strong> {{ $report->admin_notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-xs whitespace-nowrap text-neutral-500">
                                {{ $report->created_at->diffForHumans() }}
                            </td>
                            <td class="py-4 pr-4 whitespace-nowrap text-xs">
                                @if ($report->status === 'pending')
                                    <span class="rounded bg-amber-100 px-2 py-0.5 font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                        Pending
                                    </span>
                                @elseif ($report->status === 'investigating')
                                    <span class="rounded bg-indigo-100 px-2 py-0.5 font-bold text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        Investigating
                                    </span>
                                @elseif ($report->status === 'resolved')
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        Resolved
                                    </span>
                                    @if ($report->action_taken)
                                        <p class="mt-1 text-[10px] text-neutral-500">Action: {{ $report->action_taken }}</p>
                                    @endif
                                @else
                                    <span class="rounded bg-neutral-100 px-2 py-0.5 font-bold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                        Dismissed
                                    </span>
                                @endif
                                @if ($report->reviewer)
                                    <p class="mt-1 text-[10px] text-neutral-400">by {{ $report->reviewer->name ?? 'Admin' }}</p>
                                @endif
                            </td>
                            <td class="py-4 text-right whitespace-nowrap text-xs">
                                @if ($report->status === 'pending' || $report->status === 'investigating')
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($report->status === 'pending')
                                            <form method="POST" action="{{ route('admin.abuse-reports.investigate', $report) }}">
                                                @csrf
                                                <button type="submit" class="rounded bg-neutral-200 px-2 py-1 text-xs font-semibold text-neutral-800 transition hover:bg-neutral-300 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700">
                                                    Investigate
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.abuse-reports.resolve', $report) }}" onsubmit="return confirm('Resolve report and quarantine link?');">
                                            @csrf
                                            <input type="hidden" name="action_taken" value="quarantined">
                                            <button type="submit" class="rounded bg-rose-600 px-2.5 py-1 text-xs font-bold text-white transition hover:bg-rose-700">
                                                Quarantine Link
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.abuse-reports.dismiss', $report) }}" onsubmit="return confirm('Dismiss this report without action?');">
                                            @csrf
                                            <button type="submit" class="rounded border border-neutral-300 px-2 py-1 text-xs text-neutral-600 transition hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                                Dismiss
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-neutral-400">Archived</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-neutral-500">
                                No abuse reports found matching the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="mt-4 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                {{ $reports->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.admin>
