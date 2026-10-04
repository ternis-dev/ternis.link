<x-layouts.admin title="System Stats &amp; Overview — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Stats &amp; System Analytics',
        'subtitle' => 'High-level telemetry, link traffic, conversion metrics, and system activity.',
        'section' => 'overview',
        'params' => $params,
    ])

    {{-- Highlight KPI Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Link/Clicks Ratio</span>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">System Avg</span>
            </div>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ $stats['overall_ratio'] }}</p>
            <p class="mt-1 text-xs text-neutral-500">clicks per created short link</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Clicks Volume</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total: {{ number_format($stats['total_clicks']) }}</span>
            </div>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['clicks_in_window']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">recorded in this period</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Links Created</span>
                <span class="text-xs font-medium text-neutral-500">Active: {{ number_format($stats['active_links']) }}</span>
            </div>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['links_in_window']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">total lifetime: {{ number_format($stats['total_links']) }}</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Error Encounters</span>
                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800 dark:bg-red-900/40 dark:text-red-300">Errors</span>
            </div>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['errors_in_window']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">exceptions captured in window</p>
        </div>
    </div>

    {{-- Daily Clicks Timeline Bar Chart --}}
    <div class="mb-8">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['clicks_timeline'],
            'title' => 'Daily Link Clicks Traffic',
            'color' => 'amber',
        ])
    </div>

    {{-- Daily Links Timeline Bar Chart --}}
    <div class="mb-8">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['links_timeline'],
            'title' => 'Daily Link Creation Volume',
            'color' => 'blue',
        ])
    </div>

    {{-- Quick Breakdown Sections Grid --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Domains Activity Card --}}
        <x-ui.card title="Domains Performance &amp; Ratio">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Links</th>
                        <th>Clicks</th>
                        <th>Ratio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats['domain_stats'] as $d)
                        @php
                        $dRatio = $d->links_count > 0 ? round($d->clicks_count / $d->links_count, 1) : 0;
                        @endphp
                        <tr>
                            <td class="font-semibold font-mono text-xs">{{ $d->hostname }}</td>
                            <td>{{ number_format($d->links_count) }}</td>
                            <td class="font-bold">{{ number_format($d->clicks_count) }}</td>
                            <td>
                                <span class="rounded bg-neutral-100 px-2 py-0.5 font-mono text-xs font-semibold dark:bg-neutral-800">
                                    {{ $dRatio }}x
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-xs text-neutral-500 py-4">No domain data yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            <div class="mt-3 border-t border-neutral-100 pt-3 dark:border-neutral-800 text-right">
                <a href="{{ route('admin.stats.show', ['section' => 'ratio']) }}" class="text-xs font-semibold text-amber-600 hover:underline dark:text-amber-400">View detailed ratio analysis &rarr;</a>
            </div>
        </x-ui.card>

        {{-- Deep Dive Jump Links --}}
        <x-ui.card title="Explore Dedicated Telemetry">
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('admin.stats.show', ['section' => 'errors'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Errors Telemetry</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Exceptions per day, HTTP code breakdown, failing routes.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'activities'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Audit Activities</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Action audit logs, actors, security and moderation events.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'links'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Links Telemetry</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Creation frequency, tags, tracking adoption, active status.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'clicks'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Link Clicks Telemetry</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Traffic origins, device types, query parameters &amp; user tags.</span>
                </a>
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
