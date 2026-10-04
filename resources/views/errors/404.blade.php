@if (\App\Support\DomainUrls::isInternal())
<x-layouts.error code="404" title="Internal link not found.">
    The internal application link, gateway route, or resource on <strong>int.ternis.link</strong> was not found. It may have been relocated, expired, or typed incorrectly.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Internal Gateway</x-ui.button>
        <x-ui.button href="{{ url('/imprint') }}">Imprint Gateway</x-ui.button>
        <x-ui.button href="https://ternis.link">ternis.link</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@elseif (\App\Support\PublicHost::isMeinlink())
<x-layouts.error code="404" title="Link nicht gefunden.">
    Der Kurzlink, die Subdomain oder Seite wurde nicht gefunden. Vielleicht wurde sie deaktiviert, ist abgelaufen oder vertippt.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Zur Startseite</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@elseif (\App\Support\PublicHost::isYt())
<x-layouts.public-error-yt code="404" title="Video or link not found.">
    The video redirect, creator short link, or requested route wasn’t found on <strong>href.yt</strong>.
    <x-slot:actions>
        <a href="/" class="yt-btn-primary inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-semibold">
            Back to href.yt
        </a>
        <a href="/new" class="yt-btn-secondary inline-flex items-center rounded-xl px-5 py-2.5 text-xs font-semibold">
            Accelerate New Link
        </a>
    </x-slot:actions>
</x-layouts.public-error-yt>
@else
<x-layouts.error code="404" title="This link doesn’t exist (anymore).">
    The short link, subdomain or page you requested wasn’t found. It may have been deactivated, expired, or typed incorrectly.
    <x-slot:actions>
        <x-ui.button href="{{ url('/') }}" variant="primary">Back home</x-ui.button>
        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}">Dashboard</x-ui.button>
    </x-slot:actions>
</x-layouts.error>
@endif
