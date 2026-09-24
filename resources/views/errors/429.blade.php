<x-layouts.error code="429" title="Slow down a little.">
    Too many requests in a short time. Please wait a moment and retry — guests are limited to 10/min and 50/day.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in for higher limits</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
