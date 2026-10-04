<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shorten a Video Link — href.yt</title>
    <meta name="description" content="Quickly accelerate a video, stream, or destination link with href.yt.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#ff0033">
    <link rel="canonical" href="https://href.yt/new">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Font preloading --}}
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/yt.css', 'resources/js/yt.js'])
    @livewireStyles

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="yt-canvas min-h-screen font-sans text-neutral-100 antialiased selection:bg-red-600 selection:text-white">

    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 overflow-hidden">
        <div class="absolute -top-48 left-1/2 -translate-x-1/2 h-96 w-[700px] rounded-full bg-gradient-to-b from-red-600/15 via-rose-600/10 to-transparent blur-3xl"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-2xl flex-col justify-between px-4 py-8 sm:px-6">
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
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="yt-btn-secondary rounded-lg px-3 py-1.5 text-xs font-semibold">
                        Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="yt-btn-secondary rounded-lg px-3 py-1.5 text-xs font-semibold">
                        Creator Login
                    </a>
                @endauth
            </nav>
        </header>

        <main class="my-auto py-10">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-3 py-0.5 text-xs font-medium text-red-300">
                    <span class="yt-rec-dot h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    <span>Studio Mode</span>
                </div>
                <h1 class="font-display mt-4 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    Accelerate a Video Link
                </h1>
                <p class="mt-2 text-sm text-neutral-400">
                    Enter your video, stream, or destination URL to create an instant short link.
                </p>
            </div>

            <div class="yt-card mt-8 overflow-hidden rounded-2xl">
                <div class="flex items-center justify-between border-b border-white/10 bg-black/40 px-4 py-2 text-xs">
                    <span class="font-mono text-neutral-400">href.yt // studio-new</span>
                    <span class="yt-timecode rounded px-1.5 py-0.5 text-[10px] text-red-400">READY</span>
                </div>
                <div class="p-6 sm:p-8">
                    <livewire:public.shorten-form :compact="true" :minimal="true" />
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-neutral-400">
                <a href="/" class="font-medium text-red-400 underline underline-offset-4 hover:text-red-300 transition">
                    &larr; Back to full overview
                </a>
            </p>
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

    @livewireScripts
</body>
</html>
