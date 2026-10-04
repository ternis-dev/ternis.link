@props(['code' => '500', 'title' => 'Something went wrong'])

@php
[$kicker, $hint] = match ((string) $code) {
    '404' => ['Signal Lost · Not Found', 'The requested video link or route does not exist, has expired, or was typed with a typo. Slugs are case-sensitive.'],
    '403' => ['Restricted Access', 'This area requires authenticated creator credentials. Please sign in to continue.'],
    '419' => ['Session Expired', 'Your connection timed out. Please reload the page and try again.'],
    '429' => ['Too Many Requests', 'Rate limit reached. Please wait a few moments before submitting again.'],
    '500' => ['Broadcast Error', 'An unexpected error occurred on our servers. Our team has received the diagnostic trace.'],
    '503' => ['Stream Maintenance', 'href.yt infrastructure is undergoing quick scheduled maintenance. We’ll be back shortly.'],
    default => ['Notice', null],
};
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · href.yt</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#ff0033">
    <link rel="canonical" href="https://href.yt{{ request()->getPathInfo() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/yt.css'])
</head>
<body class="yt-canvas min-h-screen font-sans text-neutral-100 antialiased selection:bg-red-600 selection:text-white">

    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 overflow-hidden">
        <div class="absolute -top-48 left-1/2 -translate-x-1/2 h-96 w-[700px] rounded-full bg-gradient-to-b from-red-600/15 via-rose-600/10 to-transparent blur-3xl"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-xl flex-col justify-between px-4 py-8 sm:px-6">
        <header class="flex items-center justify-between">
            <a href="/" class="group flex items-center gap-2.5" aria-label="href.yt home">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-rose-700 text-white shadow-md shadow-red-600/25 transition duration-200 group-hover:scale-105">
                    <svg class="h-4 w-4 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                </span>
                <span class="font-display text-lg font-bold tracking-tight text-white">
                    href<span class="text-red-500">.yt</span>
                </span>
            </a>

            <nav aria-label="Navigation">
                <a href="/" class="text-xs font-medium text-neutral-400 hover:text-white transition">
                    &larr; Back to home
                </a>
            </nav>
        </header>

        <main id="error-content" class="my-auto py-10 text-center" tabindex="-1">
            <div class="yt-card yt-scanlines overflow-hidden rounded-2xl p-8 sm:p-10">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-500/30 bg-red-500/10 px-3 py-1 text-xs font-semibold text-red-400">
                    <span class="yt-rec-dot h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    <span>{{ $kicker }}</span>
                </span>

                <p class="font-display mt-5 text-6xl font-black tracking-tight text-white sm:text-7xl">
                    {{ $code }}<span class="text-red-500">.</span>
                </p>

                <h1 class="mt-3 text-xl font-bold tracking-tight text-white sm:text-2xl">
                    {{ $title }}
                </h1>

                <div class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-neutral-400">
                    {{ $slot }}
                </div>

                @if ($hint)
                    <p class="mx-auto mt-3 max-w-md text-xs text-neutral-500">
                        {{ $hint }}
                    </p>
                @endif

                @if (trim($actions ?? '') !== '')
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        {{ $actions }}
                    </div>
                @else
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <a href="/" class="yt-btn-primary inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-semibold">
                            Back to href.yt
                        </a>
                        <a href="/new" class="yt-btn-secondary inline-flex items-center rounded-xl px-5 py-2.5 text-xs font-semibold">
                            Accelerate New Link
                        </a>
                    </div>
                @endif
            </div>
        </main>

        <footer class="border-t border-white/5 pt-6 text-center text-xs text-neutral-500">
            <div class="flex flex-wrap items-center justify-center gap-4">
                <span>href.yt</span>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/privacy" class="hover:text-white transition">Privacy</a>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/terms" class="hover:text-white transition">Terms</a>
                <span>·</span>
                <a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}" class="hover:text-white transition">Imprint</a>
            </div>
            <p class="mt-3 text-[11px] leading-relaxed text-neutral-600">
                Disclaimer: href.yt is an independent utility and is not affiliated, associated, authorized, endorsed by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc., or any of their subsidiaries or affiliates.
            </p>
        </footer>
    </div>
</body>
</html>
