<x-layouts.app title="Network Trends — ternis.link stats">
    <x-slot:head>
        <link rel="canonical" href="{{ route('pages.stats.trends') }}">
        <link rel="alternate" type="text/markdown" href="{{ route('pages.stats.trends-md') }}">
    </x-slot:head>
    <x-ui.page-header title="Network Trends" subtitle="Compare complete weeks to understand how network activity changes." />
    <x-stats.nav />
    <div data-stats-page="trends">
        <p class="mb-2 text-sm text-neutral-600 dark:text-neutral-300">Last 7 complete days: <strong data-trend-period="current_period">{{ $trends['current_period'] }}</strong></p>
        <p class="mb-6 text-sm text-neutral-500">Compared with <span data-trend-period="previous_period">{{ $trends['previous_period'] }}</span>. Today is excluded because it is still in progress.</p>
        <div class="grid gap-6 lg:grid-cols-3">
            @foreach (['links' => 'Links created', 'clicks' => 'Redirect clicks', 'qr' => 'QR codes generated'] as $key => $label)
                @php($metric = $trends['metrics'][$key])
                <x-ui.card :title="$label" description="14 complete days; exact daily values below.">
                    <p class="text-3xl font-bold tabular-nums" data-trend="{{ $key }}.current">{{ number_format($metric['current']) }}</p>
                    <p class="mt-2 text-sm font-medium" data-trend="{{ $key }}.change">{{ $metric['change'] }}</p>
                    <dl class="my-4 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-neutral-500">Previous 7 days</dt><dd class="tabular-nums" data-trend="{{ $key }}.previous">{{ number_format($metric['previous']) }}</dd></div>
                        <div><dt class="text-neutral-500">Daily average</dt><dd class="tabular-nums" data-trend="{{ $key }}.daily_average">{{ number_format($metric['daily_average'], 1) }}</dd></div>
                    </dl>
                    <div class="relative h-48" role="img" aria-label="{{ $label }} over 14 complete days">
                        <canvas data-chart="clicks" data-chart-name="trend-{{ $key }}" data-chart-type="bar" data-chart-labels='@json($metric["labels"])' data-chart-values='@json($metric["values"])'></canvas>
                    </div>
                    <details class="mt-4 text-sm">
                        <summary class="cursor-pointer rounded py-2 font-medium focus-visible:outline-2">View daily values</summary>
                        <table class="w-full text-right tabular-nums"><caption class="sr-only">{{ $label }} per day</caption><thead><tr><th scope="col" class="text-left">Date</th><th scope="col">Count</th></tr></thead><tbody>
                        @foreach ($metric['labels'] as $i => $date)
                            <tr><th scope="row" class="py-1 text-left font-normal">{{ $date }}</th><td data-trend-day="{{ $key }}.{{ $date }}">{{ number_format($metric['values'][$i]) }}</td></tr>
                        @endforeach
                        </tbody></table>
                    </details>
                </x-ui.card>
            @endforeach
        </div>
        <p class="mt-6 text-sm text-neutral-500">Counts describe activity, not unique people or conversion rates. A percentage needs previous activity; zero to zero is shown as no change.</p>
    </div>
</x-layouts.app>
