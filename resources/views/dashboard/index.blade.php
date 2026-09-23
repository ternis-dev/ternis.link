<x-layouts.dashboard title="Dashboard — ternis.link">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Dashboard</h1>
            <p style="color: var(--text-secondary); margin-top: 0.25rem;">
                Welcome back, {{ auth()->user()->name }} (Plan: <strong>{{ auth()->user()->plan?->name ?? 'free' }}</strong>)
                @if (auth()->user()?->isAdmin())
                    · <strong>Admin view: stats across ALL links</strong>
                @endif
            </p>
        </div>
        <a href="{{ route('dashboard.links.create') }}" class="btn btn-primary">+ Create Link</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['total_links']) }}</span>
            <span class="stat-label">Total Links</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['total_clicks']) }}</span>
            <span class="stat-label">Total Clicks</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['links_this_month']) }}</span>
            <span class="stat-label">Links This Month</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ number_format($stats['clicks_today']) }}</span>
            <span class="stat-label">Clicks Today</span>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 class="card-title" style="margin-bottom: 0;">Recent Links</h2>
            <a href="{{ route('dashboard.links') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <livewire:dashboard.link-table />
    </div>
</x-layouts.dashboard>
