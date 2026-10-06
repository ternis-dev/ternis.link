@props(['title' => 'ternis.link', 'maxWidth' => 'max-w-6xl', 'head' => null])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ternis.link' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    {!! $head !!}
    <script>
        // Paint order: explicit browser toggle > account preference > OS.
        window.tlThemeDefault = @json(auth()->user()?->theme ?? 'system');
        try {
            const stored = localStorage.getItem('tl-theme');
            const server = window.tlThemeDefault !== 'system' ? window.tlThemeDefault : null;
            const want = stored || server || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            document.documentElement.classList.toggle('dark', want !== 'light');
        } catch (e) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:rounded-lg focus:border focus:border-neutral-900 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-neutral-900">Skip to content</a>
    <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
        <div class="mx-auto flex {{ $maxWidth }} items-center justify-between gap-4 px-4 py-3 sm:px-6">
            @if (\App\Support\DomainUrls::isInternal())
                <div class="flex items-center gap-2.5">
                    <x-ui.logo prefix="int" />
                    <x-ui.badge tone="solid" class="hidden sm:inline-flex">Internal Gateway</x-ui.badge>
                </div>
            @else
                <x-ui.logo />
            @endif
            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
                @auth
                    <div x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false" class="relative">
                        <button
                            type="button"
                            @click="open = !open"
                            aria-haspopup="menu"
                            :aria-expanded="open.toString()"
                            aria-label="Account menu for {{ auth()->user()->name }}"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-neutral-300 py-1 pr-3 pl-1 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-900"
                        >
                            <img src="{{ auth()->user()->avatarUrl(34) }}" alt="" class="h-7 w-7 rounded-full object-cover">
                            <span class="hidden max-w-32 truncate text-sm font-medium md:inline">{{ auth()->user()->name }}</span>
                            <svg class="h-3.5 w-3.5 text-neutral-500 dark:text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div
                            x-show="open"
                            x-cloak
                            x-transition.opacity
                            role="menu"
                            aria-label="Account"
                            class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
                        >
                            <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                                <p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p>
                                <p class="mt-0.5 flex items-center gap-2">
                                    <x-ui.badge>{{ auth()->user()->role->value ?? auth()->user()->role }}</x-ui.badge>
                                </p>
                            </div>
                            <nav class="p-1.5" aria-label="Account sections">
                                <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">My links</a>
                                <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Dashboard</a>
                                <a href="{{ \App\Support\DomainUrls::dashboard('/settings') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Account settings</a>
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ \App\Support\DomainUrls::admin('/') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Admin console</a>
                                @endif
                            </nav>
                            <div class="border-t border-neutral-200 p-1.5 dark:border-neutral-800">
                                <form method="POST" action="{{ url('/logout') }}">
                                    @csrf
                                    <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Log out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <x-ui.button href="{{ url('/login') }}" size="sm" variant="primary">Login with Ternis Auth</x-ui.button>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto w-full {{ $maxWidth }} flex-1 px-4 py-8 sm:px-6" id="content">
        {{ $slot }}
    </main>

    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">
            @if (\App\Support\DomainUrls::isInternal())
                int.ternis.link — internal routing by <a href="https://ternis.dev" class="underline underline-offset-2">ternis.dev</a>
                ·
                <a href="https://ternis.link" class="underline underline-offset-2">ternis.link</a>
                ·
                <a href="{{ url('/imprint') }}" class="underline underline-offset-2">Imprint</a>
            @else
                ternis.link — by <a href="https://ternis.dev" class="underline underline-offset-2">ternis.dev</a> · hosted on <a href="https://ternis.net" class="underline underline-offset-2">ternis.net</a>
                ·
                <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2">Privacy</a>
                ·
                <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2">Terms</a>
                ·
                <a href="{{ \App\Support\DomainUrls::impressum() }}" class="underline underline-offset-2">Imprint</a>
            @endif
        </p>
    </footer>

    @livewireScripts
</body>
</html>
