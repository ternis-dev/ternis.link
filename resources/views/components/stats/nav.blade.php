<nav data-stats-nav aria-label="Statistics" class="mb-6 flex flex-wrap gap-1 rounded-xl border border-neutral-200 bg-white p-1.5 dark:border-neutral-800 dark:bg-neutral-900">
    @foreach (['index' => 'Overview', 'activity' => 'Activity', 'security' => 'Security', 'domains' => 'Domains'] as $page => $label)
        <a href="{{ route('pages.stats.'.$page) }}" data-stats-tab="{{ $page }}" @if(request()->routeIs('pages.stats.'.$page)) aria-current="page" @endif class="rounded-lg px-4 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 {{ request()->routeIs('pages.stats.'.$page) ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">{{ $label }}</a>
    @endforeach
</nav>
<div class="mb-6 flex flex-wrap items-center justify-between gap-2 text-xs text-neutral-500 dark:text-neutral-400">
    <p>Aggregate data · Today starts at midnight {{ config('app.timezone') }}</p>
    <div data-stats-status class="inline-flex items-center gap-2 font-mono text-[11px]">
        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" data-stats-indicator></span>
        <span data-stats-last-updated>Updated just now</span>
        <span class="text-neutral-400 dark:text-neutral-600">·</span>
        <span data-stats-poll-note>Updates every {{ auth()->user()?->isAdmin() ? '30s' : (auth()->check() ? '45s' : '60s') }}</span>
    </div>
</div>
