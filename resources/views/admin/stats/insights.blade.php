<x-layouts.admin title="System Insights — admin.ternis.link">
    @include("admin.stats._header", [
        "title" => "System-wide Traffic & Behavioral Insights",
        "subtitle" => "Traffic heatmaps, daily contribution calendar, peak activity hours, 24-hour distribution profiles, and expiration calendars.",
        "section" => "insights",
        "params" => $params,
    ])

    {{-- Highlight KPI Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Clicks in Window</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-amber-600 dark:text-amber-400">{{ number_format($stats["total_clicks_in_window"]) }}</p>
            <p class="mt-1 text-xs text-neutral-500">across the last {{ $stats["window_days"] ?? $stats["days"] }} days</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Active Links</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats["active_links"]) }}</p>
            <p class="mt-1 text-xs text-neutral-500">total system links: {{ number_format($stats["total_links"]) }}</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Peak Traffic Slot</span>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-neutral-900 dark:text-white truncate">
                {{ !empty($stats["best_times"]) ? $stats["best_times"][0]["label"] : "—" }}
            </p>
            <p class="mt-1 text-xs text-neutral-500">
                @if (!empty($stats["best_times"]))
                    {{ number_format($stats["best_times"][0]["count"]) }} clicks recorded
                @else
                    no clicks recorded yet
                @endif
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Expiring in 60 Days</span>
            @php
                $expiringTotal = $stats["expiry_calendar"]->sum(fn ($day) => $day["links"]->count());
            @endphp
            <p class="mt-2 text-3xl font-extrabold tracking-tight {{ $expiringTotal > 0 ? "text-amber-600 dark:text-amber-400" : "text-neutral-900 dark:text-white" }}">{{ number_format($expiringTotal) }}</p>
            <p class="mt-1 text-xs text-neutral-500">monitored expiration dates</p>
        </div>
    </div>

    {{-- 7x24 Heatmap & Peak Hours Grid --}}
    <div class="mb-8 grid gap-6 xl:grid-cols-3">
        <x-ui.heatmap
            :heatmap="$stats['heatmap']"
            title="Activity Heatmap (7x24 Weekday & Hour)"
            subtitle="System-wide click intensity per weekday and UTC hour."
            color="amber"
            unit="click"
            class="xl:col-span-2"
        />

        <section class="rounded-2xl border border-neutral-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4">
                <h2 class="font-display text-base font-semibold tracking-tight text-neutral-900 dark:text-white">Peak Traffic Hours</h2>
                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">The most active hourly slots across the platform.</p>
            </div>
            @if (empty($stats["best_times"]))
                <p class="text-xs text-neutral-500">No clicks recorded in this period.</p>
            @else
                <ul class="space-y-2.5">
                    @foreach ($stats["best_times"] as $i => $slot)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200/80 bg-neutral-50/80 px-3.5 py-2.5 text-xs dark:border-neutral-800 dark:bg-neutral-950/40">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white">{{ $i + 1 }}</span>
                                <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $slot["label"] }}</span>
                            </div>
                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ number_format($slot["count"]) }} clicks</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Daily Clicks Calendar Heatmap --}}
    <div class="mb-8">
        <x-ui.calendar-heatmap
            :days="$stats['clicks_calendar'] ?? []"
            title="Daily Clicks Contribution Calendar"
            subtitle="Day-by-day redirect volume across the selected window."
            color="amber"
            unit="click"
        />
    </div>

    {{-- Hourly 24h Profile Line Graph --}}
    <div class="mb-8">
        @php
            $profileTimeline = [];
            foreach ($stats["hourly_profile"] as $hp) {
                $hLabel = str_pad((string) $hp["hour"], 2, "0", STR_PAD_LEFT) . ":00";
                $profileTimeline[$hLabel] = $hp["count"];
            }
        @endphp
        @include("admin.stats._timeline-chart", [
            "timeline" => $profileTimeline,
            "title" => "Hourly Click Profile (Aggregate 24-Hour Traffic Curve)",
            "color" => "amber",
            "unit" => "click",
        ])
    </div>

    {{-- Top Performing Links (Highest Clicks) --}}
    <section class="rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 mb-8">
        <div class="mb-4">
            <h2 class="font-display text-lg font-semibold tracking-tight">Top Platform Links by Clicks</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Platform short links with highest recorded click volumes.</p>
        </div>
        <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Domain</th>
                        <th>Owner</th>
                        <th>Destination URL</th>
                        <th>Clicks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats["top_links"] as $link)
                        <tr>
                            <td class="font-semibold font-mono text-xs">{{ $link->slug }}</td>
                            <td><code>{{ $link->domain->hostname ?? "—" }}</code></td>
                            <td class="tl-sensitive text-xs text-neutral-500" title="{{ $link->user?->email ?? "Guest" }}">{{ $link->user?->email ?? "Guest" }}</td>
                            <td class="max-w-[280px] truncate text-xs text-neutral-500">{{ $link->destination_url }}</td>
                            <td class="font-bold text-amber-600 dark:text-amber-400">{{ number_format($link->click_count) }}</td>
                            <td>
                                <a href="{{ route("admin.links", ["search" => $link->slug]) }}" class="inline-flex items-center rounded-md border border-neutral-300 bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700">Moderate</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-xs text-neutral-500 py-4">No short links yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Referrers, Geo & Expiry Grid --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-8">
        {{-- Top Referrers --}}
        <section class="rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4">
                <h2 class="font-display text-lg font-semibold tracking-tight">Top Referrers</h2>
            </div>
            @if (!empty($stats["top_referrers"]))
                @php
                    $topRefSlice = array_slice($stats["top_referrers"], 0, 5);
                    $topRefLabels = array_map(fn ($r) => parse_url($r["referrer"], PHP_URL_HOST) ?: $r["referrer"], $topRefSlice);
                    $topRefValues = array_column($topRefSlice, "count");
                @endphp
                <div class="relative mb-4 h-40 w-full" role="img" aria-label="Top referrers">
                    <canvas
                        data-chart="horizontal-bar"
                        data-chart-color="blue"
                        data-chart-unit="click"
                        data-chart-labels='@json($topRefLabels)'
                        data-chart-values='@json($topRefValues)'
                    ></canvas>
                </div>
            @endif
            <div class="space-y-3">
                @forelse ($stats["top_referrers"] as $ref)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-neutral-800 dark:text-neutral-200 max-w-[140px] truncate" title="{{ $ref["referrer"] }}">{{ $ref["referrer"] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($ref["count"]) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $ref["share"] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $ref["share"] }}%" class="h-full rounded-full bg-blue-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No referrers recorded.</p>
                @endforelse
            </div>
        </section>

        {{-- Top Countries --}}
        <section class="rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4">
                <h2 class="font-display text-lg font-semibold tracking-tight">Top Countries</h2>
            </div>
            @if (!empty($stats["top_countries"]))
                @php
                    $topCountrySlice = array_slice($stats["top_countries"], 0, 5);
                    $topCountryLabels = array_column($topCountrySlice, "country");
                    $topCountryValues = array_column($topCountrySlice, "count");
                @endphp
                <div class="relative mb-4 h-40 w-full" role="img" aria-label="Top countries">
                    <canvas
                        data-chart="horizontal-bar"
                        data-chart-color="emerald"
                        data-chart-unit="click"
                        data-chart-labels='@json($topCountryLabels)'
                        data-chart-values='@json($topCountryValues)'
                    ></canvas>
                </div>
            @endif
            <div class="space-y-3">
                @forelse ($stats["top_countries"] as $c)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-semibold text-neutral-800 dark:text-neutral-200">{{ $c["country"] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($c["count"]) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $c["share"] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $c["share"] }}%" class="h-full rounded-full bg-emerald-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No country data recorded.</p>
                @endforelse
            </div>
        </section>

        {{-- Expiry Calendar --}}
        <section class="rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4">
                <h2 class="font-display text-lg font-semibold tracking-tight">Expiring Links (Next 60 Days)</h2>
            </div>
            @if ($stats["expiry_calendar"]->isEmpty())
                <p class="text-xs text-neutral-500">No links expiring in the next 60 days.</p>
            @else
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1 no-scrollbar">
                    @foreach ($stats["expiry_calendar"] as $day)
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">{{ $day["label"] }}</span>
                            <ul class="mt-1 space-y-1.5">
                                @foreach ($day["links"] as $link)
                                    <li class="flex items-center justify-between gap-2 rounded-lg bg-amber-50/60 px-2.5 py-1.5 text-xs dark:bg-amber-950/30">
                                        <a href="{{ route("admin.links", ["search" => $link->slug]) }}" class="font-mono truncate hover:underline text-neutral-800 dark:text-neutral-200">
                                            {{ $link->domain?->hostname }}/{{ $link->slug }}
                                        </a>
                                        <span class="shrink-0 text-[11px] text-amber-700 dark:text-amber-400 font-semibold">{{ $link->expires_at->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
