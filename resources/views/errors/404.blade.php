<x-layouts.error code="404" title="This link doesn’t exist (anymore).">
    The short link, subdomain or page you requested wasn’t found. It may have been deactivated, expired, or typed incorrectly.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
