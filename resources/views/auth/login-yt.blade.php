<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Creator Studio Login — href.yt</title>
    <meta name="description" content="href.yt Creator Login — Sign in with Ternis Auth SSO to claim custom video slugs and view real-time click retention.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#ff0033">
    <link rel="canonical" href="https://href.yt/login">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Font preloading --}}
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>

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
                <a href="/" class="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-400 hover:text-white transition">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Back to shortener</span>
                </a>
            </nav>
        </header>

        <main class="my-auto py-10">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-3 py-0.5 text-xs font-medium text-red-300">
                    <span class="yt-rec-dot h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    <span>Creator Studio Access</span>
                </div>
                <h1 class="font-display mt-4 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    Creator Studio Login
                </h1>
                <p class="mt-2 text-sm text-neutral-400">
                    Sign in to unlock custom video slugs, audience analytics, and API keys.
                </p>
            </div>

            <div class="yt-card mt-8 overflow-hidden rounded-2xl p-6 sm:p-8">
                @if (session('error'))
                    <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 p-3.5 text-xs font-medium text-red-300" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <p class="text-xs leading-relaxed text-neutral-300">
                    Authentication is centralized and passwordless via <strong>Ternis Auth SSO</strong>. You don’t need a separate password for href.yt.
                </p>

                <div class="mt-6">
                    <a
                        href="{{ \App\Support\DomainUrls::dashboard('/login') }}"
                        class="yt-btn-primary flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3 font-semibold text-white"
                    >
                        <span>Sign in with Ternis Auth</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>

                <div class="mt-6 border-t border-white/10 pt-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Features for verified accounts</h2>
                    <ul class="mt-3 space-y-2.5 text-xs text-neutral-300">
                        <li class="flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Custom branded vanity video slugs (e.g., <code>href.yt/my-channel</code>)</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Live click counts, referring channels, and viewer geography</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Scoped REST API keys for stream overlays and automation pipelines</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Permanent invariants that never expire in your video descriptions</span>
                        </li>
                    </ul>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-neutral-400">
                Just need one quick link? <a href="/" class="font-medium text-red-400 underline underline-offset-4 hover:text-red-300 transition">Guests can shorten immediately without an account</a>
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
</body>
</html>
