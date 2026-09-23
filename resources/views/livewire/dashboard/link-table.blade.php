<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ auth()->user()?->isAdmin() ? 'Search by slug, URL or owner...' : 'Search by slug or destination URL...' }}"
            style="max-width: 400px; padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
        <a href="{{ route('dashboard.links.create') }}" class="btn btn-primary">+ Create Link</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th wire:click="sort('slug')" style="cursor: pointer;">
                        Short Link
                        @if ($sortBy === 'slug') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                    </th>
                    <th>Destination URL</th>
                    <th>Domain</th>
                    @if (auth()->user()?->isAdmin())
                        <th>Owner</th>
                    @endif
                    <th wire:click="sort('click_count')" style="cursor: pointer;">
                        Clicks
                        @if ($sortBy === 'click_count') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                    </th>
                    <th>Status</th>
                    <th wire:click="sort('created_at')" style="cursor: pointer;">
                        Created
                        @if ($sortBy === 'created_at') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td>
                            <a href="{{ route('dashboard.links.show', $link->id) }}" style="font-weight: 600;">
                                {{ $link->slug }}
                            </a>
                        </td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" style="color: var(--text-secondary);">
                                {{ $link->destination_url }}
                            </a>
                        </td>
                        <td>
                            <code>{{ $link->domain->hostname ?? 'href.nz' }}</code>
                        </td>
                        @if (auth()->user()?->isAdmin())
                            <td style="color: var(--text-muted); font-size: 0.85rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $link->user?->email ?? 'Guest' }}">
                                {{ $link->user?->email ?? 'Guest' }}
                            </td>
                        @endif
                        <td>
                            <strong style="color: var(--primary);">{{ number_format($link->click_count) }}</strong>
                        </td>
                        <td>
                            @if ($link->is_active && !$link->isExpired())
                                <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;">● Active</span>
                            @elseif ($link->isExpired())
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Expired</span>
                            @else
                                <span style="color: var(--danger); font-size: 0.85rem;">Disabled</span>
                            @endif
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                            {{ $link->created_at->format('M d, Y') }}
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('dashboard.links.show', $link->id) }}" class="btn btn-secondary btn-sm">Analytics</a>
                                @if ($link->is_active)
                                    <button wire:click="deactivate({{ $link->id }})" wire:confirm="Deactivate this link?" class="btn btn-danger btn-sm">Deactivate</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->isAdmin() ? 8 : 7 }}" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No short links found. <a href="{{ route('dashboard.links.create') }}">Create your first short link!</a>
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
