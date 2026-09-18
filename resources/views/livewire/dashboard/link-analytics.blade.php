<div>
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value">{{ number_format($totalClicks) }}</span>
            <span class="stat-label">Total Tracked Clicks</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($uniqueVisitors) }}</span>
            <span class="stat-label">Unique Visitors</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div class="card">
            <h3 class="card-title">Top Referrers</h3>
            @if ($topReferrers->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.9rem;">No referrer data recorded yet.</p>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Referrer</th>
                                <th style="text-align: right;">Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topReferrers as $ref)
                                <tr>
                                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $ref->referrer }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">{{ $ref->count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card">
            <h3 class="card-title">Top Countries</h3>
            @if ($topCountries->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.9rem;">No geo data recorded yet.</p>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th style="text-align: right;">Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topCountries as $c)
                                <tr>
                                    <td>{{ $c->country_code }}</td>
                                    <td style="text-align: right; font-weight: 600;">{{ $c->count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">Recent Clicks</h3>
        @if ($recentClicks->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.9rem;">No clicks recorded yet.</p>
        @else
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Referrer</th>
                            <th>User Agent</th>
                            <th>IP Hash</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentClicks as $click)
                            <tr>
                                <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                                    {{ $click->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $click->referrer ?? 'Direct / None' }}
                                </td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85rem; color: var(--text-secondary);">
                                    {{ $click->user_agent ?? 'Unknown' }}
                                </td>
                                <td>
                                    <code>{{ substr($click->ip_hash, 0, 10) }}…</code>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
