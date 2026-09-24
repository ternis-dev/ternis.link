<x-layouts.app title="ternis.link — Personal short links for family & partners">
    <div class="mx-auto flex max-w-4xl flex-col items-center px-4 py-20 text-center sm:px-6">
        <x-ui.badge tone="solid" class="mb-6">For family &amp; partners</x-ui.badge>
        <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl">Short links, <span class="text-neutral-400 dark:text-neutral-500">on your own name.</span></h1>
        <p class="mt-4 max-w-2xl text-lg text-neutral-500 dark:text-neutral-400">
            Claim your personal <code>{name}.ternis.link</code> subdomain, create branded short links with
            custom slugs, and see exactly how they perform — no DNS setup, no fuss.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg">Go to Dashboard</x-ui.button>
            @else
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">Get Started with Ternis Auth</x-ui.button>
            @endauth
            <x-ui.button href="#how-it-works" variant="secondary" size="lg">How it works</x-ui.button>
        </div>

        <div class="mt-16 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.card>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.9 5.7 3.9 9s-1.4 6.4-3.9 9c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/></svg>
                <div class="font-display mt-3 text-lg font-bold">Personal subdomain</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Your own <code>{name}.ternis.link</code>, verified instantly — one per account, ready for links right away.</p>
            </x-ui.card>
            <x-ui.card>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12V4.5A1 1 0 0 1 4.5 3.5H12L20.5 12 12 20.5Z"/><circle cx="8" cy="8" r="1.2" fill="currentColor" stroke="none"/></svg>
                <div class="font-display mt-3 text-lg font-bold">Custom slugs</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Memorable codes in your own words — or pick the auto-generated length, from 3 to 64 characters.</p>
            </x-ui.card>
            <x-ui.card>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 6H21M8.5 12H21M8.5 18H21"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor" stroke="none"/></svg>
                <div class="font-display mt-3 text-lg font-bold">Descriptions &amp; tags</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Note what each link is for and organize with tags, so every short link stays findable.</p>
            </x-ui.card>
            <x-ui.card>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16"/><path d="M7 20v-6M12 20V6M17 20v-9"/></svg>
                <div class="font-display mt-3 text-lg font-bold">Click analytics</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Referrers, countries and per-day charts per link, with CSV export for deeper digging.</p>
            </x-ui.card>
            <x-ui.card>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/></svg>
                <div class="font-display mt-3 text-lg font-bold">API access</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Create and manage links programmatically with scoped API keys and a versioned REST API.</p>
            </x-ui.card>
            <x-ui.card>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                <div class="font-display mt-3 text-lg font-bold">Custom domains</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Bring your own hostname on eligible plans — verified by DNS and ready in minutes.</p>
            </x-ui.card>
        </div>

        <div id="how-it-works" class="mt-16 w-full scroll-mt-8 text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight">How it works</h2>
            <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">1</span>
                    <p class="mt-3 font-medium">Sign in with Ternis Auth</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">One account across the family of services — no new password.</p>
                </li>
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">2</span>
                    <p class="mt-3 font-medium">Claim your subdomain</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Pick your <code>{name}.ternis.link</code> on the Domains page — verified instantly.</p>
                </li>
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">3</span>
                    <p class="mt-3 font-medium">Share &amp; measure</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Create branded links, tag them, and watch the analytics roll in.</p>
                </li>
            </ol>
        </div>

        <div class="mt-12 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2">
            <x-ui.card>
                <div class="font-display text-lg font-bold">Family</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Your corner of ternis.link — personal subdomains, generous quotas and full analytics for the whole household.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">Partners</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Branded short links on infrastructure you can trust, with API access for your own tooling.</p>
            </x-ui.card>
        </div>

        <p class="mt-10 text-sm text-neutral-500 dark:text-neutral-400">
            Just need one quick link with no account? <a href="https://href.nz" class="font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Use href.nz</a>
        </p>
    </div>
</x-layouts.app>
