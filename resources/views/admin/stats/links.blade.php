<x-layouts.admin title="Links Stats — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Short Links Telemetry',
        'subtitle' => 'Link creation volume per day, domain distribution, privacy tracking adoption, and status metrics.',
        'section' => 'links',
        'params' => $params,
    ])

    {{-- Filter Bar Form --}}
    <form method="GET" action="{{ route('admin.stats.show', ['section' => 'links']) }}" class="mb-6 rounded-2xl border border-neutral-200/90 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <input type="hidden" name="days" value="{{ $stats['days'] }}">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 items-end">
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
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Status</label>
                <select name="status" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">Any Status</option>
                    <option value="active" @selected(($params['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($params['status'] ?? '') === 'inactive')>Inactive / Paused</option>
                    <option value="expired" @selected(($params['status'] ?? '') === 'expired')>Expired</option>
                    <option value="removed" @selected(($params['status'] ?? '') === 'removed')>Removed</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">User Tracking</label>
                <select name="tracking" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">All</option>
                    <option value="1" @selected(($params['tracking'] ?? '') === '1')>Enabled</option>
                    <option value="0" @selected(($params['tracking'] ?? '') === '0')>Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Password</label>
                <select name="password" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">All</option>
                    <option value="1" @selected(($params['password'] ?? '') === '1')>Password Protected</option>
                    <option value="0" @selected(($params['password'] ?? '') === '0')>Public</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 cursor-pointer">
                    Filter
                </button>
                @if (!empty($params['domain_id']) || !empty($params['status']) || isset($params['tracking']) || isset($params['password']))
                    <a href="{{ route('admin.stats.show', ['section' => 'links', 'days' => $stats['days']]) }}" class="rounded-lg border border-neutral-300 px-2.5 py-2 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Filtered Links</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['total_filtered']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">matching criteria</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Created Today</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['links_today']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">since 00:00 UTC</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Active Links</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['active_count']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">resolving visitors</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">User Tracking</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-purple-600 dark:text-purple-400">{{ number_format($stats['tracking_count']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">opted-in links</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Protected</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['password_count']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">password locked</p>
        </div>
    </div>

    {{-- Daily Creation Timeline Chart --}}
    <div class="mb-8">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['timeline'],
            'title' => 'Links Created Per Day',
            'color' => 'blue',
        ])
    </div>

    {{-- Breakdown Grid --}}
    <div class="grid gap-6 lg:grid-cols-2 mb-8">
        {{-- Distribution by Domain --}}
        <x-ui.card title="Links Distribution by Domain">
            <div class="space-y-3">
                @forelse ($stats['by_domain'] as $dom)
                    @php
                    $pct = $stats['total_filtered'] > 0 ? round(($dom->count / $stats['total_filtered']) * 100, 1) : 0;
                    @endphp
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-semibold text-neutral-800 dark:text-neutral-200">{{ $dom->domain?->hostname ?? 'Unknown' }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($dom->count) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $pct }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $pct }}%" class="h-full rounded-full bg-blue-500"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No domain link distribution found.</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Top Links in Category --}}
        <x-ui.card title="Top Links by Clicks">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Domain</th>
                        <th>Clicks</th>
                        <th>Owner</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats['top_links'] as $lnk)
                        <tr>
                            <td class="font-mono text-xs font-semibold">{{ $lnk->slug }}</td>
                            <td class="font-mono text-xs text-neutral-500">{{ $lnk->domain?->hostname ?? '—' }}</td>
                            <td class="font-bold text-neutral-900 dark:text-white">{{ number_format($lnk->click_count) }}</td>
                            <td class="text-xs text-neutral-500">{{ $lnk->user?->email ?? 'Guest' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-xs text-neutral-500 py-4">No links recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.admin>
