<x-layouts.public-dashboard title="Dashboard — my.ternis.link">
    <x-pd.head
        title="Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}."
        subtitle="Your href.nz, meinlink.at and href.yt links — live numbers below."
    >
        <x-slot:actions>
            <x-pd.button href="{{ route('public-dashboard.links.create') }}" variant="primary">+ New link</x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4" data-tour="stats">
        <x-pd.stat :value="number_format($stats['total_links'])" label="Total links" accent="true" />
        <x-pd.stat :value="number_format($stats['total_clicks'])" label="Total clicks" />
        <x-pd.stat :value="number_format($stats['links_this_month'])" label="New this month" />
        <x-pd.stat :value="number_format($stats['clicks_today'])" label="Clicks today" />
    </div>

    @if ($stats['total_links'] === 0)
        <x-pd.card title="Shorten your first link" class="mb-6">
            Paste any long URL and get an 8-character link back — or import a whole CSV at once.
            <x-slot:actions>
                <x-pd.button href="{{ route('public-dashboard.new') }}" variant="primary" size="sm">Create a link</x-pd.button>
                <x-pd.button href="{{ route('public-dashboard.links.import') }}" variant="secondary" size="sm">Import CSV</x-pd.button>
            </x-slot:actions>
        </x-pd.card>
    @else
        <x-pd.card title="Recent links" class="mb-6" data-tour="table">
            <x-slot:actions>
                <x-pd.button href="{{ route('public-dashboard.links') }}" variant="secondary" size="sm">View all →</x-pd.button>
            </x-slot:actions>
            <livewire:dashboard.link-table scope="public" theme="public" />
        </x-pd.card>
    @endif

    @if (($byDomain ?? collect())->isNotEmpty())
        <x-pd.card title="Links by domain" class="mb-6">
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($byDomain as $row)
                    <li class="flex items-center justify-between gap-3 rounded-lg bg-neutral-50 px-4 py-3 dark:bg-white/5">
                        <x-pd.chip>{{ $row->hostname }}</x-pd.chip>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400"><strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->link_count) }}</strong> links · <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->click_sum) }}</strong> clicks</span>
                    </li>
                @endforeach
            </ul>
        </x-pd.card>
    @endif

    @if (($expiringSoon ?? collect())->isNotEmpty())
        <x-pd.card title="Expiring soon" class="mb-6">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">These links switch off within 7 days. Extend or deactivate them early.</p>
            <ul class="space-y-2">
                @foreach ($expiringSoon as $expiring)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-amber-50 px-4 py-2.5 text-sm dark:bg-amber-400/10">
                        <a href="{{ route('public-dashboard.links.edit', $expiring->id) }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-300">{{ ($expiring->domain->hostname ?? 'href.nz').'/'.$expiring->slug }}</a>
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">expires {{ $expiring->expires_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-pd.card>
    @endif

    @if (($topLinks ?? collect())->isNotEmpty())
        <x-pd.card title="Top performers" class="mb-6">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Your most-clicked links right now.</p>
            <ul class="space-y-2">
                @foreach ($topLinks as $top)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-neutral-50 px-4 py-2.5 text-sm dark:bg-white/5">
                        <a href="{{ route('public-dashboard.links.show', $top->id) }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-300">{{ ($top->domain->hostname ?? 'href.nz').'/'.$top->slug }}</a>
                        <span class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ number_format($top->click_count) }} clicks</span>
                    </li>
                @endforeach
            </ul>
        </x-pd.card>
    @endif

    @if (($recentClicks ?? collect())->isNotEmpty())
        <x-pd.card title="Latest clicks">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Fresh traffic across your public links.</p>
            <ul class="space-y-2">
                @foreach ($recentClicks as $click)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-neutral-50 px-4 py-2.5 text-sm dark:bg-white/5">
                        <a href="{{ route('public-dashboard.links.show', $click->link->id) }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-300">{{ ($click->link->domain->hostname ?? 'href.nz').'/'.$click->link->slug }}</a>
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $click->created_at->diffForHumans() }}@if ($click->referrer) · {{ Str::limit(parse_url($click->referrer, PHP_URL_HOST) ?? $click->referrer, 28) }}@endif</span>
                    </li>
                @endforeach
            </ul>
        </x-pd.card>
    @endif

    <script type="application/json" id="tl-tour-steps">
        {!! json_encode([
            'id' => 'public',
            'autostart' => ($stats['total_links'] ?? 0) === 0,
            'theme' => 'public',
            'steps' => [
                ['target' => null, 'title' => 'Welcome to my.ternis.link', 'body' => 'Your href.nz, meinlink.at and href.yt links live here. Short tour — skip anytime.'],
                ['target' => 'create', 'title' => 'New short link', 'body' => 'This button (or the C key) shortens a link without leaving the page.'],
                ['target' => 'stats', 'title' => 'Your numbers', 'body' => 'Links, clicks, and fresh traffic at a glance.'],
                ['target' => 'table', 'title' => 'Every link, filterable', 'body' => 'Search, filter by tag or domain, export CSV or grab a QR ZIP.'],
                ['target' => 'nav', 'title' => 'Need more?', 'body' => 'API keys, domains, and settings live over on dash.ternis.link. Enjoy!'],
            ],
        ]) !!}
    </script>
</x-layouts.public-dashboard>
