<x-layouts.dashboard title="Dashboard — ternis.link">
    <x-ui.page-header title="Dashboard">
        <x-slot:subtitle>
            Welcome back, {{ auth()->user()->name }} (Plan: <strong>{{ auth()->user()->plan?->name ?? 'free' }}</strong>)
            @if (auth()->user()?->isAdmin())
                · <strong>Admin view: stats across ALL links</strong>
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.links.create') }}" variant="primary">+ Create Link</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :value="number_format($stats['total_links'])" label="Total Links" />
        <x-ui.stat :value="number_format($stats['total_clicks'])" label="Total Clicks" />
        <x-ui.stat :value="number_format($stats['links_this_month'])" label="Links This Month" />
        <x-ui.stat :value="number_format($stats['clicks_today'])" label="Clicks Today" />
    </div>

    <x-ui.card title="Recent Links">
        <div class="mb-4 flex justify-end">
            <x-ui.button href="{{ route('dashboard.links') }}" size="sm">View All</x-ui.button>
        </div>
        <livewire:dashboard.link-table />
    </x-ui.card>
</x-layouts.dashboard>
