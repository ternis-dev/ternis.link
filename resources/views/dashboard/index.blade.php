<x-layouts.dashboard title="Dashboard — ternis.link">
    <x-ui.page-header title="Dashboard">
        <x-slot:subtitle>
            Welcome back, {{ auth()->user()->name }} (Plan: <strong>{{ auth()->user()->plan?->name ?? 'free' }}</strong>)
        </x-slot:subtitle>
        <x-slot:actions>
            <x-ui.button variant="primary" x-data @click="$dispatch('open-link-creator')">+ Create Link</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :value="number_format($stats['total_links'])" label="Total Links" />
        <x-ui.stat :value="number_format($stats['total_clicks'])" label="Total Clicks" />
        <x-ui.stat :value="number_format($stats['links_this_month'])" label="Links This Month" />
        <x-ui.stat :value="number_format($stats['clicks_today'])" label="Clicks Today" />
    </div>

    <x-ui.card title="Recent Links">
        @if (($publicCount ?? 0) > 0)
            <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-900 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-200">
                {{ number_format($publicCount) }} of your links live on the public dashboard —
                <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="font-bold underline underline-offset-2">open my.href.nz →</a>
            </div>
        @endif
        <div class="mb-4 flex justify-end">
            <x-ui.button href="{{ route('dashboard.links') }}" size="sm">View All</x-ui.button>
        </div>
        <livewire:dashboard.link-table scope="personal" />
    </x-ui.card>
</x-layouts.dashboard>
