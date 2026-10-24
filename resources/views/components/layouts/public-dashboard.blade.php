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
        ['label' => 'Groups', 'href' => \App\Support\PublicDashboard::route('groups'), 'match' => [$prefix.'groups*'], 'icon' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>'],
        ['label' => 'Insights', 'href' => \App\Support\PublicDashboard::route('insights'), 'match' => [$prefix.'insights*'], 'icon' => '<path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 5-6"/>'],
        ['label' => 'API keys', 'href' => \App\Support\PublicDashboard::route('api-keys'), 'match' => [$prefix.'api-keys*'], 'icon' => '<circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/>'],
        ['label' => 'Domains', 'href' => \App\Support\PublicDashboard::route('domains'), 'match' => [$prefix.'domains*'], 'icon' => '<path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/>'],
        ['label' => 'Notifications', 'href' => \App\Support\PublicDashboard::route('notifications'), 'match' => [$prefix.'notifications*'], 'badge' => 'unread', 'icon' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>'],
        ['label' => 'Settings', 'href' => \App\Support\PublicDashboard::route('settings'), 'match' => [$prefix.'settings*'], 'icon' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>'],
    ];

    // The per-key page belongs to the API keys section.
    $nav[4]['match'][] = $prefix.'api-keys.show';

    // Unread notifications, for the badge on that nav entry.
    $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp

<x-layouts.app :title="$title" maxWidth="w-full">
    <x-slot:head>
        @vite(['resources/css/public-dashboard.css'])
    </x-slot:head>

    <x-slot:header>
        <header class="nd-shell-header sticky top-0 z-40">
            <div class="mx-auto flex w-full max-w-7xl items-center gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
                <a href="{{ \App\Support\PublicDashboard::homeUrl() }}" class="group flex shrink-0 items-center gap-2.5 font-display text-base font-bold tracking-tight text-slate-900 dark:text-white" aria-label="{{ $host }} home">
                    <span class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white shadow-md shadow-emerald-500/20 ring-1 ring-white/20 transition-transform group-hover:scale-105">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/></svg>
                        <span class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-white dark:ring-neutral-950"></span>
                    </span>
                    <span class="flex items-center gap-1.5 font-display text-base font-extrabold tracking-tight">
                        <span class="hidden sm:inline">{{ $host }}</span>
                        <span class="hidden lg:inline-flex items-center rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-emerald-600 uppercase ring-1 ring-emerald-500/20 dark:text-emerald-400">Hub</span>
                    </span>
                </a>

                <nav aria-label="Public dashboard" data-tour="nav" class="no-scrollbar -mx-1 flex min-w-0 flex-1 items-center gap-1 overflow-x-auto px-1">
                    @foreach ($nav as $item)
                        @php($isActive = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
                        <a
                            href="{{ $item['href'] }}"
                            @class([
                                'nd-pill flex shrink-0 items-center gap-1.5 px-3 py-1.5 text-sm font-semibold',
                                'text-neutral-500 hover:bg-neutral-100/80 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-900/80 dark:hover:text-white' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            <span class="hidden md:inline">{{ $item['label'] }}</span>
                            @if (($item['badge'] ?? null) === 'unread' && $unreadCount > 0)
                                <span class="relative flex h-2 w-2 md:hidden">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                                <span class="hidden rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold leading-none text-emerald-700 md:inline dark:bg-emerald-400/20 dark:text-emerald-300">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        data-theme-toggle
                        class="theme-toggle inline-flex h-8.5 w-8.5 cursor-pointer items-center justify-center rounded-xl border border-slate-200/90 text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-900"
                        aria-label="Toggle color theme"
                    >\n                        <svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>\n                        <svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>\n                    </button>

                    @unless (request()->routeIs($prefix.'links.create', $prefix.'new'))
                        <button
                            type="button"
                            x-data
                            @click="$dispatch('open-link-creator')"\n                            title="Keyboard shortcut: C"\n                            data-tour="create"\n                            class="hidden cursor-pointer items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm shadow-emerald-600/30 transition-all hover:from-emerald-500 hover:to-teal-500 hover:shadow-emerald-600/40 active:scale-[0.98] sm:inline-flex"\n                        >\n                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>\n                            New link\n                        </button>
                    @endunless

                    @auth
                        <div x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false" class="relative">
                            <button
                                type="button"
                                @click="open = !open"\n                                aria-haspopup="menu"\n                                :aria-expanded="open.toString()"\n                                aria-label="Account menu for {{ auth()->user()->name }}"\n                                class="inline-flex cursor-pointer items-center rounded-xl p-0.5 ring-1 ring-slate-200 transition hover:ring-emerald-500/50 dark:ring-slate-800 dark:hover:ring-emerald-400/50"\n                            >\n                                <img src="{{ auth()->user()->avatarUrl(32) }}" alt="" class="h-8 w-8 rounded-lg object-cover">\n                            </button>
                            <div
                                x-show="open"\n                                x-cloak\n                                x-transition.opacity\n                                role="menu"\n                                aria-label="Account"\n                                class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white/95 shadow-xl backdrop-blur-xl dark:border-slate-800 dark:bg-[#111720]/95"\n                            >\n                                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/80">\n                                    <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>\n                                    <p class="mt-0.5 flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">\n                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>\n                                        {{ $host }}\n                                    </p>\n                                </div>\n                                <nav class="p-1.5" aria-label="Account sections">\n                                    <a href="{{ \App\Support\PublicDashboard::route('links') }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">Links</a>\n                                    <a href="{{ \App\Support\PublicDashboard::route('groups') }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">Link groups</a>\n                                    <a href="{{ \App\Support\PublicDashboard::route('insights') }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">Insights</a>\n                                    <a href="{{ \App\Support\PublicDashboard::route('api-keys') }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">API keys</a>\n                                    <a href="{{ \App\Support\PublicDashboard::route('domains') }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">Domains</a>\n                                    <a href="{{ \App\Support\PublicDashboard::route('settings') }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">Settings</a>\n                                </nav>\n                                @if (\App\Support\PublicDashboard::hasLegacyUi())\n                                    <div class="border-t border-slate-100 p-1.5 dark:border-slate-800/80">\n                                        {{-- The previous indigo dashboard stays one click away. --}}\n                                        <form method="POST" action="{{ \App\Support\PublicDashboard::route('switch-legacy') }}">\n                                            @csrf\n                                            <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white">Use the previous UI</button>\n                                        </form>\n                                    </div>\n                                @endif\n                                <div class="border-t border-slate-100 p-1.5 dark:border-slate-800/80">\n                                    <form method="POST" action="{{ url('/logout') }}">\n                                        @csrf\n                                        <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30">Log out</button>\n                                    </form>\n                                </div>\n                            </div>\n                        </div>\n                    @endauth\n                </div>\n            </div>\n        </header>\n    </x-slot:header>\n\n    <div class="nd nd-root mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-8">\n        <section class="min-w-0">\n            {{ $slot }}\n        </section>\n\n        <footer class="mt-16 border-t border-slate-200/80 pt-8 pb-12 dark:border-slate-800/80">\n            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row text-xs text-slate-500 dark:text-slate-400">\n                <div class="flex items-center gap-2.5">\n                    <span class="relative flex h-2 w-2">\n                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>\n                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>\n                    </span>\n                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $host }}</span>\n                    <span>·</span>\n                    <span>Privacy-first public links & real-time telemetry</span>\n                </div>\n                <div class="flex flex-wrap items-center gap-4 font-medium">\n                    <a href="{{ \App\Support\PublicDashboard::route('links') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Links</a>\n                    <a href="{{ \App\Support\PublicDashboard::route('groups') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Groups</a>\n                    <a href="{{ \App\Support\PublicDashboard::route('insights') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Insights</a>\n                    <a href="{{ \App\Support\PublicDashboard::route('api-keys') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">API</a>\n                    <a href="{{ \App\Support\PublicDashboard::route('domains') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Domains</a>\n                    <a href="{{ \App\Support\PublicDashboard::route('settings') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Settings</a>\n                    <a href="{{ \App\Support\DomainUrls::docs('/') }}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Docs ↗</a>\n                </div>\n            </div>\n        </footer>\n    </div>\n\n    @unless (request()->routeIs($prefix.'links.create', $prefix.'new'))\n        <div class="nd">\n            <x-ui.modal name="link-creator" title="New Short Link">\n                <livewire:dashboard.link-form :modal="true" scope="public" theme="public" />\n            </x-ui.modal>\n        </div>\n\n        <div\n            x-data\n            x-on:keydown.window="if ($event.key.toLowerCase() === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); $dispatch('open-link-creator'); }"\n        ></div>\n    @endunless\n</x-layouts.app>\n