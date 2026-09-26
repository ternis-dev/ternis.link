<x-layouts.app title="Links Per Domain — ternis.link stats">
    <x-ui.page-header
        title="Links Per Domain"
        subtitle="How the network splits across domains. Aggregate counts only."
        :backHref="route('pages.stats.index')"
        backLabel="Back to Stats Overview"
    />

    <x-ui.card>
        <x-ui.table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Links</th>
                    <th>Clicks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $domain)
                    <tr>
                        <td><code>{{ $domain->hostname }}</code></td>
                        <td class="font-bold">{{ number_format($domain->links_count) }}</td>
                        <td class="font-bold">{{ number_format($domain->links_sum_click_count ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"><x-ui.empty-state>No domains yet.</x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</x-layouts.app>
