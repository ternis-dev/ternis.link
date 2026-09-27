@if (request()->attributes->get('domain_type') === 'public')
    <x-layouts.public-error code="404" title="Link not found.">
        The short link <strong>{{ $slug }}</strong> for the domain <strong>{{ $domain ?? request()->getHost() }}</strong> wasn’t found, is inactive, or has expired.
        <x-slot:actions>
            <x-ui.button href="/" variant="primary">Back to shortener</x-ui.button>
        </x-slot:actions>
    </x-layouts.public-error>
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
