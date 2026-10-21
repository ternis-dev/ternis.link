<x-layouts.public-dashboard title="API Keys — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="API keys"
        subtitle="Programmatic access to your href.nz, meinlink.at and href.yt links. The raw token is shown once — store it in your secret manager."
    >
        <x-slot:actions>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">Back to links</x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <livewire:dashboard.api-key-manager theme="public" />
</x-layouts.public-dashboard>
