<x-layouts.public-dashboard title="Dashboard — my.ternis.link">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}.</h2>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Here's how your public links are doing.</p>
        </div>
        <div class="flex gap-2">
            <span class="pd-domain-chip">href.nz</span>
            <span class="pd-domain-chip">meinlink.at</span>
            <span class="pd-domain-chip">href.yt</span>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-4 xl:grid-cols-4" data-tour="stats">
        <div class="pd-stat pd-stat-accent">
            <div class="pd-stat-value">{{ number_format($stats['total_links']) }}</div>
            <div class="pd-stat-label">Total links</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['total_clicks']) }}</div>
            <div class="pd-stat-label">Total clicks</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['links_this_month']) }}</div>
            <div class="pd-stat-label">New this month</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['clicks_today']) }}</div>
            <div class="pd-stat-label">Clicks today</div>
        </div>
    </div>

    <div class="pd-card rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900" data-tour="table">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-display text-lg font-bold tracking-tight">Recent links</h3>
            <a href="{{ route('public-dashboard.links') }}" class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">View all →</a>
        </div>
        @if ($stats['total_links'] === 0)
            <div class="rounded-xl bg-indigo-600 p-8 text-center text-white">
                <h4 class="font-display text-xl font-bold">Shorten your first link</h4>
                <p class="mx-auto mt-2 max-w-md text-sm text-white/80">Paste any long URL and get an 8-character link back — or sign the details with a custom slug, tags, and expiry.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('public-dashboard.new') }}" class="rounded-full bg-white px-5 py-2 text-sm font-bold text-indigo-700 transition hover:bg-indigo-50">Create a link</a>
                    <a href="{{ route('public-dashboard.links.import') }}" class="rounded-full border border-white/40 px-5 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Import CSV</a>
                </div>
            </div>
        @else
            <livewire:dashboard.link-table scope="public" theme="public" />
        @endif
    </div>

    @if (($byDomain ?? collect())->isNotEmpty())
        <div class="pd-card mt-6 rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
            <h3 class="mb-4 font-display text-lg font-bold tracking-tight">Links by domain</h3>
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($byDomain as $row)
                    <li class="flex items-center justify-between gap-3 rounded-xl bg-neutral-50 px-4 py-3 dark:bg-white/5">
                        <span class="pd-domain-chip">{{ $row->hostname }}</span>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400"><strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->link_count) }}</strong> links · <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->click_sum) }}</strong> clicks</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (($expiringSoon ?? collect())->isNotEmpty())
        <div class="pd-card mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-400/20 dark:bg-amber-400/10">
            <h3 class="mb-1 font-display text-lg font-bold tracking-tight">Expiring soon</h3>
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">These links switch off within 7 days. Extend or deactivate them early.</p>
            <ul class="space-y-2">
                @foreach ($expiringSoon as $expiring)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white/70 px-4 py-2.5 text-sm dark:bg-black/20">
                        <a href="{{ route('public-dashboard.links.edit', $expiring->id) }}" class="font-bold text-indigo-700 hover:underline dark:text-indigo-300">{{ ($expiring->domain->hostname ?? 'href.nz').'/'.$expiring->slug }}</a>
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">expires {{ $expiring->expires_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (($topLinks ?? collect())->isNotEmpty())
        <div class="pd-card mt-6 rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
            <h3 class="mb-1 font-display text-lg font-bold tracking-tight">Top performers</h3>
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Your most-clicked links right now.</p>
            <ul class="space-y-2">
                @foreach ($topLinks as $top)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-neutral-50 px-4 py-2.5 text-sm dark:bg-white/5">
                        <a href="{{ route('public-dashboard.links.show', $top->id) }}" class="font-bold text-indigo-700 hover:underline dark:text-indigo-300">{{ ($top->domain->hostname ?? 'href.nz').'/'.$top->slug }}</a>
                        <span class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ number_format($top->click_count) }} clicks</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (($recentClicks ?? collect())->isNotEmpty())
        <div class="pd-card mt-6 rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
            <h3 class="mb-1 font-display text-lg font-bold tracking-tight">Latest clicks</h3>
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Fresh traffic across your public links.</p>
            <ul class="space-y-2">
                @foreach ($recentClicks as $click)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-neutral-50 px-4 py-2.5 text-sm dark:bg-white/5">
                        <a href="{{ route('public-dashboard.links.show', $click->link->id) }}" class="font-bold text-indigo-700 hover:underline dark:text-indigo-300">{{ ($click->link->domain->hostname ?? 'href.nz').'/'.$click->link->slug }}</a>
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $click->created_at->diffForHumans() }}@if ($click->referrer) · {{ Str::limit(parse_url($click->referrer, PHP_URL_HOST) ?? $click->referrer, 28) }}@endif</span>
                    </li>
                @endforeach
            </ul>
        </div>
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
