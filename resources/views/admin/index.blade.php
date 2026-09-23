<x-layouts.dashboard title="Admin Overview — ternis.link">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Admin Overview</h1>
            <p style="color: var(--text-secondary); margin-top: 0.25rem;">
                System-wide analytics and moderation. Admin host only.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.links') }}" class="btn btn-secondary btn-sm">Moderate Links</a>
            <a href="{{ route('admin.users') }}" class="btn btn-secondary btn-sm">Manage Users</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['total_users']) }}</span>
            <span class="stat-label">Total Users</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['total_links']) }}</span>
            <span class="stat-label">Total Links</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['active_links']) }}</span>
            <span class="stat-label">Active Links</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['total_clicks']) }}</span>
            <span class="stat-label">Total Clicks</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['total_domains']) }}</span>
            <span class="stat-label">Total Domains</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['links_today']) }}</span>
            <span class="stat-label">Links Today</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['clicks_today']) }}</span>
            <span class="stat-label">Clicks Today</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['direct_url_clicks']) }}</span>
            <span class="stat-label">Direct-URL Clicks</span>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">Top Links by Clicks</h2>
        <div class="table-container">
            <table>
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
                            <td style="font-weight: 600;">{{ $link->slug }}</td>
                            <td><code>{{ $link->domain->hostname ?? '—' }}</code></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $link->user?->email ?? 'Guest' }}</td>
                            <td><strong style="color: var(--primary);">{{ number_format($link->click_count) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">No links yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">Recent Links</h2>
        <div class="table-container">
            <table>
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
                            <td style="font-weight: 600;">{{ $link->slug }}</td>
                            <td style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-secondary);">{{ $link->destination_url }}</td>
                            <td>
                                @if ($link->is_active && !$link->isExpired())
                                    <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;">● Active</span>
                                @else
                                    <span style="color: var(--danger); font-size: 0.85rem;">Disabled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">No links yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.dashboard>
