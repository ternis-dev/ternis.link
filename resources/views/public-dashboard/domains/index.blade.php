<x-layouts.public-dashboard title="Domains — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Domains"
        subtitle="Use the built-in href.nz, meinlink.at and href.yt hostnames, or claim your own hostname and verify it via DNS."
    >
        <x-slot:actions>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">Back to links</x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <livewire:dashboard.domain-manager theme="public" />
</x-layouts.public-dashboard>
