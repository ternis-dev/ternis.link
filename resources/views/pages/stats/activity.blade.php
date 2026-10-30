<x-layouts.app title="Network Activity — ternis.link stats">
    <x-slot:head><link rel="alternate" type="text/markdown" href="{{ route('pages.stats.activity-md') }}"></x-slot:head>
    <x-ui.page-header title="Network Activity" subtitle="A daily view of links, redirects, and QR generation over the last 30 days." />
    <x-stats.nav />
    <div class="mb-6 grid gap-4 md:grid-cols-3">
        @foreach (['Links created' => $links, 'Redirect clicks' => $clicks, 'QR codes generated' => $qr] as $label => $series)
            <x-ui.stat :value="number_format(array_sum($series['values']))" :label="$label" description="Last 30 days" />
        @endforeach
    </div>
    <x-ui.card title="Daily activity" description="Newest dates first. Deleted links and their historical clicks remain included.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead><tr class="border-b border-neutral-200 text-neutral-500 dark:border-neutral-800"><th scope="col" class="py-3 text-left">Day</th><th scope="col" class="text-right">Links</th><th scope="col" class="text-right">Clicks</th><th scope="col" class="text-right">QR codes</th></tr></thead>
                <tbody>@foreach (array_reverse(array_keys($links['labels'])) as $i)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800"><th scope="row" class="py-3 text-left font-medium">{{ $links['labels'][$i] }}</th><td class="text-right">{{ number_format($links['values'][$i]) }}</td><td class="text-right">{{ number_format($clicks['values'][$i]) }}</td><td class="text-right">{{ number_format($qr['values'][$i]) }}</td></tr>
                @endforeach</tbody>
            </table>
        </div>
    </x-ui.card>
</x-layouts.app>
