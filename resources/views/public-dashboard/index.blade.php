<x-layouts.public-dashboard title="Dashboard — my.href.nz">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}.</h2>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Here's how your public links are doing.</p>
        </div>
        <div class="flex gap-2">
            <span class="pd-domain-chip">href.nz</span>
            <span class="pd-domain-chip">meinlink.at</span>
            <span class="pd-domain-chip">href.yt</span>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="pd-stat pd-stat-accent">
            <div class="pd-stat-value">{{ number_format($stats['total_links']) }}</div>
            <div class="pd-stat-label">Total links</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['total_clicks']) }}</div>
            <div class="pd-stat-label">Total clicks</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['links_this_month']) }}</div>
            <div class="pd-stat-label">New this month</div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-value">{{ number_format($stats['clicks_today']) }}</div>
            <div class="pd-stat-label">Clicks today</div>
        </div>
    </div>

    <div class="pd-card rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-display text-lg font-bold tracking-tight">Recent links</h3>
            <a href="{{ route('public-dashboard.links') }}" class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">View all →</a>
        </div>
        <livewire:dashboard.link-table scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard>
