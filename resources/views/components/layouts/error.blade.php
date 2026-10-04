@props(['code' => '500', 'title' => 'Something went wrong'])

@if (request()->attributes->get('domain_type') === 'public' && \App\Support\PublicHost::isMeinlink())
<x-layouts.public-error-meinlink :code="$code" :title="$title">
    {{ $slot }}
    @if (trim($actions ?? '') !== '')
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
</x-layouts.public-error-meinlink>
@elseif (request()->attributes->get('domain_type') === 'public' && \App\Support\PublicHost::isQr())
<x-layouts.public-error-qr :code="$code" :title="$title">
    {{ $slot }}
    @if (trim($actions ?? '') !== '')
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
</x-layouts.public-error-qr>
@elseif (request()->attributes->get('domain_type') === 'public' && \App\Support\PublicHost::isClicked())
<x-layouts.public-error-clicked :code="$code" :title="$title">
    {{ $slot }}
    @if (trim($actions ?? '') !== '')
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
</x-layouts.public-error-clicked>
@elseif (request()->attributes->get('domain_type') === 'public')
<x-layouts.public-error :code="$code" :title="$title">
    {{ $slot }}
    @if (trim($actions ?? '') !== '')
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
</x-layouts.public-error>
@else
@php
// Category pill + one-line follow-up hint per status code.
[$category, $hint] = match ((string) $code) {
    '403' => ['Forbidden', 'This area needs a different role or a fresh sign-in.'],
    '404' => ['Not found', 'Check the URL for typos — slugs are case-sensitive.'],
    '419' => ['Session expired', 'Reload the page and try again, or sign in fresh.'],
    '429' => ['Rate limited', 'Wait a moment and retry — slow down repeated attempts.'],
    '500' => ['Server error', 'We’ve logged it — try again in a moment.'],
    '503' => ['Unavailable', 'Maintenance or startup — back shortly.'],
    default => ['Error', null],
};
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · {{ \App\Support\DomainUrls::isInternal() ? 'int.ternis.link' : 'ternis.link' }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <script>
        try {
            const stored = localStorage.getItem('tl-theme');
            if (stored === 'light' || (stored !== 'dark' && matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-neutral-50 px-6 py-12 dark:bg-neutral-950">
    <main class="w-full max-w-md">
        <div class="rounded-2xl border border-neutral-200 bg-white p-8 text-center shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="inline-flex items-center rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">{{ $code }} · {{ $category }}</span>
            <div class="font-display mt-4 text-7xl font-bold tracking-tight">{{ $code }}</div>
            <h1 class="mt-3 text-xl font-semibold">{{ $title }}</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-neutral-500 dark:text-neutral-400">{{ $slot }}</p>
            @if ($hint)
                <p class="mx-auto mt-1 max-w-md text-sm text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
            @endif
            @if (trim($actions ?? '') !== '')
                <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
            @endif
        </div>
        @if (\App\Support\DomainUrls::isInternal())
            <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-500">int.ternis.link · internal routing · <a href="{{ url('/imprint') }}" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">imprint</a> · <a href="https://ternis.link" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">ternis.link</a> · <a href="https://ternis.dev" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">ternis.dev</a></p>
        @else
            <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-500">href.nz · href.re · ternis.link · <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">privacy</a> · <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">terms</a></p>
        @endif
    </main>
</body>
</html>
@endif
