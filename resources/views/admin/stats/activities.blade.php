<x-layouts.admin title="Audit Activities Stats — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Audit Activities Telemetry',
        'subtitle' => 'System-wide activity log frequency per day, action distribution, and actor analytics.',
        'section' => 'activities',
        'params' => $params,
    ])

    {{-- Filter Bar Form --}}
    <form method="GET" action="{{ route('admin.stats.show', ['section' => 'activities']) }}" class="mb-6 rounded-2xl border border-neutral-200/90 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <input type="hidden" name="days" value="{{ $stats['days'] }}">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Action Type</label>
                <input type="text" name="action" value="{{ $params['action'] ?? '' }}" placeholder="e.g. link.created, auth.login" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Search Keywords</label>
                <input type="text" name="search" value="{{ $params['search'] ?? '' }}" placeholder="Action or IP hash" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Actor / User ID</label>
                <input type="text" name="user_id" value="{{ $params['user_id'] ?? $params['actor_id'] ?? '' }}" placeholder="User / Actor ULID" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 cursor-pointer">
                    Filter
                </button>
                @if (!empty($params['action']) || !empty($params['search']) || !empty($params['user_id']))
                    <a href="{{ route('admin.stats.show', ['section' => 'activities', 'days' => $stats['days']]) }}" class="rounded-lg border border-neutral-300 px-2.5 py-2 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Filtered Activities</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['total_filtered']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">events in selected window</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Activities Today</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['activities_today']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">since 00:00 UTC</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Unique Actors</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['unique_actors']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">individual users logged</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Primary Category</span>
            @php
            arsort($stats['categories']);
            $topCat = array_key_first($stats['categories']);
            @endphp
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $topCat }}</p>
            <p class="mt-1 text-xs text-neutral-500">{{ number_format($stats['categories'][$topCat]) }} events</p>
        </div>
    </div>

    {{-- Category Pills --}}
    <div class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach ($stats['categories'] as $cat => $count)
            <div class="rounded-xl border border-neutral-200/80 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <span class="text-[11px] font-semibold text-neutral-500 uppercase">{{ $cat }}</span>
                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-white">{{ number_format($count) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Daily Timeline Chart --}}
    <div class="mb-8">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['timeline'],
            'title' => 'Audit Activities Per Day',
            'color' => 'purple',
        ])
    </div>

    {{-- Breakdown Grid --}}
    <div class="grid gap-6 lg:grid-cols-2 mb-8">
        {{-- By Action Type --}}
        <x-ui.card title="Activity by Action Type">
            <div class="space-y-3">
                @forelse ($stats['by_action'] as $act)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-semibold text-neutral-800 dark:text-neutral-200">{{ $act['action'] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($act['count']) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $act['percentage'] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $act['percentage'] }}%" class="h-full rounded-full bg-purple-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No activity logs recorded.</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Top Actors --}}
        <x-ui.card title="Most Active Users">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Events</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats['top_users'] as $u)
                        <tr>
                            <td class="font-medium text-xs">{{ $u->actor?->name ?? 'User '.$u->actor_id }}</td>
                            <td class="text-xs text-neutral-500 font-mono">{{ $u->actor?->email ?? '—' }}</td>
                            <td class="font-bold text-neutral-900 dark:text-white">{{ number_format($u->count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-xs text-neutral-500 py-4">No user activity recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>

    {{-- Recent Activities Table --}}
    <x-ui.card title="Recent Audit Log Entries">
        <x-ui.table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Actor</th>
                    <th>IP Hash</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stats['recent'] as $log)
                    <tr>
                        <td class="whitespace-nowrap text-xs text-neutral-500">{{ $log->created_at->format('M d, H:i:s') }}</td>
                        <td>
                            <span class="rounded bg-neutral-100 px-2 py-0.5 font-mono text-xs font-semibold text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="text-xs text-neutral-600 dark:text-neutral-300">
                            {{ $log->actor?->email ?? 'System / Guest' }}
                        </td>
                        <td class="font-mono text-[11px] text-neutral-400">
                            {{ substr($log->ip_hash ?? '', 0, 10) }}…
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-xs text-neutral-500 py-4">No audit activities in this timeframe.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</x-layouts.admin>
