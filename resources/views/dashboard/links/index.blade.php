<x-layouts.dashboard title="Links — ternis.link">
    <x-ui.page-header title="Your Links">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.links.import') }}" size="sm" variant="secondary">Import CSV</x-ui.button>
            <x-ui.button href="{{ route('dashboard.links.export-all') }}" size="sm" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button href="{{ route('dashboard.links.qr-zip') }}" size="sm" variant="secondary">QR ZIP</x-ui.button>
            <x-ui.button variant="primary" x-data @click="$dispatch('open-link-creator')">+ Create Link</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (($publicCount ?? 0) > 0)
        <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-900 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-200">
            {{ number_format($publicCount) }} of your links live on the public dashboard —
            <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="font-bold underline underline-offset-2">open my.ternis.link →</a>
        </div>
    @endif

    <livewire:dashboard.link-table scope="personal" />
</x-layouts.dashboard>
