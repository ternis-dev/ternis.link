@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="404" title="Link nicht gefunden.">
    Der Kurzlink, die Subdomain oder Seite wurde nicht gefunden. Vielleicht wurde sie deaktiviert, ist abgelaufen oder vertippt.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Zur Startseite</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@else
<x-layouts.error code="404" title="This link doesn’t exist (anymore).">
    The short link, subdomain or page you requested wasn’t found. It may have been deactivated, expired, or typed incorrectly.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
