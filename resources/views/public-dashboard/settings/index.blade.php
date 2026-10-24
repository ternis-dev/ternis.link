<x-layouts.public-dashboard title="Settings — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Settings"
        subtitle="Your account preferences, visual theme, and privacy configurations."
    >
        <x-slot:actions>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links') }}" variant="secondary" size="sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
                Back to links
            </x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <livewire:dashboard.settings-form surface="public" />

    <div class="mt-8">
        <livewire:dashboard.privacy-settings theme="public" />
    </div>
</x-layouts.public-dashboard>
