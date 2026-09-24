<x-layouts.error code="403" title="You don’t have access here.">
    {{ $exception->getMessage() ?: 'This area requires a different role or login. Sign in with Ternis Auth to continue.' }}
    <x-slot:actions>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary">Sign in</x-ui.button>
        <x-ui.button href="{{ url('/') }}">Back home</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
