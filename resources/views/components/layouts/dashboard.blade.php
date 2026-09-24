@props(['title' => 'Dashboard'])

@php
$nav = [
    ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Overview', 'icon' => 'M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z'],
    ['route' => 'dashboard.links', 'match' => 'dashboard.links*', 'label' => 'Links', 'icon' => 'M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06'],
    ['route' => 'dashboard.api-keys', 'match' => 'dashboard.api-keys*', 'label' => 'API Keys', 'icon' => 'M15.75 5.75a3 3 0 0 1 3 3v5.7a2.3 2.3 0 0 1-1.07 1.94l-3.98 2.5a2.3 2.3 0 0 1-2.47 0l-3.98-2.5a2.3 2.3 0 0 1-1.07-1.94V8.75a3 3 0 0 1 3-3h6.57ZM9 8.75v5.7l4.22 2.65a.8.8 0 0 0 .86 0L18.3 14.45V8.75a1.5 1.5 0 0 0-1.5-1.5H10.5a1.5 1.5 0 0 0-1.5 1.5Z'],
    ['route' => 'dashboard.domains', 'match' => 'dashboard.domains*', 'label' => 'Domains', 'icon' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z'],
];
@endphp

<x-layouts.app :title="$title">
    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="lg:w-60 lg:shrink-0">
            <nav aria-label="Dashboard" class="flex gap-1 overflow-x-auto rounded-xl border border-neutral-200 bg-white p-2 lg:sticky lg:top-6 lg:flex-col dark:border-neutral-800 dark:bg-neutral-900">
                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' => request()->routeIs($item['match']),
                            'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white' => ! request()->routeIs($item['match']),
                        ])
                        @if (request()->routeIs($item['match'])) aria-current="page" @endif
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
                @if (auth()->check() && auth()->user()->isAdmin())
                    <a
                        href="{{ route('admin.dashboard') }}"
                        @class([
                            'flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' => request()->routeIs('admin.*'),
                            'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white' => ! request()->routeIs('admin.*'),
                        ])
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6l-8-3Z"/></svg>
                        Admin
                    </a>
                @endif
            </nav>
        </aside>
        <section class="min-w-0 flex-1">
            {{ $slot }}
        </section>
    </div>
</x-layouts.app>
