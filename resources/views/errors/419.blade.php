<x-layouts.error code="419" title="Session expired.">
    Your session timed out or the form expired. Please reload and try again — or sign in fresh.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Reload home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Sign in</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
