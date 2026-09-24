<x-layouts.app title="ternis.link — URL Shortener & Insights">
    <div class="mx-auto flex max-w-2xl flex-col items-center py-20 text-center">
        <x-ui.badge tone="solid" class="mb-6">href.nz · href.re · ternis.link</x-ui.badge>
        <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl">ternis<span class="text-neutral-400 dark:text-neutral-500">.link</span></h1>
        <p class="mt-4 max-w-xl text-lg text-neutral-500 dark:text-neutral-400">Fast, high-performance link-shortening and insights platform with detailed analytics and referrer tracking.</p>
        <div class="mt-8">
            @auth
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" variant="primary" size="lg">Go to Dashboard</x-ui.button>
            @else
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">Get Started with Ternis Auth</x-ui.button>
            @endauth
        </div>
        <div class="mt-12 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-3">
            <x-ui.card>
                <div class="font-display text-lg font-bold">href.nz</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Public shortening. No account, 8-character links, 50/day fair use.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">href.re</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Official business links. Verified, trusted, analytics-backed.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">ternis.link</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Family &amp; partner domains with custom hostnames.</p>
            </x-ui.card>
        </div>
        <p class="mt-8 text-sm text-neutral-500 dark:text-neutral-400">
            Public shortening lives on <strong>href.nz</strong> · official business links on <strong>href.re</strong>
        </p>
    </div>
</x-layouts.app>
