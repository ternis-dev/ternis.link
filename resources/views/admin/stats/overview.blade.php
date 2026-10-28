<x-layouts.admin title="System Stats & Overview — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Stats &amp; System Analytics',
        'subtitle' => 'High-level telemetry, link traffic, conversion metrics, heatmaps, and system activity.',
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

    {{-- Daily Clicks & Daily Links Timeline Charts --}}
    <div class="mb-8 grid gap-6 xl:grid-cols-2">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['clicks_timeline'],
            'title' => 'Daily Link Clicks Traffic',
            'color' => 'amber',
            'unit' => 'click',
        ])

        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['links_timeline'],
            'title' => 'Daily Link Creation Volume',
            'color' => 'blue',
            'unit' => 'link',
        ])
    </div>

    {{-- Multi-Signal Platform Heatmaps (7x24 + Daily Calendar) --}}
    <section
        x-data="{ signal: 'clicks' }"
        class="mb-8 rounded-2xl border border-neutral-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
    >
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-neutral-100 pb-4 dark:border-neutral-800">
            <div>
                <h2 class="font-display text-base font-semibold tracking-tight text-neutral-900 dark:text-white">Platform Activity Heatmaps</h2>
                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">Compare 7&times;24 hourly patterns and daily intensity across clicks, link creations, audit events, and errors.</p>
            </div>
            <div class="inline-flex flex-wrap items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-xs dark:border-neutral-800 dark:bg-neutral-950" role="tablist" aria-label="Platform signal heatmap">
                @foreach (['clicks' => 'Clicks', 'links' => 'Links Created', 'activities' => 'Audit Log', 'errors' => 'Errors'] as $key => $lbl)
                    <button
                        type="button"
                        role="tab"
                        @click="signal = '{{ $key }}'"
                        :aria-selected="(signal === '{{ $key }}').toString()"
                        :class="signal === '{{ $key }}'
                            ? 'bg-white font-semibold text-neutral-900 shadow-xs dark:bg-neutral-800 dark:text-white'
                            : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'"
                        class="cursor-pointer rounded-md px-3 py-1.5 transition-all"
                    >
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="signal === 'clicks'" class="space-y-6">
            <x-ui.heatmap :heatmap="$stats['heatmaps']['clicks']" title="Clicks — 7×24 Weekday & Hour" color="amber" unit="click" :wrapped="false" />
            <div class="border-t border-neutral-100 pt-5 dark:border-neutral-800">
                <x-ui.calendar-heatmap :days="$stats['clicks_timeline']" title="Clicks — Daily Calendar" color="amber" unit="click" :wrapped="false" />
            </div>
        </div>

        <div x-show="signal === 'links'" x-cloak class="space-y-6">
            <x-ui.heatmap :heatmap="$stats['heatmaps']['links']" title="Links Created — 7×24 Weekday & Hour" color="blue" unit="link" :wrapped="false" />
            <div class="border-t border-neutral-100 pt-5 dark:border-neutral-800">
                <x-ui.calendar-heatmap :days="$stats['links_timeline']" title="Links Created — Daily Calendar" color="blue" unit="link" :wrapped="false" />
            </div>
        </div>

        <div x-show="signal === 'activities'" x-cloak class="space-y-6">
            <x-ui.heatmap :heatmap="$stats['heatmaps']['activities']" title="Audit Activities — 7×24 Weekday & Hour" color="purple" unit="event" :wrapped="false" />
            <div class="border-t border-neutral-100 pt-5 dark:border-neutral-800">
                <x-ui.calendar-heatmap :days="$stats['activities_timeline']" title="Audit Activities — Daily Calendar" color="purple" unit="event" :wrapped="false" />
            </div>
        </div>

        <div x-show="signal === 'errors'" x-cloak class="space-y-6">
            <x-ui.heatmap :heatmap="$stats['heatmaps']['errors']" title="Error Encounters — 7×24 Weekday & Hour" color="red" unit="error" :wrapped="false" />
            <div class="border-t border-neutral-100 pt-5 dark:border-neutral-800">
                <x-ui.calendar-heatmap :days="$stats['errors_timeline']" title="Error Encounters — Daily Calendar" color="red" unit="error" :wrapped="false" />
            </div>
        </div>
    </section>

    {{-- Quick Breakdown Sections Grid --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Domains Activity Card with Chart --}}
        <x-ui.card title="Domains Performance & Ratio">
            @if ($stats['domain_stats']->sum('clicks_count') > 0)
                <div class="relative mb-5 h-48 w-full" role="img" aria-label="Clicks per domain">
                    <canvas
                        data-chart="horizontal-bar"
                        data-chart-color="amber"
                        data-chart-unit="click"
                        data-chart-labels='@json($stats["domain_stats"]->pluck("hostname")->values())'
                        data-chart-values='@json($stats["domain_stats"]->pluck("clicks_count")->values())'
                    ></canvas>
                </div>
            @endif
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
                <a href="{{ route('admin.stats.show', ['section' => 'insights'] + $params) }}" class="flex flex-col rounded-xl border border-amber-200 bg-amber-50/50 p-4 transition hover:border-amber-400 dark:border-amber-900/50 dark:bg-amber-950/20 dark:hover:border-amber-700 sm:col-span-2">
                    <span class="text-xs font-semibold text-amber-900 dark:text-amber-200 flex items-center justify-between">
                        <span>Traffic &amp; Behavioral Insights (Heatmap &amp; Curves)</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-600 dark:text-neutral-400">7&times;24 weekday heatmaps, daily contribution calendar, peak traffic hours, 24h curve, top links, and expiring calendars.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'errors'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Errors Telemetry</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">4xx/5xx timelines, error burst heatmap, HTTP code breakdown, failing routes.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'activities'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Audit Activities</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Action audit logs, activity heatmap, actors, security and moderation events.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'links'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Links Telemetry</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Creation frequency &amp; heatmap, domain chart, tracking adoption, active status.</span>
                </a>

                <a href="{{ route('admin.stats.show', ['section' => 'clicks'] + $params) }}" class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:border-neutral-700">
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white flex items-center justify-between">
                        <span>Link Clicks Telemetry</span>
                        <span>&rarr;</span>
                    </span>
                    <span class="mt-1 text-[11px] text-neutral-500">Filtered click heatmap, device doughnut, traffic origins, query params &amp; tags.</span>
                </a>
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
