<x-layouts.hnz-dashboard title="API Keys — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        title="API keys"
        subtitle="Programmatic access to your href.nz, meinlink.at and href.yt links. The raw token is shown once — store it in your secret manager."
    >
        <x-slot:actions>
            <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">Back to links</x-hnz.button>
        </x-slot:actions>
    </x-hnz.head>

    <livewire:dashboard.api-key-manager theme="public" />
</x-layouts.hnz-dashboard>
