@props(['title' => 'Dashboard', 'head' => null])

@php
    $host = \App\Support\PublicDashboard::label();
    $prefix = \App\Support\PublicDashboard::routePrefix();

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $host }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    {!! $head !!}
    <script>
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
    @vite(['resources/css/app.css', 'resources/css/hnz-dashboard.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col font-sans antialiased text-neutral-900 selection:bg-violet-500 selection:text-white dark:text-neutral-100">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:rounded-xl focus:border focus:border-violet-500 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-violet-900 shadow-xl">Skip to content</a>

    <div
        class="hnz hnz-root min-h-screen"
        x-data="{
            nav: false,
            shortcutsOpen: false,
            toast: { show: false, message: '', type: 'success' },
            showToast(msg, type = 'success') {
                this.toast.message = msg;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => this.toast.show = false, 2800);
            }
        }"
        @hnz-toast.window="showToast($event.detail.message, $event.detail.type || 'success')"
    >
        <div class="mx-auto flex w-full max-w-[1600px]">
            {{-- Persistent Desktop & Drawer Mobile Sidebar --}}
            <aside
                class="hnz-sidebar fixed inset-y-0 left-0 z-50 w-72 shrink-0 -translate-x-full border-r border-neutral-200/80 bg-white/95 backdrop-blur-xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 dark:border-white/8 dark:bg-neutral-950/95"
                :class="nav ? 'translate-x-0' : '-translate-x-full'"
                aria-label="Dashboard Navigation"
            >
                <div class="flex h-full flex-col gap-6 overflow-y-auto p-5">
                    {{-- Brand Mark --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ \App\Support\PublicDashboard::homeUrl() }}" class="flex items-center gap-2.5 font-display text-lg font-bold tracking-tight group">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 text-white shadow-md shadow-violet-600/30 transition-transform group-hover:scale-105" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M9.5 14.5 14.5 9.5"/><path d="M11 6.5 12.5 5a4 4 0 0 1 5.66 5.66L16.5 12.2"/><path d="M13 17.5 11.5 19A4 4 0 0 1 5.84 13.34L7.5 11.8"/></svg>
                            </span>
                            <div class="flex flex-col">
                                <span class="leading-none text-neutral-900 dark:text-white">{{ $host }}</span>
                                <span class="mt-0.5 text-[10px] font-bold tracking-widest text-violet-600 uppercase dark:text-violet-400">Public Links</span>
                            </div>
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

                    {{-- Quick Action Shorten Button --}}
                    <x-hnz.button
                        type="button"
                        x-data
                        @click="nav = false; $dispatch('open-link-creator')"
                        variant="primary"
                        class="w-full relative shadow-md shadow-violet-600/20 group"
                        icon="<svg class='h-4 w-4 transition-transform group-hover:rotate-90 duration-200' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2' stroke-linecap='round'><path d='M12 5v14M5 12h14'/></svg>"
                    >
                        <span>New short link</span>
                        <kbd class="ml-auto hidden rounded bg-white/20 px-1.5 py-0.5 text-[10px] font-medium tracking-wide sm:inline-block">C</kbd>
                    </x-hnz.button>

                    {{-- Navigation Links --}}
                    <nav class="flex flex-1 flex-col gap-1" aria-label="Main Navigation">
                        @foreach ($nav as $item)
                            @php($isActive = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
                            <a
                                href="{{ $item['href'] }}"
                                @class([
                                    'hnz-nav-link group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all',
                                    'bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-sm shadow-violet-600/25 ring-1 ring-white/20' => $isActive,
                                    'text-neutral-600 hover:bg-neutral-100/80 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $isActive,
                                ])
                                @if ($isActive) aria-current="page" @endif
                            >
                                <svg class="h-4.5 w-4.5 shrink-0 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                <span>{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? null) === 'unread' && $unreadCount > 0)
                                    <span class="ml-auto rounded-lg bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold leading-none text-violet-700 dark:bg-violet-400/20 dark:text-violet-200">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    {{-- Bottom Navigation & User Profile --}}
                    <div class="border-t border-neutral-200/80 pt-4 dark:border-white/8 space-y-2">
                        <div class="flex items-center justify-between px-1">
                            <button
                                type="button"
                                @click="shortcutsOpen = true"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-500 hover:text-violet-600 dark:text-neutral-400 dark:hover:text-violet-300 transition-colors"
                                title="View Keyboard Shortcuts"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <span>Shortcuts</span>
                                <kbd class="rounded border border-neutral-200 px-1 text-[10px] dark:border-white/10">?</kbd>
                            </button>

                            <button
                                type="button"
                                data-theme-toggle
                                class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-200 text-neutral-600 transition hover:bg-neutral-100 dark:border-white/10 dark:text-neutral-400 dark:hover:bg-white/5"
                                aria-label="Toggle color theme"
                            >
                                <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                                <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                            </button>
                        </div>

                        <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            <span>dash.ternis.link (Admin)</span>
                        </a>

                        @auth
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-xl p-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-white/5"
                                    aria-haspopup="menu"
                                >
                                    <img src="{{ auth()->user()->avatarUrl(32) }}" alt="" class="h-8 w-8 rounded-lg object-cover ring-1 ring-neutral-200 dark:ring-white/10">
                                    <div class="min-w-0 flex-1 text-left">
                                        <p class="truncate text-xs font-bold leading-tight">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-[10px] text-neutral-400 leading-tight">{{ auth()->user()->plan?->name ?? 'Free Tier' }}</p>
                                    </div>
                                    <svg class="h-3.5 w-3.5 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity
                                    role="menu"
                                    class="absolute bottom-full left-0 z-40 mb-2 w-64 overflow-hidden rounded-2xl border border-neutral-200 bg-white/95 p-1.5 shadow-2xl backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/95"
                                >
                                    <div class="border-b border-neutral-100 px-3 py-2.5 dark:border-white/8">
                                        <p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p>
                                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $host }}</p>
                                    </div>
                                    <nav class="pt-1" aria-label="Account">
                                        <a href="{{ \App\Support\PublicDashboard::route('settings') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white">Account Settings</a>
                                        <form method="POST" action="{{ url('/logout') }}">
                                            @csrf
                                            <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">Log out</button>
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
                class="fixed inset-0 z-40 bg-neutral-950/60 backdrop-blur-sm lg:hidden"
                aria-hidden="true"
            ></div>

            {{-- Main Content Column --}}
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
                    <a href="{{ \App\Support\PublicDashboard::homeUrl() }}" class="font-display text-base font-bold tracking-tight text-neutral-900 dark:text-white">{{ $host }}</a>
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

                {{-- Page Main Slot --}}
                <main id="content" class="px-4 py-7 sm:px-6 lg:px-10 lg:py-10">
                    {{ $slot }}
                </main>

                {{-- Footer --}}
                <footer class="border-t border-neutral-200/60 px-4 py-8 sm:px-6 lg:px-10 dark:border-white/5">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-neutral-400 dark:text-neutral-500">
                        <p>
                            <span class="font-semibold text-neutral-600 dark:text-neutral-400">{{ $host }}</span> — Consumer shortener dashboard for href.nz, meinlink.at &amp; href.yt.
                        </p>
                        <div class="flex items-center gap-4">
                            <button type="button" @click="shortcutsOpen = true" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                Shortcuts [?]
                            </button>
                            <a href="https://status.ternis.link" target="_blank" rel="noopener noreferrer" class="hover:underline">Network Status</a>
                            <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="hover:underline">dash.ternis.link</a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        {{-- Toast Banner Container --}}
        <div
            x-show="toast.show"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-2xl bg-neutral-900/90 px-4 py-3 text-sm font-semibold text-white shadow-2xl backdrop-blur-xl ring-1 ring-white/10 dark:bg-neutral-800/95"
            role="status"
        >
            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-neutral-900 text-xs font-bold">✓</span>
            <span x-text="toast.message"></span>
        </div>

        {{-- Shortcuts Modal --}}
        <div
            x-show="shortcutsOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/60 backdrop-blur-sm"
            @keydown.escape.window="shortcutsOpen = false"
        >
            <div
                @click.away="shortcutsOpen = false"
                class="w-full max-w-md rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-4 dark:border-white/10">
                    <h3 class="font-display text-lg font-bold">Keyboard Shortcuts</h3>
                    <button type="button" @click="shortcutsOpen = false" class="text-neutral-400 hover:text-neutral-700 dark:hover:text-white">✕</button>
                </div>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Create new short link</span>
                        <kbd class="rounded border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-xs font-mono font-semibold dark:border-white/10 dark:bg-white/5">C</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Toggle shortcuts cheat sheet</span>
                        <kbd class="rounded border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-xs font-mono font-semibold dark:border-white/10 dark:bg-white/5">?</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Close modal or drawer</span>
                        <kbd class="rounded border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-xs font-mono font-semibold dark:border-white/10 dark:bg-white/5">Esc</kbd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Global Link Creator Modal --}}
        @unless (request()->routeIs($prefix.'links.create', $prefix.'new'))
            <x-ui.modal name="link-creator" title="New Short Link">
                <livewire:dashboard.link-form :modal="true" scope="public" theme="public" />
            </x-ui.modal>

            <div
                x-data
                x-on:keydown.window="if ($event.key.toLowerCase() === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); $dispatch('open-link-creator'); } if ($event.key === '?' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); shortcutsOpen = !shortcutsOpen; }"
            ></div>
        @endunless
    </div>
    @livewireScripts
</body>
</html>
