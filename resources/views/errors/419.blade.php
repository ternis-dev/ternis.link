@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="419" title="Session abgelaufen.">
    Deine Session ist abgelaufen oder das Formular ist veraltet. Lad bitte neu und versuch es erneut — oder log dich frisch ein.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Startseite neu laden</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Einloggen</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@elseif (\App\Support\PublicHost::isYt())
<x-layouts.public-error-yt code="419" title="Session expired.">
    Your session timed out or the form expired while waiting. Please reload and try again.
    <x-slot:actions>
        <a href="{{ url('/') }}" class="yt-play-cta">
            <span>Reload href.yt</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="yt-ghost">
            <span>Sign in again</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </x-slot:actions>
</x-layouts.public-error-yt>
@else
<x-layouts.error code="419" title="Session expired.">
    Your session timed out or the form expired. Please reload and try again — or sign in fresh.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Reload home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Sign in</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
