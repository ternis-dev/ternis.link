@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="500" title="Bei uns ist was kaputt.">
    Ein unerwarteter Fehler ist aufgetreten. Das Team wurde über die Logs informiert — versuch es bitte gleich nochmal.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Zur Startseite</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@elseif (\App\Support\PublicHost::isYt())
<x-layouts.public-error-yt code="500" title="Something broke on our side.">
    An unexpected error occurred. The team has been notified via logs — please try again in a moment.
    <x-slot:actions>
        <a href="{{ url('/') }}" class="yt-play-cta">
            <span>Back to href.yt</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="yt-ghost">
            <span>Dashboard</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </x-slot:actions>
</x-layouts.public-error-yt>
@else
<x-layouts.error code="500" title="Something broke on our side.">
    An unexpected error occurred. The team has been notified via logs — please try again in a moment.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
