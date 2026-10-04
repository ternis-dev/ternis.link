@if (\App\Support\DomainUrls::isInternal())
    <x-layouts.app title="Link Not Found — int.ternis.link">
        <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
            <x-ui.badge tone="solid" class="mb-4">Internal Gateway</x-ui.badge>
            <div class="font-display text-7xl font-bold tracking-tight">404</div>
            <h1 class="mt-3 text-xl font-semibold">Internal Link Not Found</h1>
            <p class="mt-2 text-neutral-500 dark:text-neutral-400">The internal application redirect <code>{{ $slug }}</code> on <code>{{ $domain ?? request()->getHost() }}</code> was not found, is inactive, or has been relocated.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <x-ui.button href="/" variant="primary">Return to int.ternis.link</x-ui.button>
                <x-ui.button href="/imprint" variant="secondary">Imprint Gateway</x-ui.button>
                <x-ui.button href="https://ternis.link" variant="secondary">ternis.link Home</x-ui.button>
            </div>
        </div>
    </x-layouts.app>
@elseif (request()->attributes->get('domain_type') === 'public')
    @if (\App\Support\PublicHost::isMeinlink())
        <x-layouts.public-error-meinlink code="404" title="Link nicht gefunden.">
            Der Kurzlink <strong>{{ $slug }}</strong> für die Domain <strong>{{ $domain ?? request()->getHost() }}</strong> wurde nicht gefunden, ist inaktiv oder abgelaufen.
            <x-slot:actions>
                <x-ui.button href="/" variant="primary">Zurück zum Kürzer</x-ui.button>
            </x-slot:actions>
        </x-layouts.public-error-meinlink>
    @elseif (\App\Support\PublicHost::isYt())
        <x-layouts.public-error-yt code="404" title="Video link not found.">
            The short link <strong>{{ $slug }}</strong> on <strong>{{ $domain ?? request()->getHost() }}</strong> was not found, is inactive, or has expired.
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
        <x-layouts.public-error code="404" title="Link not found.">
            The short link <strong>{{ $slug }}</strong> for the domain <strong>{{ $domain ?? request()->getHost() }}</strong> wasn’t found, is inactive, or has expired.
            <x-slot:actions>
                <x-ui.button href="/" variant="primary">Back to shortener</x-ui.button>
            </x-slot:actions>
        </x-layouts.public-error>
    @endif
@elseif (request()->attributes->get('domain_type') === 'business')
    <x-layouts.app title="Link Not Found — href.re">
        <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
            <div class="font-display text-7xl font-bold tracking-tight">404</div>
            <h1 class="mt-3 text-xl font-semibold">Business Link Not Found</h1>
            <p class="mt-2 text-neutral-500 dark:text-neutral-400">The business redirect <code>{{ $slug }}</code> on <code>{{ $domain ?? request()->getHost() }}</code> was not found, is inactive, or has expired.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <x-ui.button href="/" variant="primary">Return to href.re</x-ui.button>
                <x-ui.button href="https://href.nz" variant="secondary">Open href.nz</x-ui.button>
            </div>
        </div>
    </x-layouts.app>
@else
    <x-layouts.app title="Link Not Found — ternis.link">
        <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
            <div class="font-display text-7xl font-bold tracking-tight">404</div>
            <h1 class="mt-3 text-xl font-semibold">Link Not Found</h1>
            <p class="mt-2 text-neutral-500 dark:text-neutral-400">The short link <code>{{ $slug }}</code> for the domain <code>{{ $domain ?? request()->getHost() }}</code> was not found, is inactive, or has expired.</p>
            <x-ui.button href="/" class="mt-6">Return to Home</x-ui.button>
        </div>
    </x-layouts.app>
@endif
