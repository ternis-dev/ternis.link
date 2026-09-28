<x-layouts.app title="Browser Extension — ternis.link">
    <x-slot:head>
        <link rel="alternate" type="text/markdown" title="Browser Extension (Markdown)" href="{{ url('/pages/extension.md') }}">
    </x-slot:head>

    <div class="mx-auto max-w-3xl py-12">
        <x-ui.badge tone="solid" class="mb-4">Chrome Extension · v{{ $version }}</x-ui.badge>
        <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">Shorten any tab <span class="text-neutral-400 dark:text-neutral-500">in one click.</span></h1>
        <p class="mt-4 text-lg text-neutral-500 dark:text-neutral-400">
            The ternis.link extension shortens the current tab from the toolbar, the right-click menu,
            or the address bar (<code>tl</code> + <code>Space</code>). Guest mode works with no account;
            add an API key for custom slugs, your domains, and QR codes.
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
            @if ($downloadReady)
                <x-ui.button href="{{ $downloadUrl }}" variant="primary" size="lg">Download v{{ $version }}{{ $downloadSize ? ' ('.number_format($downloadSize / 1024, 1).' KB)' : '' }}</x-ui.button>
            @else
                <x-ui.button href="#" variant="primary" size="lg" disabled>Build pending — check back soon</x-ui.button>
            @endif
            <x-ui.button href="https://docs.ternis.link/extension" variant="secondary" size="lg">Read the docs</x-ui.button>
        </div>
        <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">
            Chrome Web Store listing is on the way — until then, install the zip below in Developer Mode (≈1 minute).
            Version endpoint: <a href="{{ url('/pages/extension/version') }}" class="underline underline-offset-2">version JSON</a>
        </p>

        <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card>
                <div class="font-display text-lg font-bold">Toolbar popup</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Shorten the active tab, pick a domain, set an optional custom slug, copy or open the QR.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">Right-click</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Shorten any page, link, or selected URL from the context menu — result lands in history.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">Omnibox</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Type <code>tl</code>, hit <code>Space</code>, paste a long URL, Enter — opens the short link.</p>
            </x-ui.card>
        </div>

        <h2 class="font-display mt-12 text-2xl font-bold tracking-tight">Install in 4 steps</h2>
        <ol class="mt-4 grid grid-cols-1 gap-4">
            <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                <p class="font-medium">1 · Download &amp; unzip</p>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    @if ($downloadReady)
                        <a href="{{ $downloadUrl }}" class="underline underline-offset-2">Download v{{ $version }}</a> and unzip it anywhere.
                    @else
                        The packaged zip is being built — grab <code>extension/</code> from the repo meanwhile.
                    @endif
                </p>
            </li>
            <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                <p class="font-medium">2 · Load unpacked</p>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Open <code>chrome://extensions</code>, enable <strong>Developer mode</strong>, choose <strong>Load unpacked</strong> → the unzipped folder.</p>
            </li>
            <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                <p class="font-medium">3 · Pin it (optional)</p>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Pin ternis.link to the toolbar so the popup is one click away.</p>
            </li>
            <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                <p class="font-medium">4 · Add an API key (optional, recommended)</p>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Without a key you get guest mode (<code>href.nz</code>, auto-generated codes, 50/day).
                    With a key from <a href="https://dash.ternis.link/api-keys" class="underline underline-offset-2">dash.ternis.link/api-keys</a>
                    (starts with <code>tl_</code>) you unlock custom slugs, your own domains, and your plan quotas.
                </p>
            </li>
        </ol>

        <h2 class="font-display mt-12 text-2xl font-bold tracking-tight">Permissions — why each one</h2>
        <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-900">
                    <tr><th class="px-4 py-2 font-semibold">Permission</th><th class="px-4 py-2 font-semibold">Why</th></tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                    <tr><td class="px-4 py-2"><code>storage</code></td><td class="px-4 py-2 text-neutral-500 dark:text-neutral-400">API key + history, on your device only.</td></tr>
                    <tr><td class="px-4 py-2"><code>activeTab</code></td><td class="px-4 py-2 text-neutral-500 dark:text-neutral-400">Read the current tab's URL when you click Shorten.</td></tr>
                    <tr><td class="px-4 py-2"><code>contextMenus</code></td><td class="px-4 py-2 text-neutral-500 dark:text-neutral-400">Right-click → Shorten with ternis.link.</td></tr>
                    <tr><td class="px-4 py-2"><code>notifications</code></td><td class="px-4 py-2 text-neutral-500 dark:text-neutral-400">Confirm the short link after a menu/omnibox shorten.</td></tr>
                    <tr><td class="px-4 py-2"><code>links.t-api.de</code></td><td class="px-4 py-2 text-neutral-500 dark:text-neutral-400">The API — keys are sent here and nowhere else.</td></tr>
                </tbody>
            </table>
        </div>

        <h2 class="font-display mt-12 text-2xl font-bold tracking-tight">FAQ</h2>
        <div class="mt-4 space-y-4 text-sm text-neutral-600 dark:text-neutral-300">
            <x-ui.card title="Is my API key safe?">
                <p>Yes — it lives in <code>chrome.storage.sync</code> on your devices and is only sent as a <code>Bearer</code> header to the API base you configure (default <code>https://links.t-api.de/v1</code>). Revoke it anytime at <a href="https://dash.ternis.link/api-keys" class="underline underline-offset-2">dash.ternis.link/api-keys</a>.</p>
            </x-ui.card>
            <x-ui.card title="Guest mode limits?">
                <p>Auto-generated 8-character codes on <code>href.nz</code>, 50 links/day per IP, no custom slugs — same rules as the public shortener. Sign in for more.</p>
            </x-ui.card>
            <x-ui.card title="Firefox / Edge / Safari?">
                <p>Manifest V3 also loads in Edge, Brave, and other Chromium browsers. Firefox support is planned — the API calls are already standard <code>fetch</code>.</p>
            </x-ui.card>
        </div>

        <p class="mt-10 text-sm text-neutral-500 dark:text-neutral-400">
            Source: <code>extension/</code> in the repo · Docs: <a href="https://docs.ternis.link/extension" class="underline underline-offset-2">docs.ternis.link/extension</a> · API: <a href="https://docs.ternis.link/links" class="underline underline-offset-2">links docs</a>
        </p>
    </div>
</x-layouts.app>
