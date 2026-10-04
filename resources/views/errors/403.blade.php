@if (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="403" title="Hier hast du keinen Zugriff.">
    {{ $exception->getMessage() ?: 'Dieser Bereich braucht eine andere Rolle oder einen Login. Meld dich mit Ternis Auth an.' }}
    <x-slot:actions>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary">Einloggen</x-ui.button>
        <x-ui.button href="{{ url('/') }}">Zur Startseite</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@elseif (\App\Support\PublicHost::isYt())
<x-layouts.public-error-yt code="403" title="Members only.">
    {{ $exception->getMessage() ?: 'This area requires creator sign-in. Authenticate via Ternis Auth to continue.' }}
    <x-slot:actions>
        <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="yt-play-cta">
            <span>Sign in with Ternis Auth</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="/" class="yt-ghost">
            <span>Back to href.yt</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </x-slot:actions>
</x-layouts.public-error-yt>
@else
<x-layouts.error code="403" title="You don’t have access here.">
    {{ $exception->getMessage() ?: 'This area requires a different role or login. Sign in with Ternis Auth to continue.' }}
    <x-slot:actions>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary">Sign in</x-ui.button>
        <x-ui.button href="{{ url('/') }}">Back home</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
