<x-layouts.app title="Links Per Domain — ternis.link stats">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/pages/stats/domains') }}">
        <meta name="description" content="Domain breakdown for the ternis.link network: aggregate link and click volume per domain.">
        <meta property="og:title" content="Links Per Domain — ternis.link stats">
        <meta property="og:description" content="Domain breakdown for the ternis.link network: aggregate link and click volume per domain.">
        <meta property="og:url" content="{{ url('/pages/stats/domains') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ternis.link">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Links Per Domain — ternis.link stats">
        <meta name="twitter:description" content="Domain breakdown for the ternis.link network: aggregate link and click volume per domain.">
        <link rel="alternate" type="text/markdown" title="Links Per Domain (Markdown)" href="{{ url('/pages/stats/domains.md') }}">
    </x-slot:head>
    <x-ui.page-header
        title="Links Per Domain"
        subtitle="How the network splits across domains. Aggregate counts only."
        :backHref="route('pages.stats.index')"
        backLabel="Back to Stats Overview"
    />

    <x-stats.nav />

    <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">Platform domains are listed for everyone; your own custom domains appear here once you're signed in. Other users' hostnames are never shown. System traffic is included in the totals on the overview.</p>

    @if ($domains->isNotEmpty())
        @php
            $domainLabels = $domains->pluck('hostname')->values()->all();
            $domainLinks = $domains->map(fn ($d) => (int) $d->links_count)->values()->all();
            $domainClicks = $domains->map(fn ($d) => (int) ($d->links_sum_click_count ?? 0))->values()->all();
            $colorsLight = $domains->map(fn ($d) => $domainColors[$d->hostname]['light'] ?? '#2563eb')->values()->all();
            $colorsDark = $domains->map(fn ($d) => $domainColors[$d->hostname]['dark'] ?? '#60a5fa')->values()->all();
        @endphp

        <x-ui.card
            title="Volume Per Domain"
            description="Each domain is shown in its own distinct color across Bar Graph and Graph modes."
            class="mb-6"
            data-chart-card
            x-data="{ metric: 'links' }"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950" role="tablist" aria-label="Domain metric">
                        <button
                            type="button"
                            role="tab"
                            @click="metric = 'links'; $nextTick(() => document.dispatchEvent(new CustomEvent('tl:charts-refresh', { detail: { root: $el.closest('[data-chart-card]') } })))"
                            :aria-selected="(metric === 'links').toString()"
                            :class="metric === 'links' ? 'bg-white font-bold text-neutral-900 shadow-xs dark:bg-neutral-800 dark:text-white' : 'text-neutral-500'"
                            class="cursor-pointer rounded-md px-2.5 py-1 transition-all"
                        >Links</button>
                        <button
                            type="button"
                            role="tab"
                            @click="metric = 'clicks'; $nextTick(() => document.dispatchEvent(new CustomEvent('tl:charts-refresh', { detail: { root: $el.closest('[data-chart-card]') } })))"
                            :aria-selected="(metric === 'clicks').toString()"
                            :class="metric === 'clicks' ? 'bg-white font-bold text-neutral-900 shadow-xs dark:bg-neutral-800 dark:text-white' : 'text-neutral-500'"
                            class="cursor-pointer rounded-md px-2.5 py-1 transition-all"
                        >Clicks</button>
                    </div>

                    <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950" role="group" aria-label="Domain chart display mode">
                        <button type="button" data-chart-switch="bar" data-active="true" aria-pressed="true" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Bar Graph</button>
                        <button type="button" data-chart-switch="line" data-active="false" aria-pressed="false" class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500">Graph</button>
                    </div>
                </div>
            </x-slot:actions>

            <div x-show="metric === 'links'" class="relative h-64" role="img" aria-label="Links per domain">
                <canvas
                    data-chart="clicks"
                    data-chart-type="bar"
                    data-chart-unit="link"
                    data-chart-labels='@json($domainLabels)'
                    data-chart-values='@json($domainLinks)'
                    data-chart-colors-light='@json($colorsLight)'
                    data-chart-colors-dark='@json($colorsDark)'
                ></canvas>
            </div>

            <div x-show="metric === 'clicks'" x-cloak class="relative h-64" role="img" aria-label="Clicks per domain">
                <canvas
                    data-chart="clicks"
                    data-chart-type="bar"
                    data-chart-unit="click"
                    data-chart-labels='@json($domainLabels)'
                    data-chart-values='@json($domainClicks)'
                    data-chart-colors-light='@json($colorsLight)'
                    data-chart-colors-dark='@json($colorsDark)'
                ></canvas>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card>
        <x-ui.table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Links</th>
                    <th>Clicks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $domain)
                    @php
                        $swatchColor = $domainColors[$domain->hostname]['light'] ?? '#2563eb';
                    @endphp
                    <tr>
                        <td>
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $swatchColor }}"></span>
                                <code>{{ $domain->hostname }}</code>
                            </span>
                        </td>
                        <td class="font-bold">{{ number_format($domain->links_count) }}</td>
                        <td class="font-bold">{{ number_format($domain->links_sum_click_count ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"><x-ui.empty-state>No domains yet.</x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</x-layouts.app>
