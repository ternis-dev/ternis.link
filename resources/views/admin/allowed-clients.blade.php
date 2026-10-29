<x-layouts.admin title="Allowed Pairs & Rate Limits — ternis.link">
    <x-ui.page-header
        title="Allowed Pairs & Rate Limits"
        subtitle="Manage allowed User-Agent and IP pairs (or standalone IPs/User-Agents) that bypass API rate limits and guest link quotas."
        :backHref="route('admin.dashboard')"
        backLabel="Back to Admin Overview"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.errors')" variant="secondary" size="sm">
                View Error Encounters
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <livewire:admin.allowed-client-moderation />
    </x-ui.card>
</x-layouts.admin>
