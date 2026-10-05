@props(['title' => 'Dashboard'])

@php
$dashHost = config('domains.dashboard_host', 'dash.ternis.link');
$scheme = request()->getScheme();
$nav = [
    ['route' => 'public-dashboard', 'match' => 'public-dashboard', 'label' => 'Overview', 'icon' => '<path d="M3 3h7v7H3zM14 3h3v4h-3zM14 10h3v7h-3zM3 13h7v4H3z"/>'],
    ['route' => 'public-dashboard.links', 'match' => 'public-dashboard.links*', 'label' => 'Links', 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
];

$homeHref = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1', 'testserver'], true)
    ? url('/dashboard')
    : route('public-dashboard');
$nav[0]['href'] = $homeHref;
@endphp

<x-layouts.app :title="$title" maxWidth="max-w-[1440px]">
    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="lg:w-60 lg:shrink-0">
            <nav aria-label="Public dashboard" data-nav="side" class="no-scrollbar flex gap-1 overflow-x-auto rounded-xl border border-neutral-200 bg-white p-2 lg:sticky lg:top-6 lg:flex-col dark:border-neutral-800 dark:bg-neutral-900">
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
                    </a>
                @endforeach
                <div class="mt-2 hidden border-t border-neutral-200 pt-2 lg:block dark:border-neutral-800">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Account on dash</p>
                    @foreach ([['label' => 'API Keys', 'path' => '/api-keys'], ['label' => 'Domains', 'path' => '/domains'], ['label' => 'Settings', 'path' => '/settings']] as $ext)
                        <a
                            href="{{ $scheme }}://{{ $dashHost }}{{ $ext['path'] }}"
                            class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white"
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>
                            {{ $ext['label'] }}
                        </a>
                    @endforeach
                </div>
            </nav>
        </aside>
        <section class="min-w-0 flex-1">
            {{ $slot }}
        </section>
    </div>

    @unless (request()->routeIs('public-dashboard.links.create', 'public-dashboard.new'))
        <x-ui.modal name="link-creator" title="New Short Link">
            <livewire:dashboard.link-form :modal="true" scope="public" />
        </x-ui.modal>

        <div
            x-data
            x-on:keydown.window="if ($event.key.toLowerCase() === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.metaKey && !$event.ctrlKey) { $event.preventDefault(); $dispatch('open-link-creator'); }"
        ></div>
    @endunless
</x-layouts.app>
