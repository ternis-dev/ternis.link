@props(['title' => 'Dashboard'])

@php
    $host = \App\Support\PublicDashboard::label();
    $prefix = \App\Support\PublicDashboard::routePrefix();

    /*
     * my.href.nz has its own shell: a persistent sidebar instead of the
     * top bar of my.ternis.link. Same data, same routes — a different
     * way around it.
     */
    $nav = [
        ['label' => 'Overview', 'href' => \App\Support\PublicDashboard::homeUrl(), 'match' => [rtrim($prefix, '.')], 'icon' => '<path d="M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z"/>'],
        ['label' => 'Links', 'href' => \App\Support\PublicDashboard::route('links'), 'match' => [$prefix.'links*'], 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
        ['label' => 'Groups', 'href' => \App\Support\PublicDashboard::route('groups'), 'match' => [$prefix.'groups*'], 'icon' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>'],
        ['label' => 'Insights', 'href' => \App\Support\PublicDashboard::route('insights'), 'match' => [$prefix.'insights*'], 'icon' => '<path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 5-6"/>'],
        ['label' => 'API keys', 'href' => \App\Support\PublicDashboard::route('api-keys'), 'match' => [$prefix.'api-keys*'], 'icon' => '<circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/>'],
        ['label' => 'Domains', 'href' => \App\Support\PublicDashboard::route('domains'), 'match' => [$prefix.'domains*'], 'icon' => '<path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/>'],
        ['label' => 'Notifications', 'href' => \App\Support\PublicDashboard::route('notifications'), 'match' => [$prefix.'notifications*'], 'badge' => 'unread', 'icon' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>'],
        ['label' => 'Settings', 'href' => \App\Support\PublicDashboard::route('settings'), 'match' => [$prefix.'settings*'], 'icon' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>'],
    ];

    $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp

<x-layouts.app :title="$title" maxWidth="w-full">
    <x-slot:head>
        @vite(['resources/css/hnz-dashboard.css'])
    </x-slot:head>

    <div class="hnz hnz-root min-h-screen" x-data="{ nav: false }">
        <div class="mx-auto flex w-full max-w-[1600px]">
            {{-- Sidebar --}}
            <aside
                class="hnz-sidebar fixed inset-y-0 left-0 z-50 w-72 shrink-0 -translate-x-full border-r border-neutral-200/80 bg-white/95 backdrop-blur-xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 dark:border-white/8 dark:bg-neutral-950/95"
                :class="nav ? 'translate-x-0' : '-translate-x-full'"
                aria-label="Dashboard"
            >
                <div class="flex h-full flex-col gap-6 overflow-y-auto p-5">
                    <div class="flex items-center justify-between">
                        <a href="{{ \App\Support\PublicDashboard::homeUrl() }}" class="flex items-center gap-2.5 font-display text-lg font-bold tracking-tight">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-sm shadow-violet-600/30" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M9.5 14.5 14.5 9.5"/><path d="M11 6.5 12.5 5a4 4 0 0 1 5.66 5.66L16.5 12.2"/><path d="M13 17.5 11.5 19A4 4 0 0 1 5.84 13.34L7.5 11.8"/></svg>
                            </span>
                            <span>{{ $host }}</span>
                        </a>
                        <button
                            type="button"
                            @click="nav = false"
                            class="cursor-pointer rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 lg:hidden dark:hover:bg-white/5"
                            aria-label="Close navigation"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    <x-hnz.button
                        type="button"
                        x-data
                        @click="nav = false; $dispatch('open-link-creator')"
                        variant="primary"
                        class="w-full"
                        icon="<svg class='h-4 w-4' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2' stroke-linecap='round'><path d='M12 5v14M5 12h14'/></svg>"
                    >
                        New short link
                    </x-hnz.button>

                    <nav class="flex flex-1 flex-col gap-1">
                        @foreach ($nav as $item)
                            @php($isActive = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
                            <a
                                href="{{ $item['href'] }}"
                                @class([
                                    'hnz-nav-link group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all',
                                    'bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-sm shadow-violet-600/25' => $isActive,
                                    'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $isActive,
                                ])
                                @if ($isActive) aria-current="page" @endif
                            >
                                <svg class="h-4.5 w-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                <span>{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? null) === 'unread' && $unreadCount > 0)
                                    <span class="ml-auto rounded-lg bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold leading-none text-violet-700 dark:bg-violet-400/20 dark:text-violet-200">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    <div class="border-t border-neutral-200/80 pt-4 dark:border-white/8">
                        <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z"/></svg>
                            Everything else
                        </a>

                        @auth
                            <div x-data="{ open: false }" @click.away="open = false" class="relative mt-2">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-white/5"
                                    aria-haspopup="menu"
                                >
                                    <img src="{{ auth()->user()->avatarUrl(32) }}" alt="" class="h-7 w-7 rounded-lg object-cover">
                                    <span class="min-w-0 flex-1 truncate text-left">{{ auth()->user()->name }}</span>
                                    <svg class="h-3.5 w-3.5 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity
                                    role="menu"
                                    class="absolute bottom-full left-0 z-40 mb-2 w-60 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-1.5 shadow-xl dark:border-white/10 dark:bg-neutral-900"
                                >
                                    <div class="border-b border-neutral-100 px-3 py-2.5 dark:border-white/8">
                                        <p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p>
                                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $host }}</p>
                                    </div>
                                    <nav class="pt-1" aria-label="Account">
                                        <form method="POST" action="{{ url('/logout') }}">
                                            @csrf
                                            <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center rounded-xl px-3 py-2 text-sm font-medium text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-white/5">Log out</button>
                                        </form>
                                    </nav>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </aside>

            {{-- Mobile scrim --}}
            <div
                x-show="nav"
                x-cloak
                x-transition.opacity
                @click="nav = false"
                class="fixed inset-0 z-40 bg-neutral-950/50 lg:hidden"
                aria-hidden="true"
            ></div>

            <div class="min-w-0 flex-1">
                {{-- Mobile top bar --}}
                <div class="hnz-mobile-bar sticky top-0 z-30 flex items-center gap-3 border-b border-neutral-200/80 bg-white/90 px-4 py-3 backdrop-blur-xl lg:hidden dark:border-white/8 dark:bg-neutral-950/90">
                    <button
                        type="button"
                        @click="nav = true"
                        class="cursor-pointer rounded-xl border border-neutral-200 p-2 text-neutral-600 transition hover:bg-neutral-100 dark:border-white/10 dark:text-neutral-300 dark:hover:bg-white/5"
                        aria-label="Open navigation"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ \App\Support\PublicDashboard::homeUrl() }}" class="font-display text-base font-bold tracking-tight">{{ $host }}</a>
                    <button
                        type="button"
                        data-theme-toggle
                        class="theme-toggle ml-auto inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-neutral-200 text-neutral-600 transition hover:bg-neutral-100 dark:border-white/10 dark:text-neutral-400 dark:hover:bg-white/5"
                        aria-label="Toggle color theme"
                    >
                        <svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                        <svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                    </button>
                </div>

                <main class="px-4 py-7 sm:px-6 lg:px-10 lg:py-10">
                    {{ $slot }}
                </main>

                <footer class="px-4 pb-10 sm:px-6 lg:px-10">
                    <p class="text-center text-xs text-neutral-400 dark:text-neutral-500">
                        {{ $host }} — your href.nz, meinlink.at &amp; href.yt links.
                        Everything else lives on
                        <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="font-semibold underline underline-offset-2">dash.ternis.link</a>.
                    </p>
                </footer>
            </div>
        </div>

        @unless (request()->routeIs($prefix.'links.create', $prefix.'new'))
            <x-ui.modal name="link-creator" title="New Short Link">
                <livewire:dashboard.link-form :modal="true" scope="public" theme="public" />
            </x-ui.modal>

            <div
                x-data
                x-on:keydown.window="if ($event.key.toLowerCase() === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); $dispatch('open-link-creator'); }"
            ></div>
        @endunless
    </div>
</x-layouts.app>
