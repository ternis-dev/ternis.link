@props(['title' => 'Admin Console'])

@php
$nav = [
    ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Overview', 'icon' => '<path d="M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z"/>'],
    ['route' => 'admin.links', 'match' => 'admin.links*', 'label' => 'Links', 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
    ['route' => 'admin.users', 'match' => 'admin.users*', 'label' => 'Users', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['route' => 'admin.domains', 'match' => 'admin.domains*', 'label' => 'Domains', 'icon' => '<path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/>'],
    ['route' => 'admin.activity', 'match' => 'admin.activity*', 'label' => 'Audit Log', 'icon' => '<path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'],
];

$dashUrl = \App\Support\DomainUrls::dashboard('/');
@endphp

<x-layouts.app :title="$title">
    <div class="mb-6 flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/30">
        <div class="flex items-center gap-2.5">
            <span class="rounded-md bg-amber-500 px-2 py-0.5 text-[11px] font-bold tracking-widest text-white uppercase">Admin</span>
            <p class="text-sm font-medium text-amber-900 dark:text-amber-200">Admin Console — system-wide moderation. Actions here affect all users.</p>
        </div>
        <a href="{{ $dashUrl }}" class="shrink-0 text-xs font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-700 dark:text-amber-200 dark:hover:text-amber-100">Back to Dashboard →</a>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="lg:w-60 lg:shrink-0">
            <nav aria-label="Admin" data-nav="admin-side" class="flex gap-1 overflow-x-auto rounded-xl border border-neutral-200 bg-white p-2 lg:sticky lg:top-6 lg:flex-col dark:border-neutral-800 dark:bg-neutral-900">
                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            'bg-amber-500 text-white' => request()->routeIs($item['match']),
                            'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white' => ! request()->routeIs($item['match']),
                        ])
                        @if (request()->routeIs($item['match'])) aria-current="page" @endif
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>
        <section class="min-w-0 flex-1">
            {{ $slot }}
        </section>
    </div>
</x-layouts.app>
