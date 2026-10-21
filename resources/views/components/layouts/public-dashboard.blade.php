@props(['title' => 'Dashboard'])

@php
    $host = \App\Support\PublicDashboard::label();

    // The route name prefix of the host being served: "public-dashboard."
    // on my.ternis.link, "public-dashboard-alt." on the alias host. Every
    // URL and every active-state pattern is built from it.
    $prefix = \App\Support\PublicDashboard::routePrefix();

    // Route prefixes differ per host (my.ternis.link vs my.href.nz), so
    // every link is built through the helper instead of route().
    $nav = [
        ['label' => 'Overview', 'href' => \App\Support\PublicDashboard::homeUrl(), 'match' => [rtrim($prefix, '.')], 'icon' => '<path d="M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z"/>'],
        ['label' => 'Links', 'href' => \App\Support\PublicDashboard::route('links'), 'match' => [$prefix.'links*'], 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
        ['label' => 'API keys', 'href' => \App\Support\PublicDashboard::route('api-keys'), 'match' => [$prefix.'api-keys*'], 'icon' => '<circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/>'],
        ['label' => 'Domains', 'href' => \App\Support\PublicDashboard::route('domains'), 'match' => [$prefix.'domains*'], 'icon' => '<path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/>'],
        ['label' => 'Settings', 'href' => \App\Support\PublicDashboard::route('settings'), 'match' => [$prefix.'settings*'], 'icon' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>'],
    ];

    // The per-key page belongs to the API keys section.
    $nav[2]['match'][] = $prefix.'api-keys.show';
@endphp

<x-layouts.app :title="$title" maxWidth="w-full">
    <x-slot:head>
        @vite(['resources/css/public-dashboard.css'])
    </x-slot:head>

    <x-slot:header>
        <header class="nd-shell-header sticky top-0 z-40 border-b border-neutral-200 bg-white/85 backdrop-blur-md dark:border-neutral-800 dark:bg-neutral-950/85">
            <div class="mx-auto flex w-full max-w-7xl items-center gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
                <a href="{{ \App\Support\PublicDashboard::homeUrl() }}" class="flex shrink-0 items-center gap-2 font-display text-base font-bold tracking-tight" aria-label="{{ $host }} home">
                    <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    <span class="hidden sm:inline">{{ $host }}</span>
                </a>

                <nav aria-label="Public dashboard" data-tour="nav" class="no-scrollbar -mx-1 flex min-w-0 flex-1 items-center gap-1 overflow-x-auto px-1">
                    @foreach ($nav as $item)
                        @php($isActive = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
                        <a
                            href="{{ $item['href'] }}"
                            @class([
                                'nd-pill flex shrink-0 items-center gap-1.5 px-3 py-1.5 text-sm font-semibold transition-colors',
                                'bg-emerald-600 text-white' => $isActive,
                                'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-white' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            <span class="hidden md:inline">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        data-theme-toggle
                        class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                        aria-label="Toggle color theme"
                    >
                        <svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                        <svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                    </button>

                    @unless (request()->routeIs($prefix.'links.create', $prefix.'new'))
                        <button
                            type="button"
                            x-data
                            @click="$dispatch('open-link-creator')"
                            title="Keyboard shortcut: C"
                            data-tour="create"
                            class="hidden cursor-pointer items-center gap-1.5 rounded-full bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-500 sm:inline-flex"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            New link
                        </button>
                    @endunless

                    @auth
                        <div x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false" class="relative">
                            <button
                                type="button"
                                @click="open = !open"
                                aria-haspopup="menu"
                                :aria-expanded="open.toString()"
                                aria-label="Account menu for {{ auth()->user()->name }}"
                                class="inline-flex cursor-pointer items-center rounded-full ring-1 ring-neutral-300 transition hover:ring-neutral-400 dark:ring-neutral-700 dark:hover:ring-neutral-500"
                            >
                                <img src="{{ auth()->user()->avatarUrl(32) }}" alt="" class="h-8 w-8 rounded-full object-cover">
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
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $host }}</p>
                                </div>
                                <nav class="p-1.5" aria-label="Account sections">
                                    <a href="{{ \App\Support\PublicDashboard::route('api-keys') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">API keys</a>
                                    <a href="{{ \App\Support\PublicDashboard::route('domains') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Domains</a>
                                    <a href="{{ \App\Support\PublicDashboard::route('settings') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Settings</a>
                                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Everything else → dash.ternis.link</a>
                                </nav>
                                <div class="border-t border-neutral-200 p-1.5 dark:border-neutral-800">
                                    <form method="POST" action="{{ url('/logout') }}">
                                        @csrf
                                        <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Log out</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </header>
    </x-slot:header>

    <div class="nd nd-root mx-auto w-full max-w-7xl">
        <section class="min-w-0">
            {{ $slot }}
        </section>

        <p class="mt-12 text-center text-xs text-neutral-500 dark:text-neutral-500">
            clicked.at, ternis.link and href.re links live on
            <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="font-semibold underline underline-offset-2">dash.ternis.link →</a>
        </p>
    </div>

    @unless (request()->routeIs($prefix.'links.create', $prefix.'new'))
        <div class="nd">
            <x-ui.modal name="link-creator" title="New Short Link">
                <livewire:dashboard.link-form :modal="true" scope="public" theme="public" />
            </x-ui.modal>
        </div>

        <div
            x-data
            x-on:keydown.window="if ($event.key.toLowerCase() === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); $dispatch('open-link-creator'); }"
        ></div>
    @endunless
</x-layouts.app>
