<x-layouts.app title="Network Stats — ternis.link">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/pages/stats') }}">
        <meta name="description" content="Public, aggregate-only analytics for the whole ternis.link network. No personal data — counts, heatmaps, and daily totals, nothing else.">
        <meta property="og:title" content="Network Stats — ternis.link">
        <meta property="og:description" content="Public, aggregate-only analytics for the whole ternis.link network.">
        <meta property="og:url" content="{{ url('/pages/stats') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ternis.link">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Network Stats — ternis.link">
        <meta name="twitter:description" content="Public, aggregate-only analytics for the whole ternis.link network.">
        <link rel="alternate" type="text/markdown" title="Network Stats (Markdown)" href="{{ url('/pages/stats.md') }}">
    </x-slot:head>
    <x-ui.page-header
        title="Network Stats"
        subtitle="Public, aggregate-only analytics for the whole ternis.link network. No personal data — counts, heatmaps, and daily totals, nothing else."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('pages.stats.domains') }}" size="sm">Per Domain</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat :value="number_format($stats['total_links'])" label="Links Created (All Time)" />
        <x-ui.stat :value="number_format($stats['active_links'])" label="Active Links" />
        <x-ui.stat :value="number_format($stats['removed_links'])" label="Removed Links" />
        <x-ui.stat :value="number_format($stats['total_clicks'])" label="Total Clicks (All Time)" />
        <x-ui.stat :value="number_format($stats['links_today'])" label="Links Today" />
        <x-ui.stat :value="number_format($stats['clicks_today'])" label="Clicks Today" />
        <x-ui.stat :value="number_format($stats['direct_url_clicks'])" label="Direct-URL Redirects (All Time)" />
        <x-ui.stat :value="number_format($stats['direct_url_clicks_today'])" label="Direct-URL Redirects Today" />
        <x-ui.stat :value="number_format($stats['qr_codes'])" label="QR Codes Generated (All Time)" />
        <x-ui.stat :value="number_format($stats['qr_codes_today'])" label="QR Codes Today" />
        <x-ui.stat :value="number_format($stats['threat_evaluations'])" label="AI Security Threat Scans" />
        <x-ui.stat :value="number_format($stats['threats_quarantined'])" label="Threats Quarantined" />
    </div>

    <x-ui.card
        title="Links Created Per Day (last 30 days)"
        description="Daily short links created across the network, color-coded by domain (deleted links remain counted via tombstones)."
        class="mb-6"
        data-chart-card
    >
        <x-slot:actions>
            <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950" role="group" aria-label="Links chart display mode">
                <button type="button" data-chart-switch="bar" data-active="true" aria-pressed="true" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Bar Graph</button>
                <button type="button" data-chart-switch="line" data-active="false" aria-pressed="false" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Graph</button>
            </div>
        </x-slot:actions>
        <div class="relative h-64" role="img" aria-label="Links created per day by domain for the last 30 days">
            <canvas
                data-chart="multi"
                data-chart-type="bar"
                data-chart-stacked="true"
                data-chart-show-legend="true"
                data-chart-unit="link"
                data-chart-labels='@json($creationLabels)'
                data-chart-datasets='@json($creationDatasets)'
            ></canvas>
        </div>
    </x-ui.card>

    <x-ui.card
        title="Clicks Per Day (last 30 days)"
        description="Daily redirect traffic across the network, color-coded per short-link domain."
        class="mb-6"
        data-chart-card
    >
        <x-slot:actions>
            <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950" role="group" aria-label="Clicks chart display mode">
                <button type="button" data-chart-switch="bar" data-active="true" aria-pressed="true" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Bar Graph</button>
                <button type="button" data-chart-switch="line" data-active="false" aria-pressed="false" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Graph</button>
            </div>
        </x-slot:actions>
        <div class="relative h-64" role="img" aria-label="Clicks per day by domain for the last 30 days">
            <canvas
                data-chart="multi"
                data-chart-type="bar"
                data-chart-stacked="true"
                data-chart-show-legend="true"
                data-chart-unit="click"
                data-chart-labels='@json($clickLabels)'
                data-chart-datasets='@json($clickDatasets)'
            ></canvas>
        </div>
    </x-ui.card>

    <x-ui.card
        title="QR Codes Per Day (last 30 days)"
        description="On-demand vector SVG and PNG QR codes rendered across short-link QR endpoints, bio pages, qr.href.nz studio, and the /v1/qr API."
        data-chart-card
    >
        <x-slot:actions>
            <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950" role="group" aria-label="QR codes chart display mode">
                <button type="button" data-chart-switch="bar" data-active="true" aria-pressed="true" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Bar Graph</button>
                <button type="button" data-chart-switch="line" data-active="false" aria-pressed="false" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Graph</button>
            </div>
        </x-slot:actions>
        <div class="relative h-64" role="img" aria-label="On-demand QR codes rendered per day across short links, qr.href.nz, and /v1/qr for the last 30 days">
            <canvas
                data-chart="multi"
                data-chart-type="bar"
                data-chart-stacked="true"
                data-chart-show-legend="true"
                data-chart-unit="QR code"
                data-chart-verb="generated"
                data-chart-labels='@json($qrLabels)'
                data-chart-datasets='@json($qrDatasets)'
            ></canvas>
        </div>
    </x-ui.card>

    @php
        $weekdayNamesFull = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $daypartBlocks = [
            ['label' => 'Night', 'range' => '00–05', 'offset' => 0],
            ['label' => 'Morning', 'range' => '06–11', 'offset' => 6],
            ['label' => 'Afternoon', 'range' => '12–17', 'offset' => 12],
            ['label' => 'Evening', 'range' => '18–23', 'offset' => 18],
        ];

        $heatmapConfigs = [
            'clicks' => [
                'label' => 'Clicks',
                'unit' => 'click',
                'color' => 'emerald',
                'dot' => 'bg-emerald-500 dark:bg-emerald-400',
                'bar' => 'bg-emerald-500 dark:bg-emerald-400',
                'accent' => 'text-emerald-600 dark:text-emerald-400',
                'softBg' => 'bg-emerald-500/15 dark:bg-emerald-400/20',
                'calendarTitle' => '52-Week Clicks Calendar',
                'calendarSubtitle' => 'Day-by-day redirect traffic across every network domain over the last 364 days.',
                'hourlyTitle' => 'Weekday × UTC Hour Click Rhythm (Last 90 Days)',
                'hourlySubtitle' => 'Aggregate redirect intensity grouped by day of week and UTC hour.',
            ],
            'links' => [
                'label' => 'Links Created',
                'unit' => 'link',
                'color' => 'blue',
                'dot' => 'bg-blue-500 dark:bg-blue-400',
                'bar' => 'bg-blue-500 dark:bg-blue-400',
                'accent' => 'text-blue-600 dark:text-blue-400',
                'softBg' => 'bg-blue-500/15 dark:bg-blue-400/20',
                'calendarTitle' => '52-Week Links Created Calendar',
                'calendarSubtitle' => 'Daily short links created across platform and custom domains over the last 364 days.',
                'hourlyTitle' => 'Weekday × UTC Hour Link Creation Rhythm (Last 90 Days)',
                'hourlySubtitle' => 'Aggregate link creation cadence grouped by day of week and UTC hour.',
            ],
            'qr' => [
                'label' => 'QR Codes',
                'unit' => 'QR code',
                'color' => 'cyan',
                'dot' => 'bg-cyan-500 dark:bg-cyan-400',
                'bar' => 'bg-cyan-500 dark:bg-cyan-400',
                'accent' => 'text-cyan-600 dark:text-cyan-400',
                'softBg' => 'bg-cyan-500/15 dark:bg-cyan-400/20',
                'calendarTitle' => '52-Week QR Codes Calendar',
                'calendarSubtitle' => 'Daily on-demand SVG and PNG QR renders across the network over the last 364 days.',
                'hourlyTitle' => 'Weekday × UTC Hour QR Generation Rhythm (Last 90 Days)',
                'hourlySubtitle' => 'Aggregate QR code generation rhythm grouped by day of week and UTC hour.',
            ],
        ];

        $heatmapSummaries = [];
        foreach ($heatmapConfigs as $mKey => $cfg) {
            $calData = $heatmaps['calendar'][$mKey] ?? [];
            $hrGrid = $heatmaps['hourly'][$mKey] ?? [];

            $calSum = array_sum($calData);
            $rowSums = array_fill(0, 7, 0);
            $hourSums = array_fill(0, 24, 0);
            $dpCounts = [0, 0, 0, 0];
            $total90 = 0;

            for ($w = 0; $w < 7; $w++) {
                for ($h = 0; $h < 24; $h++) {
                    $v = (int) ($hrGrid[$w][$h] ?? 0);
                    $rowSums[$w] += $v;
                    $hourSums[$h] += $v;
                    $dpCounts[(int) intdiv($h, 6)] += $v;
                    $total90 += $v;
                }
            }

            $bestDayVal = max($rowSums);
            $bestDayIdx = $bestDayVal > 0 ? (int) array_search($bestDayVal, $rowSums, true) : null;

            $bestHrVal = max($hourSums);
            $bestHrIdx = $bestHrVal > 0 ? (int) array_search($bestHrVal, $hourSums, true) : null;

            $workweekSum = array_sum(array_slice($rowSums, 0, 5));
            $weekendSum = $rowSums[5] + $rowSums[6];
            $workweekPct = $total90 > 0 ? (int) round(($workweekSum / $total90) * 100) : 0;
            $weekendPct = $total90 > 0 ? max(0, 100 - $workweekPct) : 0;

            $maxDp = max(1, max($dpCounts));
            $dayparts = [];
            foreach ($daypartBlocks as $dpIdx => $dpMeta) {
                $c = $dpCounts[$dpIdx];
                $dayparts[] = [
                    'label' => $dpMeta['label'],
                    'range' => $dpMeta['range'],
                    'count' => $c,
                    'sharePct' => $total90 > 0 ? (int) round(($c / $total90) * 100) : 0,
                    'barPct' => $c > 0 ? max(8, (int) round(($c / $maxDp) * 100)) : 0,
                ];
            }

            $heatmapSummaries[$mKey] = [
                'calendarTotal' => $calSum,
                'total90' => $total90,
                'bestDayLabel' => $bestDayIdx !== null ? $weekdayNamesFull[$bestDayIdx] : '—',
                'bestDayCount' => $bestDayVal,
                'bestDayShare' => $total90 > 0 ? (int) round(($bestDayVal / $total90) * 100) : 0,
                'bestHourLabel' => $bestHrIdx !== null ? str_pad((string) $bestHrIdx, 2, '0', STR_PAD_LEFT).':00 UTC' : '—',
                'bestHourCount' => $bestHrVal,
                'bestHourShare' => $total90 > 0 ? (int) round(($bestHrVal / $total90) * 100) : 0,
                'workweekSum' => $workweekSum,
                'weekendSum' => $weekendSum,
                'workweekPct' => $workweekPct,
                'weekendPct' => $weekendPct,
                'dayparts' => $dayparts,
            ];
        }
    @endphp

    {{-- Network Activity Heatmaps (Bottom: 52-Week Calendar + 7x24 Rhythm & Diurnal Profile) --}}
    <section
        id="activity-heatmaps"
        data-stats-heatmaps
        x-data="{ metric: 'clicks' }"
        class="mt-10 border-t border-neutral-200 pt-8 dark:border-neutral-800"
    >
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-lg font-semibold tracking-tight text-neutral-900 dark:text-white">Network Activity Heatmaps</h2>
                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">Long-range 52-week contribution calendar and 90-day UTC diurnal rhythm across clicks, new short links, and QR renders.</p>
            </div>

            <div class="inline-flex flex-wrap items-center gap-0.5 rounded-xl border border-neutral-200 bg-neutral-50 p-1 text-xs dark:border-neutral-800 dark:bg-neutral-900" role="tablist" aria-label="Heatmap metric">
                @foreach ($heatmapConfigs as $mKey => $cfg)
                    <button
                        type="button"
                        role="tab"
                        @click="metric = '{{ $mKey }}'"
                        :aria-selected="(metric === '{{ $mKey }}').toString()"
                        :class="metric === '{{ $mKey }}'
                            ? 'bg-white font-semibold text-neutral-900 shadow-xs ring-1 ring-neutral-200/70 dark:bg-neutral-800 dark:text-white dark:ring-neutral-700'
                            : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg px-3 py-1.5 transition-all"
                    >
                        <span class="h-2 w-2 rounded-full {{ $cfg['dot'] }}"></span>
                        <span>{{ $cfg['label'] }}</span>
                        <span class="rounded-md bg-neutral-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-neutral-600 dark:bg-neutral-950/70 dark:text-neutral-300">
                            {{ number_format($heatmapSummaries[$mKey]['calendarTotal']) }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        @foreach ($heatmapConfigs as $mKey => $cfg)
            @php
                $summary = $heatmapSummaries[$mKey];
            @endphp
            <div
                x-show="metric === '{{ $mKey }}'"
                @if ($mKey !== 'clicks') x-cloak @endif
                class="space-y-5"
            >
                {{-- 52-Week Contribution Calendar Card --}}
                <div class="rounded-xl border border-neutral-200 bg-white p-5 sm:p-6 dark:border-neutral-800 dark:bg-neutral-900">
                    <x-ui.calendar-heatmap
                        :days="$heatmaps['calendar'][$mKey]"
                        :title="$cfg['calendarTitle']"
                        :subtitle="$cfg['calendarSubtitle']"
                        :color="$cfg['color']"
                        :unit="$cfg['unit']"
                        :wrapped="false"
                    />
                </div>

                {{-- 7x24 Weekday × UTC Hour Matrix + Diurnal Rhythm Sidebar --}}
                <div class="grid gap-5 xl:grid-cols-12">
                    <div class="rounded-xl border border-neutral-200 bg-white p-5 sm:p-6 xl:col-span-8 dark:border-neutral-800 dark:bg-neutral-900">
                        <x-ui.heatmap
                            :heatmap="$heatmaps['hourly'][$mKey]"
                            :title="$cfg['hourlyTitle']"
                            :subtitle="$cfg['hourlySubtitle']"
                            :color="$cfg['color']"
                            :unit="$cfg['unit']"
                            :wrapped="false"
                        />
                    </div>

                    <div class="flex flex-col justify-between rounded-xl border border-neutral-200 bg-white p-5 sm:p-6 xl:col-span-4 dark:border-neutral-800 dark:bg-neutral-900">
                        <div>
                            <div class="mb-4 flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-display text-base font-semibold tracking-tight text-neutral-900 dark:text-white">90-Day Rhythm Profile</h3>
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">UTC time-of-day blocks and weekday concentration.</p>
                                </div>
                                <span class="rounded-md bg-neutral-100 px-2 py-0.5 font-mono text-[10px] font-semibold text-neutral-500 uppercase dark:bg-neutral-800 dark:text-neutral-400">UTC</span>
                            </div>

                            {{-- Busiest Day & Busiest Hour Mini KPIs --}}
                            <div class="mb-5 grid grid-cols-2 gap-2.5">
                                <div class="rounded-lg border border-neutral-200/80 bg-neutral-50/70 p-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                                    <div class="text-[10px] font-semibold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">Busiest Day</div>
                                    <div class="mt-1 font-display text-sm font-bold text-neutral-900 dark:text-white">{{ $summary['bestDayLabel'] }}</div>
                                    <div class="mt-0.5 font-mono text-[11px] {{ $cfg['accent'] }}">
                                        @if ($summary['bestDayCount'] > 0)
                                            {{ number_format($summary['bestDayCount']) }} ({{ $summary['bestDayShare'] }}%)
                                        @else
                                            No activity yet
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-lg border border-neutral-200/80 bg-neutral-50/70 p-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                                    <div class="text-[10px] font-semibold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">Peak UTC Hour</div>
                                    <div class="mt-1 font-display text-sm font-bold text-neutral-900 dark:text-white">{{ $summary['bestHourLabel'] }}</div>
                                    <div class="mt-0.5 font-mono text-[11px] {{ $cfg['accent'] }}">
                                        @if ($summary['bestHourCount'] > 0)
                                            {{ number_format($summary['bestHourCount']) }} ({{ $summary['bestHourShare'] }}%)
                                        @else
                                            No activity yet
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- 4 UTC Daypart Bars --}}
                            <div class="space-y-3">
                                @foreach ($summary['dayparts'] as $dp)
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-xs">
                                            <span class="font-medium text-neutral-700 dark:text-neutral-300">
                                                {{ $dp['label'] }}
                                                <span class="ml-1 font-mono text-[10px] text-neutral-400 dark:text-neutral-500">{{ $dp['range'] }}</span>
                                            </span>
                                            <span class="font-mono text-[11px] text-neutral-600 dark:text-neutral-300">
                                                <strong class="font-semibold text-neutral-900 dark:text-white">{{ number_format($dp['count']) }}</strong>
                                                <span class="text-neutral-400 dark:text-neutral-500">({{ $dp['sharePct'] }}%)</span>
                                            </span>
                                        </div>
                                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                            <div
                                                class="h-full rounded-full transition-all duration-300 {{ $cfg['bar'] }}"
                                                style="width: {{ $dp['barPct'] }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Workweek vs Weekend Split Footer --}}
                        <div class="mt-5 border-t border-neutral-100 pt-4 dark:border-neutral-800">
                            <div class="mb-1.5 flex items-center justify-between text-[11px]">
                                <span class="font-medium text-neutral-600 dark:text-neutral-300">
                                    Mon–Fri <strong class="font-mono text-neutral-900 dark:text-white">{{ $summary['workweekPct'] }}%</strong>
                                </span>
                                <span class="font-medium text-neutral-500 dark:text-neutral-400">
                                    Sat–Sun <strong class="font-mono text-neutral-900 dark:text-white">{{ $summary['weekendPct'] }}%</strong>
                                </span>
                            </div>
                            <div class="flex h-2 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                @if ($summary['total90'] > 0)
                                    <div class="h-full {{ $cfg['bar'] }}" style="width: {{ $summary['workweekPct'] }}%"></div>
                                    <div class="h-full {{ $cfg['softBg'] }}" style="width: {{ $summary['weekendPct'] }}%"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
</x-layouts.app>
