@props(['title' => 'Dashboard'])

@php
$isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1', 'testserver'], true);
$linksHref = $isLocal ? url('/links') : route('public-dashboard.links');
$homeHref = $isLocal ? url('/dashboard') : route('public-dashboard');
@endphp

<x-layouts.app :title="$title" maxWidth="max-w-6xl">
    <x-slot:head>
        @vite(['resources/css/public-dashboard.css'])
    </x-slot:head>

    <div class="nd nd-root">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-neutral-200 bg-white px-4 py-3 sm:px-5 dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center gap-3">
                <a href="{{ $homeHref }}" class="flex items-center gap-2 font-display text-lg font-bold tracking-tight" aria-label="my.ternis.link home">
                    <span class="inline-block h-3 w-3 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    my<span class="text-neutral-400">.ternis.link</span>
                </a>
                <nav aria-label="Public dashboard" data-tour="nav" class="flex items-center gap-1">
                    <a href="{{ $homeHref }}" @class(['nd-pill px-3.5 py-1.5 text-sm font-semibold text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white']) @if (request()->routeIs('public-dashboard')) aria-current="page" @endif>Overview</a>
                    <a href="{{ $linksHref }}" @class(['nd-pill px-3.5 py-1.5 text-sm font-semibold text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white']) @if (request()->routeIs('public-dashboard.links*')) aria-current="page" @endif>Links</a>
                </nav>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('public-dashboard.switch-legacy') }}" class="inline">
                    @csrf
                    <button type="submit" title="Back to the previous indigo dashboard" class="cursor-pointer rounded-full border border-dashed border-neutral-300 px-3.5 py-1.5 text-xs font-semibold text-neutral-500 transition hover:border-emerald-600 hover:text-emerald-700 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-emerald-400 dark:hover:text-emerald-300">Legacy UI</button>
                </form>
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-link-creator')"
                    title="Keyboard shortcut: C"
                    data-tour="create"
                    class="cursor-pointer rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-500"
                >+ New link</button>
            </div>
        </div>

        <section class="min-w-0">
            {{ $slot }}
        </section>

        <p class="mt-10 text-center text-xs text-neutral-500 dark:text-neutral-500">
            clicked.at, ternis.link and href.re links live on
            <a href="https://{{ config('domains.dashboard_host', 'dash.ternis.link') }}/" class="font-semibold underline underline-offset-2">dash.ternis.link →</a>
        </p>
    </div>

    @unless (request()->routeIs('public-dashboard.links.create', 'public-dashboard.new'))
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
