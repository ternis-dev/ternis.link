@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="429" title="Bisschen langsamer.">
    Zu viele Anfragen in kurzer Zeit. Wart bitte einen Moment und versuch es erneut — Gäste sind auf 10/Min und 50/Tag begrenzt.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Zur Startseite</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Einloggen für höhere Limits</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@else
<x-layouts.error code="429" title="Slow down a little.">
    Too many requests in a short time. Please wait a moment and retry — guests are limited to 10/min and 50/day.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in for higher limits</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
