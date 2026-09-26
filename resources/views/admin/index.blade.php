<x-layouts.admin title="Admin Overview — ternis.link">
    <x-ui.page-header
        title="Admin Overview"
        subtitle="System-wide analytics and moderation. Admin host only."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.links') }}" size="sm">Moderate Links</x-ui.button>
            <x-ui.button href="{{ route('admin.users') }}" size="sm">Manage Users</x-ui.button>
            <x-ui.button href="{{ route('admin.domains') }}" size="sm">Moderate Domains</x-ui.button>
            <x-ui.button href="{{ route('admin.activity') }}" size="sm">Audit Log</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :value="number_format($stats['total_users'])" label="Total Users" />
        <x-ui.stat :value="number_format($stats['total_links'])" label="Total Links" />
        <x-ui.stat :value="number_format($stats['active_links'])" label="Active Links" />
        <x-ui.stat :value="number_format($stats['total_clicks'])" label="Total Clicks" />
        <x-ui.stat :value="number_format($stats['total_domains'])" label="Total Domains" />
        <x-ui.stat :value="number_format($stats['links_today'])" label="Links Today" />
        <x-ui.stat :value="number_format($stats['clicks_today'])" label="Clicks Today" />
        <x-ui.stat :value="number_format($stats['direct_url_clicks'])" label="Direct-URL Clicks" />
    </div>

    <x-ui.card title="Top Links by Clicks" class="mb-6">
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
                        <td class="max-w-[320px] truncate text-neutral-500 dark:text-neutral-400">{{ $link->destination_url }}</td>
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
</x-layouts.admin>
