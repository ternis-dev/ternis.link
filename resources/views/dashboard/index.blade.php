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
        @if (($stats['total_links'] ?? 0) === 0 && ($publicCount ?? 0) === 0)
            <div class="mb-6 rounded-xl border border-dashed border-neutral-300 p-5 dark:border-neutral-700">
                <h3 class="font-display text-base font-bold">Get started in three steps</h3>
                <ol class="mt-3 space-y-2.5 text-sm">
                    <li class="flex items-start gap-2.5">
                        <span aria-hidden="true" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-[11px] font-bold text-white dark:bg-white dark:text-neutral-900">1</span>
                        <span><a href="{{ route('dashboard.links.create') }}" class="font-semibold underline underline-offset-2">Create your first link</a> <span class="text-neutral-500 dark:text-neutral-400">— pick a domain, set a destination, done.</span></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span aria-hidden="true" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-[11px] font-bold text-white dark:bg-white dark:text-neutral-900">2</span>
                        <span><a href="{{ route('dashboard.domains') }}" class="font-semibold underline underline-offset-2">Bring your own domain</a> <span class="text-neutral-500 dark:text-neutral-400">— verify by DNS, shorten on your hostname.</span></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span aria-hidden="true" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-[11px] font-bold text-white dark:bg-white dark:text-neutral-900">3</span>
                        <span><a href="{{ route('dashboard.api-keys') }}" class="font-semibold underline underline-offset-2">Automate with an API key</a> <span class="text-neutral-500 dark:text-neutral-400">— create links from your own tooling.</span></span>
                    </li>
                </ol>
            </div>
        @endif
        @if (($publicCount ?? 0) > 0)
            <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-900 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-200">
                {{ number_format($publicCount) }} of your links live on the public dashboard —
                <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="font-bold underline underline-offset-2">open my.ternis.link →</a>
            </div>
        @endif
        <div class="mb-4 flex justify-end">
            <x-ui.button href="{{ route('dashboard.links') }}" size="sm">View All</x-ui.button>
        </div>
        <livewire:dashboard.link-table scope="personal" />
    </x-ui.card>
</x-layouts.dashboard>
