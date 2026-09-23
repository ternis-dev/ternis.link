<div>
    <div class="analytics-toolbar">
        <div class="segmented" role="group" aria-label="Analytics period">
            @foreach (\App\Livewire\Dashboard\LinkAnalytics::PERIODS as $days)
                <button
                    type="button"
                    wire:click="setPeriod({{ $days }})"
                    @class(['active' => $period === $days])
                >{{ $days }}d</button>
            @endforeach
        </div>
        <a href="{{ route('dashboard.links.export', $link->id) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value">{{ number_format($totalClicks) }}</span>
            <span class="stat-label">Clicks · last {{ $period }} days</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($uniqueVisitors) }}</span>
            <span class="stat-label">Unique Visitors</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $averagePerDay }}</span>
            <span class="stat-label">Avg. per day</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $peakDay ? $peakDay['label'] : '—' }}</span>
            <span class="stat-label">Peak day{{ $peakDay ? ' ('.$peakDay['count'].')' : '' }}</span>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">Clicks over time</h3>
        @if ($totalClicks === 0)
            <p style="color: var(--text-muted); font-size: 0.9rem;">No clicks in the last {{ $period }} days yet. Share your link to see traffic here.</p>
        @else
            <div class="chart-bars" role="img" aria-label="Daily clicks for the last {{ $period }} days">
                @foreach ($clicksByDay as $day)
                    <div
                        class="chart-bar"
                        style="height: {{ max(3, round($day['count'] / $maxDailyClicks * 100)) }}%;"
                        title="{{ $day['label'] }}: {{ $day['count'] }} clicks"
                    ></div>
                @endforeach
            </div>
            <div class="chart-axis">
                <span>{{ $clicksByDay->first()['label'] }}</span>
                <span>{{ $clicksByDay->get((int) floor($clicksByDay->count() / 2))['label'] ?? '' }}</span>
                <span>{{ $clicksByDay->last()['label'] }}</span>
            </div>
        @endif
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div class="card" style="margin-bottom: 0;">
            <h3 class="card-title">Top Referrers</h3>
            @if ($topReferrers->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.9rem;">No referrer data recorded yet.</p>
            @else
                <div class="breakdown">
                    @foreach ($topReferrers as $ref)
                        @php($share = $totalClicks > 0 ? round($ref->count / $totalClicks * 100, 1) : 0)
                        <div class="breakdown-row">
                            <div class="breakdown-meta">
                                <span class="breakdown-label" title="{{ $ref->referrer }}">{{ $ref->referrer }}</span>
                                <span class="breakdown-value">{{ $ref->count }} · {{ $share }}%</span>
                            </div>
                            <div class="breakdown-track">
                                <div class="breakdown-fill" style="width: {{ $share }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card" style="margin-bottom: 0;">
            <h3 class="card-title">Top Countries</h3>
            @if ($topCountries->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.9rem;">No geo data recorded yet.</p>
            @else
                <div class="breakdown">
                    @foreach ($topCountries as $c)
                        @php($share = $totalClicks > 0 ? round($c->count / $totalClicks * 100, 1) : 0)
                        <div class="breakdown-row">
                            <div class="breakdown-meta">
                                <span class="breakdown-label">{{ $c->country_code }}</span>
                                <span class="breakdown-value">{{ $c->count }} · {{ $share }}%</span>
                            </div>
                            <div class="breakdown-track">
                                <div class="breakdown-fill" style="width: {{ $share }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">Browsers</h3>
        @if ($topBrowsers->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.9rem;">No browser data recorded yet.</p>
        @else
            <div class="breakdown">
                @foreach ($topBrowsers as $b)
                    @php($share = $totalClicks > 0 ? round($b['count'] / $totalClicks * 100, 1) : 0)
                    <div class="breakdown-row">
                        <div class="breakdown-meta">
                            <span class="breakdown-label">{{ $b['browser'] }}</span>
                            <span class="breakdown-value">{{ $b['count'] }} · {{ $share }}%</span>
                        </div>
                        <div class="breakdown-track">
                            <div class="breakdown-fill" style="width: {{ $share }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
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
