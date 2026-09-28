<x-layouts.app title="Network Stats — ternis.link">
    <x-slot:head>
        <link rel="alternate" type="text/markdown" title="Network Stats (Markdown)" href="{{ url('/pages/stats.md') }}">
    </x-slot:head>
    <x-ui.page-header
        title="Network Stats"
        subtitle="Public, aggregate-only analytics for the whole ternis.link network. No personal data — counts and daily totals, nothing else."
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

    <x-ui.card title="Links Created Per Day (last 30 days)" class="mb-6">
        <div class="relative h-56" role="img" aria-label="Links created per day for the last 30 days">
            <canvas data-chart="clicks" data-chart-unit="link" data-chart-labels='@json($creationLabels)' data-chart-values='@json($creationValues)'></canvas>
        </div>
    </x-ui.card>

    <x-ui.card title="Clicks Per Day (last 30 days)">
        <div class="relative h-56" role="img" aria-label="Clicks per day for the last 30 days">
            <canvas data-chart="clicks" data-chart-labels='@json($clickLabels)' data-chart-values='@json($clickValues)'></canvas>
        </div>
    </x-ui.card>

    <x-ui.card title="QR Codes Per Day (last 30 days)" class="mt-6">
        <div class="relative h-56" role="img" aria-label="QR codes generated per day for the last 30 days">
            <canvas data-chart="clicks" data-chart-labels='@json($qrLabels)' data-chart-values='@json($qrValues)'></canvas>
        </div>
    </x-ui.card>
</x-layouts.app>
