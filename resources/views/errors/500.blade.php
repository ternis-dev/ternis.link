@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="500" title="Bei uns ist was kaputt.">
    Ein unerwarteter Fehler ist aufgetreten. Das Team wurde über die Logs informiert — versuch es bitte gleich nochmal.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Zur Startseite</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@else
<x-layouts.error code="500" title="Something broke on our side.">
    An unexpected error occurred. The team has been notified via logs — please try again in a moment.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
