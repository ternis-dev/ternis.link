<x-layouts.admin title="Link/Clicks Ratio — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Link / Clicks Ratio &amp; Engagement Telemetry',
        'subtitle' => 'System-wide conversion efficiency, clicks-per-link distribution tiers, and domain-level performance.',
        'section' => 'ratio',
        'params' => $params,
    ])

    {{-- Filter Bar Form --}}
    <form method="GET" action="{{ route('admin.stats.show', ['section' => 'ratio']) }}" class="mb-6 rounded-2xl border border-neutral-200/90 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <input type="hidden" name="days" value="{{ $stats['days'] }}">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Domain</label>
                <select name="domain_id" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">All Domains</option>
                    @foreach ($stats['domains'] as $d)
                        <option value="{{ $d->id }}" @selected(($params['domain_id'] ?? '') == $d->id)>{{ $d->hostname }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 cursor-pointer">
                    Filter
                </button>
                @if (!empty($params['domain_id']))
                    <a href="{{ route('admin.stats.show', ['section' => 'ratio', 'days' => $stats['days']]) }}" class="rounded-lg border border-neutral-300 px-2.5 py-2 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Average Clicks / Link</span>
            <p class="mt-2 text-4xl font-extrabold tracking-tight text-amber-500">{{ $stats['ratio'] }}x</p>
            <p class="mt-1 text-xs text-neutral-500">{{ number_format($stats['total_clicks']) }} clicks / {{ number_format($stats['total_links']) }} links</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Active Engagement</span>
            <p class="mt-2 text-4xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $stats['active_ratio_percent'] }}%</p>
            <p class="mt-1 text-xs text-neutral-500">{{ number_format($stats['active_clicked_links']) }} links received &ge; 1 click</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Dormant Links</span>
            <p class="mt-2 text-4xl font-extrabold tracking-tight text-neutral-700 dark:text-neutral-300">{{ number_format($stats['dormant_links']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">links with 0 clicks</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Powerhouse Links</span>
            @php
            $viral = $stats['tiers']['1,000+ clicks'] ?? 0;
            @endphp
            <p class="mt-2 text-4xl font-extrabold tracking-tight text-purple-600 dark:text-purple-400">{{ number_format($viral) }}</p>
            <p class="mt-1 text-xs text-neutral-500">links with &gt; 1,000 clicks</p>
        </div>
    </div>

    {{-- Click Distribution Tiers --}}
    <x-ui.card title="Clicks-per-Link Volume Distribution Tiers" class="mb-8">
        <p class="mb-4 text-xs text-neutral-500">Distribution of short links by the total volume of clicks they have accumulated.</p>
        <div class="space-y-4">
            @foreach ($stats['tiers'] as $tierName => $tierCount)
                @php
                $pct = $stats['total_links'] > 0 ? round(($tierCount / $stats['total_links']) * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-neutral-800 dark:text-neutral-200">{{ $tierName }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($tierCount) }} links</span>
                            <span class="w-12 text-right text-neutral-400">({{ $pct }}%)</span>
                        </div>
                    </div>
                    <div class="h-2 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $pct }}%" class="h-full rounded-full bg-amber-500"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Domain Performance Comparison & Top Links --}}
    <div class="grid gap-6 lg:grid-cols-2 mb-8">
        {{-- Domain Ratio Leaderboard --}}
        <x-ui.card title="Domain-Level Click Conversion Ratio">
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
                    @forelse ($stats['domain_ratios'] as $dr)
                        <tr>
                            <td class="font-mono text-xs font-semibold">{{ $dr['hostname'] }}</td>
                            <td>{{ number_format($dr['links_count']) }}</td>
                            <td class="font-bold">{{ number_format($dr['clicks_count']) }}</td>
                            <td>
                                <span class="rounded bg-amber-100 px-2 py-0.5 font-mono text-xs font-bold text-amber-900 dark:bg-amber-900/40 dark:text-amber-200">
                                    {{ $dr['ratio'] }}x
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-xs text-neutral-500 py-4">No domain data recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        {{-- Top Ratio Links --}}
        <x-ui.card title="Highest Performing Links">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Domain</th>
                        <th>Clicks</th>
                        <th>Destination</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats['top_links'] as $tl)
                        <tr>
                            <td class="font-mono text-xs font-semibold">{{ $tl->slug }}</td>
                            <td class="font-mono text-xs text-neutral-500">{{ $tl->domain?->hostname ?? '—' }}</td>
                            <td class="font-bold text-neutral-900 dark:text-white">{{ number_format($tl->click_count) }}</td>
                            <td class="text-xs text-neutral-500 max-w-[160px] truncate" title="{{ $tl->destination_url }}">{{ $tl->destination_url }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-xs text-neutral-500 py-4">No links recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.admin>
