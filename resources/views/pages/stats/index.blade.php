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
    </div>

    {{-- Network Activity Heatmaps (52-Week Calendar + 7x24 Weekday/Hour) --}}
    <section
        x-data="{ metric: 'clicks' }"
        class="mb-8 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
    >
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-neutral-100 pb-4 dark:border-neutral-800">
            <div>
                <h2 class="font-display text-lg font-semibold tracking-tight text-neutral-900 dark:text-white">Network Activity Heatmap</h2>
                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">Aggregate daily volume over the past 52 weeks and hourly rhythm across the last 90 days (UTC).</p>
            </div>

            <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-xs dark:border-neutral-800 dark:bg-neutral-950" role="tablist" aria-label="Heatmap metric">
                @foreach (['clicks' => 'Clicks', 'links' => 'Links Created', 'qr' => 'QR Codes'] as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        @click="metric = '{{ $key }}'"
                        :aria-selected="(metric === '{{ $key }}').toString()"
                        :class="metric === '{{ $key }}'
                            ? 'bg-white font-semibold text-neutral-900 shadow-xs dark:bg-neutral-800 dark:text-white'
                            : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'"
                        class="cursor-pointer rounded-md px-3 py-1.5 transition-all"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Clicks Heatmaps (Default visible) --}}
        <div x-show="metric === 'clicks'" class="space-y-8">
            <x-ui.calendar-heatmap
                :days="$heatmaps['calendar']['clicks']"
                title="Daily Clicks Calendar (Last 52 Weeks)"
                subtitle="Each square represents one calendar day of network-wide redirects."
                color="neutral"
                unit="click"
                :wrapped="false"
            />
            <div class="border-t border-neutral-100 pt-6 dark:border-neutral-800">
                <x-ui.heatmap
                    :heatmap="$heatmaps['hourly']['clicks']"
                    title="Weekday × Hour Click Rhythm (Last 90 Days)"
                    subtitle="Aggregate clicks grouped by day of week and UTC hour."
                    color="neutral"
                    unit="click"
                    :wrapped="false"
                />
            </div>
        </div>

        {{-- Links Created Heatmaps --}}
        <div x-show="metric === 'links'" x-cloak class="space-y-8">
            <x-ui.calendar-heatmap
                :days="$heatmaps['calendar']['links']"
                title="Daily Links Created Calendar (Last 52 Weeks)"
                subtitle="Each square represents one calendar day of shortened links across all domains."
                color="neutral"
                unit="link"
                :wrapped="false"
            />
            <div class="border-t border-neutral-100 pt-6 dark:border-neutral-800">
                <x-ui.heatmap
                    :heatmap="$heatmaps['hourly']['links']"
                    title="Weekday × Hour Link Creation Rhythm (Last 90 Days)"
                    subtitle="Aggregate link creations grouped by day of week and UTC hour."
                    color="neutral"
                    unit="link"
                    :wrapped="false"
                />
            </div>
        </div>

        {{-- QR Codes Heatmaps --}}
        <div x-show="metric === 'qr'" x-cloak class="space-y-8">
            <x-ui.calendar-heatmap
                :days="$heatmaps['calendar']['qr']"
                title="Daily QR Codes Calendar (Last 52 Weeks)"
                subtitle="Each square represents one calendar day of QR codes generated."
                color="neutral"
                unit="QR code"
                :wrapped="false"
            />
            <div class="border-t border-neutral-100 pt-6 dark:border-neutral-800">
                <x-ui.heatmap
                    :heatmap="$heatmaps['hourly']['qr']"
                    title="Weekday × Hour QR Generation Rhythm (Last 90 Days)"
                    subtitle="Aggregate QR code generations grouped by day of week and UTC hour."
                    color="neutral"
                    unit="QR code"
                    :wrapped="false"
                />
            </div>
        </div>
    </section>

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
        class="mt-6"
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
</x-layouts.app>
