<x-layouts.public-dashboard title="Settings — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Settings"
        subtitle="Your account, your theme and your notification preferences. Changes apply everywhere."
    >
        <x-slot:actions>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">Back to links</x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <livewire:dashboard.settings-form surface="public" />

    <div class="mt-6">
        <livewire:dashboard.privacy-settings theme="public" />
    </div>
</x-layouts.public-dashboard>
