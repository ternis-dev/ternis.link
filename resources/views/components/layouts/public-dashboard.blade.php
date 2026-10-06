@props(['title' => 'Dashboard'])

@php
$dashHost = config('domains.dashboard_host', 'dash.ternis.link');
$isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1', 'testserver'], true);
$homeHref = $isLocal ? url('/dashboard') : route('public-dashboard');

$nav = [
    ['href' => $homeHref, 'match' => 'public-dashboard', 'label' => 'Overview'],
    ['href' => $isLocal ? url('/links') : route('public-dashboard.links'), 'match' => 'public-dashboard.links*', 'label' => 'Links'],
];
@endphp

<x-layouts.app :title="$title" maxWidth="max-w-6xl">
    <x-slot:head>
        @vite(['resources/css/public-dashboard.css'])
    </x-slot:head>

    <div class="pd pd-root">
        {{-- Brand band --}}
        <div class="pd-hero mb-8 overflow-hidden rounded-3xl px-6 py-8 text-white sm:px-10">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-300"></span>
                        my.ternis.link · public links
                    </p>
                    <h1 class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Your short links, minus the clutter.</h1>
                    <p class="mt-2 max-w-xl text-sm text-white/80">href.nz, meinlink.at &amp; href.yt in one focused workspace. Everything else lives on dash.ternis.link.</p>
                </div>
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-link-creator')"
                    class="cursor-pointer rounded-full bg-white px-5 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-50"
                >
                    + New short link
                </button>
            </div>
            <nav aria-label="Public dashboard" class="mt-6 flex flex-wrap gap-2">
                @foreach ($nav as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @class(['pd-pill px-4 py-1.5 text-sm font-semibold text-white/85 hover:bg-white/15 hover:text-white'])
                        @if (request()->routeIs($item['match'])) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @endforeach
                <span class="mx-1 hidden h-6 w-px self-center bg-white/25 sm:inline-block" aria-hidden="true"></span>
                <a href="https://{{ $dashHost }}/api-keys" class="pd-pill px-4 py-1.5 text-sm font-semibold text-white/85 hover:bg-white/15 hover:text-white">API Keys ↗</a>
                <a href="https://{{ $dashHost }}/domains" class="pd-pill px-4 py-1.5 text-sm font-semibold text-white/85 hover:bg-white/15 hover:text-white">Domains ↗</a>
                <a href="https://{{ $dashHost }}/settings" class="pd-pill px-4 py-1.5 text-sm font-semibold text-white/85 hover:bg-white/15 hover:text-white">Settings ↗</a>
            </nav>
        </div>

        <section class="min-w-0">
            {{ $slot }}
        </section>

        <p class="mt-10 text-center text-xs text-neutral-500 dark:text-neutral-500">
            Looking for clicked.at, ternis.link or href.re links?
            <a href="https://{{ $dashHost }}/" class="font-semibold underline underline-offset-2">Open dash.ternis.link →</a>
        </p>
    </div>

    @unless (request()->routeIs('public-dashboard.links.create', 'public-dashboard.new'))
        <div class="pd">
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
