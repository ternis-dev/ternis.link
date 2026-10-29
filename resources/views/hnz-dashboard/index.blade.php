<x-layouts.hnz-dashboard title="Dashboard — {{ \App\Support\PublicDashboard::label() }}">
    {{-- Hero --}}
    <section class="mb-7 overflow-hidden rounded-3xl bg-gradient-to-br from-violet-700 via-violet-600 to-blue-600 px-6 py-8 shadow-lg shadow-violet-600/20 sm:px-9 sm:py-10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="min-w-0">
                <p class="text-[11px] font-bold tracking-[0.18em] text-violet-200 uppercase">
                    {{ \App\Support\PublicDashboard::label() }} · personal dashboard
                </p>
                <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ trim(auth()->user()->name) === '' ? 'Welcome back' : 'Welcome back, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ') }}
                </h1>
                <p class="mt-2 max-w-xl text-sm text-violet-100/80">
                    Everything you shortened on href.nz, meinlink.at and href.yt — in one place.
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <x-hnz.button
                    type="button"
                    x-data
                    @click="$dispatch('open-link-creator')"
                    variant="secondary"
                    class="border-white/25 bg-white/10 text-white backdrop-blur hover:border-white/40 hover:bg-white/20 hover:text-white"
                >+ New link</x-hnz.button>
                <x-hnz.button href="{{ \App\Support\PublicDashboard::route('insights') }}" variant="secondary" class="border-white/25 bg-white/10 text-white backdrop-blur hover:border-white/40 hover:bg-white/20 hover:text-white">
                    Insights
                </x-hnz.button>
            </div>
        </div>

        <dl class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                ['Total links', number_format($stats['total_links'])],
                ['Total clicks', number_format($stats['total_clicks'])],
                ['New this month', number_format($stats['links_this_month'])],
                ['Clicks today', number_format($stats['clicks_today'])],
            ] as $index => [$label, $value])
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <dt class="text-[10px] font-bold tracking-[0.12em] text-violet-100/70 uppercase">{{ $label }}</dt>
                    <dd class="mt-1 font-display text-2xl font-bold text-white tabular-nums">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Main column --}}
        <div class="xl:col-span-2">
            <x-hnz.card title="Your links" subtitle="Search, filter, reorder columns — everything stays per account.">
                <x-slot:actions>
                    <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">Manage all</x-hnz.button>
                </x-slot:actions>

                @if ($stats['total_links'] === 0)
                    <x-hnz.empty title="No short links yet">
                        Paste any long URL to get an 8-character link back — or import a whole CSV at once.
                    </x-hnz.empty>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.create') }}" variant="primary" size="sm">Create a link</x-hnz.button>
                        <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.import') }}" variant="secondary" size="sm">Import CSV</x-hnz.button>
                    </div>
                @else
                    <livewire:dashboard.link-table scope="public" theme="public" />
                @endif
            </x-hnz.card>
        </div>

        {{-- Right rail --}}
        <div class="space-y-6">
            @if (($expiringSoon ?? collect())->isNotEmpty())
                <x-hnz.card title="Expiring soon" subtitle="These switch off within 7 days.">
                    <ul class="space-y-2">
                        @foreach ($expiringSoon as $expiring)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm dark:bg-amber-400/10">
                                <a href="{{ \App\Support\PublicDashboard::route('links.edit', $expiring->id) }}" class="truncate font-semibold text-neutral-800 hover:underline dark:text-neutral-100">
                                    {{ ($expiring->domain->hostname ?? 'href.nz').'/'.$expiring->slug }}
                                </a>
                                <span class="shrink-0 text-xs font-semibold text-amber-700 dark:text-amber-300">{{ $expiring->expires_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif

            @if (($topLinks ?? collect())->isNotEmpty())
                <x-hnz.card title="Top performers" subtitle="Your most-clicked links.">
                    <ul class="space-y-2">
                        @foreach ($topLinks as $top)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-neutral-50 px-3.5 py-2.5 text-sm dark:bg-white/[0.03]">
                                <a href="{{ \App\Support\PublicDashboard::route('links.show', $top->id) }}" class="truncate font-semibold text-neutral-800 hover:underline dark:text-neutral-100">
                                    {{ ($top->domain->hostname ?? 'href.nz').'/'.$top->slug }}
                                </a>
                                <span class="shrink-0 text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ number_format($top->click_count) }} clicks</span>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif

            @if (($byDomain ?? collect())->isNotEmpty())
                <x-hnz.card title="Links by domain">
                    <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-1">
                        @foreach ($byDomain as $row)
                            <li class="flex items-center justify-between gap-3 rounded-xl bg-neutral-50 px-3.5 py-2.5 dark:bg-white/[0.03]">
                                <x-hnz.chip>{{ $row->hostname }}</x-hnz.chip>
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                    <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->link_count) }}</strong> links ·
                                    <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->click_sum) }}</strong> clicks
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif

            @if (($recentClicks ?? collect())->isNotEmpty())
                <x-hnz.card title="Latest clicks" subtitle="Fresh traffic across your links.">
                    <ul class="space-y-2">
                        @foreach ($recentClicks as $click)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-neutral-50 px-3.5 py-2.5 text-sm dark:bg-white/[0.03]">
                                <a href="{{ \App\Support\PublicDashboard::route('links.show', $click->link->id) }}" class="truncate font-semibold text-neutral-800 hover:underline dark:text-neutral-100">
                                    {{ ($click->link->domain->hostname ?? 'href.nz').'/'.$click->link->slug }}
                                </a>
                                <span class="shrink-0 text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $click->created_at->diffForHumans() }}@if ($click->referrer) · {{ Str::limit(parse_url($click->referrer, PHP_URL_HOST) ?? $click->referrer, 24) }}@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif
        </div>
    </div>

    <script type="application/json" id="tl-tour-steps">
        {!! json_encode([
            'id' => 'public',
            'autostart' => ($stats['total_links'] ?? 0) === 0,
            'theme' => 'public-new',
            'steps' => [
                ['target' => null, 'title' => 'Welcome to '.\App\Support\PublicDashboard::label(), 'body' => 'Your href.nz, meinlink.at and href.yt links live here. Create your first link, then come back for stats, API keys and domains.'],
            ],
        ]) !!}
    </script>
</x-layouts.hnz-dashboard>
