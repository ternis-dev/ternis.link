@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="403" title="Hier hast du keinen Zugriff.">
    {{ $exception->getMessage() ?: 'Dieser Bereich braucht eine andere Rolle oder einen Login. Meld dich mit Ternis Auth an.' }}
    <x-slot:actions>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary">Einloggen</x-ui.button>
        <x-ui.button href="{{ url('/') }}">Zur Startseite</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@else
<x-layouts.error code="403" title="You don’t have access here.">
    {{ $exception->getMessage() ?: 'This area requires a different role or login. Sign in with Ternis Auth to continue.' }}
    <x-slot:actions>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary">Sign in</x-ui.button>
        <x-ui.button href="{{ url('/') }}">Back home</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
