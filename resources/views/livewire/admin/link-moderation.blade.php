<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by slug or destination URL..."
            style="max-width: 320px; padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
        <select
            wire:model.live="status"
            style="padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
            <option value="expired">Expired</option>
        </select>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th wire:click="sort('slug')" style="cursor: pointer;">
                        Slug
                        @if ($sortBy === 'slug') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                    </th>
                    <th>Destination</th>
                    <th>Domain</th>
                    <th>Owner</th>
                    <th wire:click="sort('click_count')" style="cursor: pointer;">
                        Clicks
                        @if ($sortBy === 'click_count') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                    </th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td style="font-weight: 600;">{{ $link->slug }}</td>
                        <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" style="color: var(--text-secondary);">
                                {{ $link->destination_url }}
                            </a>
                        </td>
                        <td><code>{{ $link->domain->hostname ?? '—' }}</code></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                            {{ $link->user?->email ?? 'Guest' }}
                        </td>
                        <td><strong style="color: var(--primary);">{{ number_format($link->click_count) }}</strong></td>
                        <td>
                            @if ($link->is_active && !$link->isExpired())
                                <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;">● Active</span>
                            @elseif ($link->isExpired())
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Expired</span>
                            @else
                                <span style="color: var(--danger); font-size: 0.85rem;">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                @if ($link->is_active)
                                    <button wire:click="deactivate({{ $link->id }})" wire:confirm="Deactivate this link?" class="btn btn-danger btn-sm">Deactivate</button>
                                @else
                                    <button wire:click="reactivate({{ $link->id }})" class="btn btn-secondary btn-sm">Reactivate</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No links found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $links->links() }}
    </div>
</div>
