<x-layouts.public-dashboard title="Dashboard — {{ \App\Support\PublicDashboard::label() }}">
    {{-- Modern Clean Tech Hero Header --}}
    <section class="nd-hero mb-8 overflow-hidden rounded-2xl p-6 sm:p-8 lg:p-10" data-tour="hero">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="min-w-0 max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold tracking-wider text-emerald-300 uppercase ring-1 ring-emerald-400/25 backdrop-blur-xs">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ \App\Support\PublicDashboard::label() }} Console
                </div>
                <h1 class="mt-3 font-display text-2xl font-extrabold tracking-tight text-white sm:text-3xl lg:text-4xl">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}.
                </h1>
                <p class="mt-2 text-sm sm:text-base text-emerald-100/80 leading-relaxed">
                    Privacy-first short links for href.nz, meinlink.at and href.yt with live click telemetry.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.import') }}" variant="secondary" size="sm">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Import CSV
                </x-pd.button>
                <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.create') }}" variant="primary" size="sm">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    New link
                </x-pd.button>
            </div>
        </div>
    </section>

    {{-- Stat tiles --}}
    <div class="mb-8 grid grid-cols-2 gap-4 xl:grid-cols-4" data-tour="stats">
        <x-pd.stat :value="number_format($stats['total_links'])" label="Total links" accent="true" />
        <x-pd.stat :value="number_format($stats['total_clicks'])" label="Total clicks" />
        <x-pd.stat :value="number_format($stats['links_this_month'])" label="New this month" />
        <x-pd.stat :value="number_format($stats['clicks_today'])" label="Clicks today" />
    </div>

    @if ($stats['total_links'] === 0)
        <x-pd.card title="Shorten your first link" class="mb-8">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Paste any long URL and get a high-performance public short link back — or bulk-import a full CSV list.
            </p>
            <x-slot:actions>
                <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.create') }}" variant="primary" size="sm">Create a link</x-pd.button>
                <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.import') }}" variant="secondary" size="sm">Import CSV</x-pd.button>
            </x-slot:actions>
        </x-pd.card>
    @else
        <x-pd.card title="Your links" class="mb-8" data-tour="table">
            <x-slot:actions>
                <x-pd.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">Manage all →</x-pd.button>
            </x-slot:actions>
            <livewire:dashboard.link-table scope="public" theme="public" />
        </x-pd.card>
    @endif

    {{-- Secondary panels --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @if (($byDomain ?? collect())->isNotEmpty())
            <x-pd.card title="Links by domain">
                <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($byDomain as $row)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/60 dark:bg-slate-900/60 dark:ring-slate-800/60">
                            <x-pd.chip>{{ $row->hostname }}</x-pd.chip>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                <strong class="font-bold text-slate-900 dark:text-white">{{ number_format($row->link_count) }}</strong> links · <strong class="font-bold text-slate-900 dark:text-white">{{ number_format($row->click_sum) }}</strong> clicks
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-pd.card>
        @endif

        @if (($expiringSoon ?? collect())->isNotEmpty())
            <x-pd.card title="Expiring soon">
                <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">These links will deactivate within 7 days. Extend or deactivate them early.</p>
                <ul class="space-y-2">
                    @foreach ($expiringSoon as $expiring)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-amber-50/70 p-3 text-sm ring-1 ring-amber-200/60 dark:bg-amber-950/20 dark:ring-amber-900/40">
                            <a href="{{ \App\Support\PublicDashboard::route('links.edit', $expiring->id) }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ ($expiring->domain->hostname ?? 'href.nz').'/'.$expiring->slug }}</a>
                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">expires {{ $expiring->expires_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-pd.card>
        @endif

        @if (($topLinks ?? collect())->isNotEmpty())
            <x-pd.card title="Top performers">
                <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Your highest traffic public links right now.</p>
                <ul class="space-y-2">
                    @foreach ($topLinks as $i => $top)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50/80 p-3 text-sm ring-1 ring-slate-200/60 dark:bg-slate-900/60 dark:ring-slate-800/60">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-emerald-500/10 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">#{{ $loop->iteration }}</span>
                                <a href="{{ \App\Support\PublicDashboard::route('links.show', $top->id) }}" class="truncate font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ ($top->domain->hostname ?? 'href.nz').'/'.$top->slug }}</a>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ number_format($top->click_count) }} clicks</span>
                        </li>
                    @endforeach
                </ul>
            </x-pd.card>
        @endif

        @if (($recentClicks ?? collect())->isNotEmpty())
            <x-pd.card title="Latest clicks">
                <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Real-time incoming traffic across your links.</p>
                <ul class="space-y-2">
                    @foreach ($recentClicks as $click)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50/80 p-3 text-sm ring-1 ring-slate-200/60 dark:bg-slate-900/60 dark:ring-slate-800/60">
                            <a href="{{ \App\Support\PublicDashboard::route('links.show', $click->link->id) }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ ($click->link->domain->hostname ?? 'href.nz').'/'.$click->link->slug }}</a>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $click->created_at->diffForHumans() }}@if ($click->referrer) · {{ Str::limit(parse_url($click->referrer, PHP_URL_HOST) ?? $click->referrer, 24) }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            </x-pd.card>
        @endif
    </div>

    <script type="application/json" id="tl-tour-steps">
        {!! json_encode([
            'id' => 'public',
            'autostart' => ($stats['total_links'] ?? 0) === 0,
            'theme' => 'public-new',
            'steps' => [
                ['target' => null, 'title' => 'Welcome to '.\App\Support\PublicDashboard::label(), 'body' => 'Your href.nz, meinlink.at and href.yt links live here. Quick overview — skip anytime.'],
                ['target' => 'create', 'title' => 'New short link', 'body' => 'Shorten any URL instantly from anywhere with the C keyboard shortcut.'],
                ['target' => 'stats', 'title' => 'Real-time telemetry', 'body' => 'Links, clicks, and incoming traffic counters at a glance.'],
                ['target' => 'table', 'title' => 'Every link, filterable', 'body' => 'Search, filter by tag or domain, export CSV or grab a QR ZIP.'],
                ['target' => 'nav', 'title' => 'Dedicated tools', 'body' => 'Manage API keys, domains, link groups, and performance insights.'],
            ],
        ]) !!}
    </script>
</x-layouts.public-dashboard>
