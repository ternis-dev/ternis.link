<x-layouts.admin title="Clicks Stats — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Link Clicks Traffic Telemetry',
        'subtitle' => 'Traffic volume per day, device types, referrer channels, geographic origins, and query parameters.',
        'section' => 'clicks',
        'params' => $params,
    ])

    {{-- Filter Bar Form --}}
    <form method="GET" action="{{ route('admin.stats.show', ['section' => 'clicks']) }}" class="mb-6 rounded-2xl border border-neutral-200/90 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <input type="hidden" name="days" value="{{ $stats['days'] }}">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 items-end">
            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Device Type</label>
                <select name="device" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">Any Device</option>
                    <option value="desktop" @selected(($params['device'] ?? '') === 'desktop')>Desktop</option>
                    <option value="mobile" @selected(($params['device'] ?? '') === 'mobile')>Mobile</option>
                    <option value="tablet" @selected(($params['device'] ?? '') === 'tablet')>Tablet</option>
                    <option value="bot" @selected(($params['device'] ?? '') === 'bot')>Bot / Crawler</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Domain</label>
                <select name="domain_id" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">All Domains</option>
                    @foreach ($stats['domains'] as $d)
                        <option value="{{ $d->id }}" @selected(($params['domain_id'] ?? '') == $d->id)>{{ $d->hostname }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Country (ISO)</label>
                <input type="text" name="country" value="{{ $params['country'] ?? '' }}" placeholder="e.g. US, DE, AT" maxlength="2" class="w-full uppercase rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Query Params</label>
                <select name="has_params" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">All Clicks</option>
                    <option value="1" @selected(($params['has_params'] ?? '') === '1')>With Parameters</option>
                    <option value="0" @selected(($params['has_params'] ?? '') === '0')>Without Parameters</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Click Tags</label>
                <select name="has_tags" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">All Clicks</option>
                    <option value="1" @selected(($params['has_tags'] ?? '') === '1')>Tagged Clicks</option>
                    <option value="0" @selected(($params['has_tags'] ?? '') === '0')>Untagged Clicks</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 cursor-pointer">
                    Filter
                </button>
                @if (!empty($params['device']) || !empty($params['domain_id']) || !empty($params['country']) || isset($params['has_params']) || isset($params['has_tags']))
                    <a href="{{ route('admin.stats.show', ['section' => 'clicks', 'days' => $stats['days']]) }}" class="rounded-lg border border-neutral-300 px-2.5 py-2 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Filtered Clicks</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['total_filtered']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">in selected window</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Clicks Today</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['clicks_today']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">since 00:00 UTC</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Unique Users</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['unique_users']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">pseudonymized identifiers</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">With Query Params</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-amber-600 dark:text-amber-400">{{ number_format($stats['with_params']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">captured &amp; forwarded</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Tagged Clicks</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-purple-600 dark:text-purple-400">{{ number_format($stats['with_tags']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">via apps &amp; headers</p>
        </div>
    </div>

    {{-- Daily Clicks Timeline Chart --}}
    <div class="mb-8">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['timeline'],
            'title' => 'Clicks Received Per Day',
            'color' => 'amber',
        ])
    </div>

    {{-- Breakdown Grid --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-8">
        {{-- Device Breakdown --}}
        <x-ui.card title="Device Breakdown">
            <div class="space-y-3">
                @forelse ($stats['by_device'] as $dev)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium capitalize text-neutral-800 dark:text-neutral-200">{{ $dev['device'] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($dev['count']) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $dev['percentage'] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $dev['percentage'] }}%" class="h-full rounded-full bg-amber-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No clicks recorded.</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Referrer Channels --}}
        <x-ui.card title="Top Referrer Sources">
            <div class="space-y-3">
                @forelse ($stats['by_referer'] as $ref)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-neutral-800 dark:text-neutral-200 max-w-[140px] truncate" title="{{ $ref['referer'] }}">{{ $ref['referer'] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($ref['count']) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $ref['percentage'] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $ref['percentage'] }}%" class="h-full rounded-full bg-blue-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No referrers recorded.</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Country Breakdown --}}
        <x-ui.card title="Top Countries">
            <div class="space-y-3">
                @forelse ($stats['by_country'] as $c)
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-semibold text-neutral-800 dark:text-neutral-200">{{ $c['country'] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($c['count']) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $c['percentage'] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $c['percentage'] }}%" class="h-full rounded-full bg-emerald-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No country data recorded.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
