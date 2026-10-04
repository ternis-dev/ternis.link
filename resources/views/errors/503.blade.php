@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="503" title="Kurz nicht verfügbar.">
    Wir warten gerade oder der Dienst startet hoch. Weiterleitungen und API sind gleich zurück.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Startseite erneut versuchen</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@elseif (\App\Support\PublicHost::isYt())
<x-layouts.public-error-yt code="503" title="Briefly unavailable.">
    We’re doing maintenance or starting up. Redirects and tools will be back in a few moments.
    <x-slot:actions>
        <a href="{{ url('/') }}" class="yt-play-cta">
            <span>Retry href.yt</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </x-slot:actions>
</x-layouts.public-error-yt>
@else
<x-layouts.error code="503" title="Briefly unavailable.">
    We’re doing maintenance or the service is starting up. Redirects and the API will be back shortly.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Retry home</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
