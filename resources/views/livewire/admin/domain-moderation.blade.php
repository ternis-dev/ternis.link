<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by hostname or owner email..."
            style="max-width: 320px; padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
        <select
            wire:model.live="status"
            style="padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
            <option value="all">All domains</option>
            <option value="system">System</option>
            <option value="verified">Verified</option>
            <option value="pending">Pending DNS</option>
            <option value="disabled">Disabled</option>
        </select>
    </div>

    @error('domain') <div class="form-error" style="margin-bottom: 1rem;">{{ $message }}</div> @enderror

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th wire:click="sort('hostname')" style="cursor: pointer;">
                        Hostname
                        @if ($sortBy === 'hostname') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                    </th>
                    <th>Owner</th>
                    <th>Type</th>
                    <th>Links</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $domain)
                    <tr>
                        <td><code>{{ $domain->hostname }}</code></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                            {{ $domain->user?->email ?? 'System' }}
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $domain->type->value ?? $domain->type }}</td>
                        <td><strong style="color: var(--primary);">{{ number_format($domain->links_count) }}</strong></td>
                        <td>
                            @if ($domain->isSystemDomain())
                                <span style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 600;">● System</span>
                            @elseif (! $domain->is_active)
                                <span style="color: var(--danger); font-size: 0.85rem;">Disabled</span>
                            @elseif ($domain->isVerified())
                                <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;">● Verified</span>
                            @else
                                <span style="color: var(--warning, #b45309); font-size: 0.85rem; font-weight: 600;">● Pending DNS</span>
                            @endif
                        </td>
                        <td>
                            @if ($domain->isSystemDomain())
                                <span style="color: var(--text-muted); font-size: 0.8rem;">Protected</span>
                            @else
                                <div style="display: flex; gap: 0.5rem;">
                                    @if ($domain->is_active)
                                        <button wire:click="deactivate({{ $domain->id }})" wire:confirm="Disable {{ $domain->hostname }}? Links on it stop resolving, analytics are preserved." class="btn btn-danger btn-sm">Disable</button>
                                    @else
                                        <button wire:click="reactivate({{ $domain->id }})" class="btn btn-secondary btn-sm">Reactivate</button>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No domains found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $domains->links() }}
    </div>
</div>
