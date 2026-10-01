<x-layouts.dashboard title="Domains — ternis.link">
    <x-ui.page-header
        title="Domains"
        subtitle="Register your own hostnames and verify ownership via DNS."
    />

    <livewire:dashboard.domain-manager />

    <x-ui.card title="Link-in-bio" class="mt-6">
        <p class="text-sm text-neutral-500">A verified domain can host a bio page with sub-pages and per-button analytics instead of just redirects.</p>
        <x-ui.button href="{{ route('dashboard.bio') }}" variant="secondary" class="mt-3">Open the page-builder</x-ui.button>
    </x-ui.card>
</x-layouts.dashboard>
