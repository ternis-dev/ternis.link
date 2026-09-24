<x-layouts.error code="503" title="Briefly unavailable.">
    We’re doing maintenance or the service is starting up. Redirects and the API will be back shortly.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Retry home</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
