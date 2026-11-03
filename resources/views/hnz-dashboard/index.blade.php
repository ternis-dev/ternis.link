<x-layouts.hnz-dashboard title="Dashboard — {{ \App\Support\PublicDashboard::label() }}">
    {{-- Fresh Atmospheric Hero Banner --}}
    <section class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 p-6 sm:p-8 lg:p-10 shadow-xl shadow-violet-600/20 text-white">
        {{-- Decorative ambient mesh glows --}}
        <div class="pointer-events-none absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute right-1/3 -bottom-12 h-56 w-56 rounded-full bg-sky-400/20 blur-3xl" aria-hidden="true"></div>

        <div class="relative flex flex-col gap-6">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="min-w-0 max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold tracking-wider uppercase backdrop-blur-md">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            {{ \App\Support\PublicDashboard::label() }}
                        </span>
                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-semibold text-violet-100 backdrop-blur-md">
                            {{ auth()->user()->plan?->name ?? 'Free Tier' }}
                        </span>
                    </div>

                    <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        {{ trim(auth()->user()->name) === '' ? 'Welcome back' : 'Welcome back, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ') }}
                    </h1>

                    <p class="mt-2 text-sm text-violet-100/90 leading-relaxed">
                        Manage your public links across href.nz, meinlink.at and href.yt with live click tracking and zero latency redirects.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                    <x-hnz.button
                        type="button"
                        x-data
                        @click="$dispatch('open-link-creator')"
                        variant="secondary"
                        class="border-white/20 bg-white/15 text-white shadow-none backdrop-blur-md hover:border-white/40 hover:bg-white/25 hover:text-white"
                        icon="<svg class='h-4 w-4' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2'><path d='M12 5v14M5 12h14'/></svg>"
                    >
                        <span>New link</span>
                    </x-hnz.button>

                    <x-hnz.button
                        href="{{ \App\Support\PublicDashboard::route('insights') }}"
                        variant="secondary"
                        class="border-white/20 bg-white/15 text-white shadow-none backdrop-blur-md hover:border-white/40 hover:bg-white/25 hover:text-white"
                    >
                        Insights
                    </x-hnz.button>
                </div>
            </div>

            {{-- High-impact 4-metric glass cards --}}
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 pt-2">
                @foreach ([
                    ['label' => 'Total links', 'value' => number_format($stats['total_links']), 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
                    ['label' => 'Total clicks', 'value' => number_format($stats['total_clicks']), 'icon' => '<path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/>'],
                    ['label' => 'New this month', 'value' => number_format($stats['links_this_month']), 'icon' => '<rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>'],
                    ['label' => 'Clicks today', 'value' => number_format($stats['clicks_today']), 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                ] as $metric)
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md transition-transform duration-200 hover:-translate-y-0.5">
                        <div class="flex items-center justify-between text-violet-200">
                            <dt class="text-[10px] font-bold tracking-wider text-violet-100/80 uppercase">{{ $metric['label'] }}</dt>
                            <svg class="h-4 w-4 opacity-75" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $metric['icon'] !!}</svg>
                        </div>
                        <dd class="mt-2 font-display text-2xl font-bold text-white tabular-nums sm:text-3xl">{{ $metric['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Frontend-Heavy Quick Shorten Bar --}}
    <x-hnz.quick-shorten />

    {{-- 3-Column Overview Grid --}}
    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Main Column (Links Table) --}}
        <div class="xl:col-span-2">
            <x-hnz.card
                title="Your links"
                subtitle="Search, filter, reorder columns — everything stays per account."
            >
                <x-slot:actions>
                    <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">
                        Manage all
                    </x-hnz.button>
                </x-slot:actions>

                @if ($stats['total_links'] === 0)
                    <x-hnz.empty title="No short links yet">
                        Paste any long URL to get an 8-character link back — or import a whole CSV at once.
                    </x-hnz.empty>
                    <div class="mt-5 flex flex-wrap justify-center gap-2.5">
                        <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.create') }}" variant="primary" size="sm">
                            Create a link
                        </x-hnz.button>
                        <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.import') }}" variant="secondary" size="sm">
                            Import CSV
                        </x-hnz.button>
                    </div>
                @else
                    <livewire:dashboard.link-table scope="public" theme="public" />
                @endif
            </x-hnz.card>
        </div>

        {{-- Right Rail --}}
        <div class="space-y-6">
            {{-- Links by domain --}}
            @if (($byDomain ?? collect())->isNotEmpty())
                <x-hnz.card title="Links by domain" subtitle="Distribution across public shorteners.">
                    <ul class="space-y-3">
                        @foreach ($byDomain as $row)
                            <li class="rounded-2xl border border-neutral-200/60 bg-neutral-50/50 p-3.5 dark:border-white/5 dark:bg-white/[0.02]">
                                <div class="flex items-center justify-between gap-3">
                                    <x-hnz.chip>{{ $row->hostname }}</x-hnz.chip>
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                        <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->link_count) }}</strong> links ·
                                        <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($row->click_sum) }}</strong> clicks
                                    </span>
                                </div>
                                @php($pct = $stats['total_links'] > 0 ? round(($row->link_count / $stats['total_links']) * 100) : 0)
                                <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200/70 dark:bg-neutral-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-violet-600 to-blue-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif

            {{-- Top performers --}}
            @if (($topLinks ?? collect())->isNotEmpty())
                <x-hnz.card title="Top performers" subtitle="Your most-clicked links.">
                    <ul class="space-y-2.5">
                        @foreach ($topLinks as $idx => $top)
                            <li class="flex items-center justify-between gap-3 rounded-2xl border border-neutral-200/60 bg-neutral-50/50 p-3 text-sm dark:border-white/5 dark:bg-white/[0.02]">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-[10px] font-bold text-violet-700 dark:bg-violet-400/20 dark:text-violet-300">
                                        #{{ $idx + 1 }}
                                    </span>
                                    <a href="{{ \App\Support\PublicDashboard::route('links.show', $top->id) }}" class="truncate font-semibold text-neutral-800 hover:text-violet-600 hover:underline dark:text-neutral-100 dark:hover:text-violet-300">
                                        {{ ($top->domain->hostname ?? 'href.nz').'/'.$top->slug }}
                                    </a>
                                </div>
                                <span class="shrink-0 rounded-full bg-neutral-200/80 px-2 py-0.5 text-xs font-bold tabular-nums text-neutral-700 dark:bg-white/10 dark:text-neutral-300">
                                    {{ number_format($top->click_count) }} clicks
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif

            {{-- Expiring soon --}}
            @if (($expiringSoon ?? collect())->isNotEmpty())
                <x-hnz.card title="Expiring soon" subtitle="These switch off within 7 days.">
                    <ul class="space-y-2.5">
                        @foreach ($expiringSoon as $expiring)
                            <li class="flex items-center justify-between gap-2.5 rounded-2xl border border-amber-200/60 bg-amber-50/60 p-3 text-sm dark:border-amber-400/20 dark:bg-amber-400/10">
                                <a href="{{ \App\Support\PublicDashboard::route('links.edit', $expiring->id) }}" class="truncate font-semibold text-neutral-800 hover:underline dark:text-neutral-100">
                                    {{ ($expiring->domain->hostname ?? 'href.nz').'/'.$expiring->slug }}
                                </a>
                                <span class="shrink-0 rounded-lg bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-400/20 dark:text-amber-200">
                                    {{ $expiring->expires_at->diffForHumans() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-hnz.card>
            @endif

            {{-- Latest clicks --}}
            @if (($recentClicks ?? collect())->isNotEmpty())
                <x-hnz.card title="Latest clicks" subtitle="Fresh traffic across your links.">
                    <ul class="space-y-2.5">
                        @foreach ($recentClicks as $click)
                            <li class="flex items-center justify-between gap-2.5 rounded-2xl border border-neutral-200/60 bg-neutral-50/50 p-3 text-sm dark:border-white/5 dark:bg-white/[0.02]">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                                    <a href="{{ \App\Support\PublicDashboard::route('links.show', $click->link->id) }}" class="truncate font-semibold text-neutral-800 hover:underline dark:text-neutral-100">
                                        {{ ($click->link->domain->hostname ?? 'href.nz').'/'.$click->link->slug }}
                                    </a>
                                </div>
                                <span class="shrink-0 text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $click->created_at->diffForHumans() }}@if ($click->referrer) · {{ Str::limit(parse_url($click->referrer, PHP_URL_HOST) ?? $click->referrer, 18) }}@endif
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
