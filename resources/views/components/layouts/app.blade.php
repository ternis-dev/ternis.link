@props(['title' => 'ternis.link', 'maxWidth' => 'max-w-6xl'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ternis.link' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
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
    <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
        <div class="mx-auto flex {{ $maxWidth }} items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="/" class="font-display text-xl font-bold tracking-tight">ternis<span class="text-neutral-400 dark:text-neutral-500">.link</span></a>
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
                    <img src="{{ auth()->user()->avatarUrl(34) }}" alt="{{ auth()->user()->name }}" class="h-8 w-8 rounded-full border border-neutral-300 object-cover dark:border-neutral-700">
                    <span class="hidden text-sm font-medium md:inline">{{ auth()->user()->name }}</span>
                    <x-ui.badge>{{ auth()->user()->role->value ?? auth()->user()->role }}</x-ui.badge>
                    <x-ui.button href="{{ in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1', 'testserver'], true) ? url('/dashboard') : \App\Support\DomainUrls::dashboard('/') }}" size="sm">Dashboard</x-ui.button>
                    @if (auth()->user()->isAdmin())
                        <x-ui.button href="{{ \App\Support\DomainUrls::admin('/') }}" size="sm" variant="ghost">Admin</x-ui.button>
                    @endif
                    <form method="POST" action="{{ url('/logout') }}" class="inline">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" size="sm">Logout</x-ui.button>
                    </form>
                @else
                    <x-ui.button href="{{ url('/login') }}" size="sm" variant="primary">Login with Ternis Auth</x-ui.button>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto w-full {{ $maxWidth }} flex-1 px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">
            ternis.link — by <a href="https://ternis.dev" class="underline underline-offset-2">ternis.dev</a> · hosted on <a href="https://ternis.net" class="underline underline-offset-2">ternis.net</a>
            ·
            <a href="https://ternis.link/legal/privacy" class="underline underline-offset-2">Privacy</a>
            ·
            <a href="https://ternis.link/legal/terms" class="underline underline-offset-2">Terms</a>
            ·
            <a href="https://ternis.dev/en/legal/imprint" class="underline underline-offset-2">Imprint</a>
        </p>
    </footer>

    @livewireScripts
</body>
</html>
