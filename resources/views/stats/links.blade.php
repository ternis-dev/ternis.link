<x-layouts.app title="Top Links — ternis.link stats">
    <x-ui.page-header
        title="Top Links"
        subtitle="Most-clicked short links across the network. Slugs are public; no owners, no destinations."
        :backHref="route('stats.index')"
        backLabel="Back to Stats Overview"
    />

    <x-ui.card>
        <x-ui.table>
            <thead>
                <tr>
                    <th>Short Link</th>
                    <th>Clicks</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td class="font-semibold"><code>{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</code></td>
                        <td class="font-bold">{{ number_format($link->click_count) }}</td>
                        <td>
                            @if ($link->is_removed)
                                <x-ui.status state="removed" />
                            @elseif ($link->is_active && !$link->isExpired())
                                <x-ui.status state="active" />
                            @elseif ($link->isExpired())
                                <x-ui.status state="expired" />
                            @else
                                <x-ui.status state="disabled" />
                            @endif
                        </td>
                        <td class="text-xs whitespace-nowrap text-neutral-500">{{ $link->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4"><x-ui.empty-state>No links yet.</x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</x-layouts.app>
