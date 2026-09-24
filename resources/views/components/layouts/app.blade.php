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
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="/" class="font-display text-xl font-bold tracking-tight">ternis<span class="text-neutral-400 dark:text-neutral-500">.link</span></a>
            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M17.25 14.2A7.5 7.5 0 0 1 5.8 2.75a.75.75 0 0 0-1-1A9 9 0 1 0 18.25 15.2a.75.75 0 0 0-1-1Z"/></svg>
                    <svg class="icon-sun h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2Zm0 13a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm6.28-8.72a.75.75 0 0 1 0 1.06l-1.06 1.06a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM3.78 6.28a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06L3.78 7.34a.75.75 0 0 1 0-1.06Zm11.44 7.44a.75.75 0 1 1-1.06 1.06l-1.06-1.06a.75.75 0 1 1 1.06-1.06l1.06 1.06ZM4.84 13.22a.75.75 0 0 1 0 1.06l-1.06 1.06a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM10 15.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V16a.75.75 0 0 1 .75-.75ZM16.22 3.78a.75.75 0 0 1 0 1.06L15.16 5.9a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM5.9 4.84a.75.75 0 0 1-1.06 1.06L3.78 4.84a.75.75 0 1 1 1.06-1.06l1.06 1.06Z" clip-rule="evenodd"/></svg>
                </button>
                @auth
                    <img src="{{ auth()->user()->avatarUrl(34) }}" alt="{{ auth()->user()->name }}" class="h-8 w-8 rounded-full border border-neutral-300 object-cover dark:border-neutral-700">
                    <span class="hidden text-sm font-medium md:inline">{{ auth()->user()->name }}</span>
                    <x-ui.badge>{{ auth()->user()->role->value ?? auth()->user()->role }}</x-ui.badge>
                    <x-ui.button href="{{ url('/dashboard') }}" size="sm">Dashboard</x-ui.button>
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

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">
            ternis.link — by <a href="https://ternis.dev" class="underline underline-offset-2">ternis.dev</a> · hosted on <a href="https://ternis.net" class="underline underline-offset-2">ternis.net</a>
        </p>
    </footer>

    @livewireScripts
</body>
</html>
