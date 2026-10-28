<x-layouts.admin title="Admin Overview — ternis.link">
    <x-ui.page-header
        title="Admin Overview"
        subtitle="System-wide analytics and moderation. Admin host only."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.stats') }}" size="sm">Full Telemetry</x-ui.button>
            <x-ui.button href="{{ route('admin.links') }}" size="sm">Moderate Links</x-ui.button>
            <x-ui.button href="{{ route('admin.users') }}" size="sm">Manage Users</x-ui.button>
            <x-ui.button href="{{ route('admin.domains') }}" size="sm">Moderate Domains</x-ui.button>
            <x-ui.button href="{{ route('admin.activity') }}" size="sm">Audit Log</x-ui.button>
            <x-ui.button href="{{ route('admin.errors') }}" size="sm">Errors</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :value="number_format($stats['total_users'])" label="Total Users" />
        <x-ui.stat :value="number_format($stats['total_links'])" label="Total Links" />
        <x-ui.stat :value="number_format($stats['active_links'])" label="Active Links" />
        <x-ui.stat :value="number_format($stats['removed_links'])" label="Removed Links" />
        <x-ui.stat :value="number_format($stats['total_clicks'])" label="Total Clicks" />
        <x-ui.stat :value="number_format($stats['clicks_today'])" label="Clicks Today" />
        <x-ui.stat :value="number_format($stats['links_today'])" label="Links Today" />
        <x-ui.stat :value="number_format($stats['direct_url_clicks'])" label="Direct-URL Clicks" />
        <x-ui.stat :value="number_format($stats['total_domains'])" label="Total Domains" />
        <x-ui.stat :value="number_format($stats['active_api_keys'])" label="Active API Keys" />
        <x-ui.stat :value="number_format($stats['qr_codes'])" label="QR Codes Generated" />
        <x-ui.stat :value="number_format($stats['errors_today'])" label="Errors Today" />
    </div>

    {{-- 30-Day Traffic & Link Creation Multi-Series Chart --}}
    <div class="mb-6">
        @include('admin.stats._timeline-chart', [
            'labels' => array_keys($telemetry['clicks_timeline']),
            'datasets' => [
                [
                    'label' => 'Clicks',
                    'values' => array_values($telemetry['clicks_timeline']),
                    'color' => 'amber',
                    'unit' => 'click',
                ],
                [
                    'label' => 'Links Created',
                    'values' => array_values($telemetry['links_timeline']),
                    'color' => 'blue',
                    'unit' => 'link',
                ],
            ],
            'title' => '30-Day Platform Traffic & Link Creation',
            'subtitle' => 'Daily clicks and new short links across all platform and partner domains.',
            'unit' => 'event',
        ])
    </div>

    {{-- System Activity Heatmap (7x24 Weekday x Hour) --}}
    <section
        x-data="{ signal: 'clicks' }"
        class="mb-6 rounded-2xl border border-neutral-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
    >
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-neutral-100 pb-4 dark:border-neutral-800">
            <div>
                <h2 class="font-display text-base font-semibold tracking-tight text-neutral-900 dark:text-white">System Activity Heatmap (Last 30 Days)</h2>
                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">7&times;24 UTC weekday and hour intensity across platform signals.</p>
            </div>
            <div class="inline-flex flex-wrap items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-xs dark:border-neutral-800 dark:bg-neutral-950" role="tablist" aria-label="System signal">
                @foreach (['clicks' => 'Clicks', 'links' => 'Links', 'activities' => 'Audit Log', 'errors' => 'Errors'] as $key => $lbl)
                    <button
                        type="button"
                        role="tab"
                        @click="signal = '{{ $key }}'"
                        :aria-selected="(signal === '{{ $key }}').toString()"
                        :class="signal === '{{ $key }}'
                            ? 'bg-white font-semibold text-neutral-900 shadow-xs dark:bg-neutral-800 dark:text-white'
                            : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'"
                        class="cursor-pointer rounded-md px-2.5 py-1 transition-all"
                    >
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="signal === 'clicks'">
            <x-ui.heatmap :heatmap="$telemetry['heatmaps']['clicks']" color="amber" unit="click" :wrapped="false" />
        </div>
        <div x-show="signal === 'links'" x-cloak>
            <x-ui.heatmap :heatmap="$telemetry['heatmaps']['links']" color="blue" unit="link" :wrapped="false" />
        </div>
        <div x-show="signal === 'activities'" x-cloak>
            <x-ui.heatmap :heatmap="$telemetry['heatmaps']['activities']" color="purple" unit="event" :wrapped="false" />
        </div>
        <div x-show="signal === 'errors'" x-cloak>
            <x-ui.heatmap :heatmap="$telemetry['heatmaps']['errors']" color="red" unit="error" :wrapped="false" />
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Top Links by Clicks">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Domain</th>
                        <th>Owner</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topLinks as $link)
                        <tr>
                            <td class="font-semibold">{{ $link->slug }}</td>
                            <td><code>{{ $link->domain->hostname ?? '—' }}</code></td>
                            <td class="tl-sensitive text-xs text-neutral-500" title="{{ $link->user?->email ?? 'Guest' }}">{{ $link->user?->email ?? 'Guest' }}</td>
                            <td class="font-bold">{{ number_format($link->click_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-ui.empty-state>No links yet.</x-ui.empty-state></td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Recent Links">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Destination</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLinks as $link)
                        <tr>
                            <td class="font-semibold">{{ $link->slug }}</td>
                            <td class="max-w-[260px] truncate text-neutral-500 dark:text-neutral-400">{{ $link->destination_url }}</td>
                            <td>
                                @if ($link->is_active && !$link->isExpired())
                                    <x-ui.status state="active" />
                                @else
                                    <x-ui.status state="disabled" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"><x-ui.empty-state>No links yet.</x-ui.empty-state></td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.admin>
