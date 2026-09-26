@props(['title' => 'Dashboard'])

@php
$nav = [
    ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Overview', 'icon' => '<path d="M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z"/>'],
    ['route' => 'dashboard.links', 'match' => 'dashboard.links*', 'label' => 'Links', 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
    ['route' => 'dashboard.api-keys', 'match' => 'dashboard.api-keys*', 'label' => 'API Keys', 'icon' => '<circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/>'],
    ['route' => 'dashboard.domains', 'match' => 'dashboard.domains*', 'label' => 'Domains', 'icon' => '<path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/>'],
    ['route' => 'dashboard.notifications', 'match' => 'dashboard.notifications*', 'label' => 'Notifications', 'badge' => 'unread', 'icon' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>'],
    ['route' => 'dashboard.activity', 'match' => 'dashboard.activity*', 'label' => 'Activity', 'icon' => '<path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'],
    ['route' => 'dashboard.settings', 'match' => 'dashboard.settings*', 'label' => 'Settings', 'icon' => '<path d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>'],
];

$unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

$topNav = auth()->check() && auth()->user()->usesTopNav();

// Dashboard home has no host-blind URL: the pinned route always
// generates the dashboard host (right for prod, unreachable locally),
// while /dashboard redirects prod but serves locally. Pick per host.
$homeHref = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1', 'testserver'], true)
    ? url('/dashboard')
    : route('dashboard');
$nav[0]['href'] = $homeHref;
@endphp

<x-layouts.app :title="$title" maxWidth="max-w-[1440px]">
    @if ($topNav)
        <nav aria-label="Dashboard" data-nav="top" class="sticky top-4 z-30 mb-6 flex items-center justify-between gap-1 overflow-x-auto rounded-xl border border-neutral-200 bg-white p-2 dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center gap-1">
                @foreach ($nav as $item)
                    <a
                        href="{{ $item['href'] ?? route($item['route']) }}"
                        @class([
                            'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' => request()->routeIs($item['match']),
                            'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white' => ! request()->routeIs($item['match']),
                        ])
                        @if (request()->routeIs($item['match'])) aria-current="page" @endif
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        {{ $item['label'] }}
                        @if (($item['badge'] ?? null) === 'unread' && $unreadCount > 0)
                            <span class="ml-auto rounded-full bg-neutral-900 px-2 py-0.5 text-[11px] font-semibold leading-none text-white dark:bg-white dark:text-neutral-900">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-link-creator')"
                class="ml-auto flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100"
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Create Link
            </button>
        </nav>
        <section class="min-w-0">
            {{ $slot }}
        </section>
    @else
        <div class="flex flex-col gap-6 lg:flex-row">
            <aside class="lg:w-60 lg:shrink-0">
                <nav aria-label="Dashboard" data-nav="side" class="flex gap-1 overflow-x-auto rounded-xl border border-neutral-200 bg-white p-2 lg:sticky lg:top-6 lg:flex-col dark:border-neutral-800 dark:bg-neutral-900">
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-link-creator')"
                        class="mb-2 hidden lg:flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-neutral-900 px-3 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Create Link
                    </button>
                    @foreach ($nav as $item)
                        <a
                            href="{{ $item['href'] ?? route($item['route']) }}"
                            @class([
                                'flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' => request()->routeIs($item['match']),
                                'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white' => ! request()->routeIs($item['match']),
                            ])
                            @if (request()->routeIs($item['match'])) aria-current="page" @endif
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            {{ $item['label'] }}
                            @if (($item['badge'] ?? null) === 'unread' && $unreadCount > 0)
                                <span class="ml-auto rounded-full bg-neutral-900 px-2 py-0.5 text-[11px] font-semibold leading-none text-white dark:bg-white dark:text-neutral-900">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>
            <section class="min-w-0 flex-1">
                {{ $slot }}
            </section>
        </div>
    @endif

    @unless (request()->routeIs('dashboard.links.create', 'dashboard.new'))
        <x-ui.modal name="link-creator" title="New Short Link">
            <livewire:dashboard.link-form :modal="true" />
        </x-ui.modal>

        <div
            x-data
            x-on:keydown.window="if ($event.key.toLowerCase() === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); $dispatch('open-link-creator'); }"
        ></div>
    @endunless
</x-layouts.app>
