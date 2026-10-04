@php
$faqs = [
    ['q' => 'Can I shorten regular links or only videos on href.yt?', 'a' => 'You can shorten any web destination. href.yt is tuned for YouTube, Twitch, Shorts, and video creators, but standard links get the same direct redirect.'],
    ['q' => 'How do timestamp deep-links work?', 'a' => 'Timestamp parameters like <code>?t=90s</code> or <code>?t=1m30s</code> are preserved through the redirect, taking viewers straight to the moment you meant to share.'],
    ['q' => 'Do href.yt links show ads or countdowns?', 'a' => 'Never. Every redirect resolves directly over TLS 1.3 with zero ad walls, countdown timers, or third-party tracking scripts.'],
    ['q' => 'How do I get custom slugs?', 'a' => 'Guests can shorten instantly. Sign in with <a href="/login" class="underline">Ternis Auth</a> to claim custom slugs and access real-time retention charts.'],
];
$faqJsonLd = array_map(fn ($faq) => ['@type' => 'Question', 'name' => $faq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['a'])]], $faqs);
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.yt — Links with a point of view</title>
    <meta name="description" content="Compact, direct links for video creators, streamers, and the moments worth sharing.">
    <meta name="theme-color" content="#f2efe8"><link rel="canonical" href="https://href.yt/"><link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta property="og:type" content="website"><meta property="og:site_name" content="href.yt"><meta property="og:title" content="href.yt — Links with a point of view"><meta property="og:description" content="Compact, direct links for the moments worth sharing."><meta property="og:url" content="https://href.yt/">
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin><link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [['@type' => 'WebSite', '@id' => 'https://href.yt/#website', 'url' => 'https://href.yt/', 'name' => 'href.yt'], ['@type' => 'WebApplication', '@id' => 'https://href.yt/#webapp', 'name' => 'href.yt', 'applicationCategory' => 'UtilitiesApplication', 'url' => 'https://href.yt/'], ['@type' => 'FAQPage', '@id' => 'https://href.yt/#faq', 'mainEntity' => $faqJsonLd]]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @vite(['resources/css/yt.css', 'resources/js/yt.js']) @livewireStyles
    @if (config('services.turnstile.key'))<link rel="preconnect" href="https://challenges.cloudflare.com"><script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>@endif
</head>
<body class="yt-canvas font-sans antialiased">
<div class="yt-shell">
    <header class="yt-nav relative z-10 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 lg:px-10">
        <a href="/" class="yt-logo flex items-center gap-3 font-display text-xl font-bold" aria-label="href.yt home">
            <span class="yt-logo-mark flex h-9 w-9 items-center justify-center rounded-full text-lg">↗</span><span>href<span class="font-normal opacity-45">.yt</span></span>
        </a>
        <div class="flex items-center gap-4 text-sm">
            <a href="#why" class="yt-link hidden sm:inline">Why href.yt</a><a href="#faq" class="yt-link hidden sm:inline">FAQ</a>
            @auth<a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="yt-btn-secondary rounded-full px-4 py-2 text-xs font-semibold">Dashboard ↗</a>
            @else<a href="{{ url('/login') }}" class="yt-btn-secondary rounded-full px-4 py-2 text-xs font-semibold">Creator login</a>@endauth
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-7xl px-5 pb-24 pt-16 lg:px-10 lg:pt-24">
        <section class="grid items-end gap-14 lg:grid-cols-[1.05fr_.95fr]">
            <div>
                <p class="yt-kicker font-mono text-[11px] font-bold uppercase">A small tool for big moments / 01</p>
                <h1 class="yt-display mt-6 max-w-3xl font-display text-6xl font-bold sm:text-8xl">Make the<br><span class="yt-stroke">moment</span><br><span class="text-[var(--signal)]">click.</span></h1>
                <p class="mt-8 max-w-lg text-base leading-relaxed text-[var(--muted)] sm:text-lg">A link shortener with a point of view. Built for the clip, the chapter, the drop, and every place a long URL gets in the way.</p>
                <div class="mt-8 flex flex-wrap items-center gap-5 text-xs font-semibold">
                    <a href="#make" class="yt-btn-primary rounded-full px-5 py-3">Cut a link <span class="yt-arrow ml-2 inline-block">↗</span></a>
                    <span class="font-mono text-[var(--muted)]">No account · No ad wall</span>
                </div>
            </div>
            <div id="make" class="yt-desk rounded-[2rem] p-5 sm:p-8">
                <div class="yt-desk-bar flex items-center justify-between border-b pb-4 font-mono text-[10px] uppercase tracking-[.18em] text-white/55">
                    <span><i class="yt-rec-dot mr-2 inline-block h-2 w-2 rounded-full bg-[var(--signal)]"></i>live / link desk</span><span>signal 001</span>
                </div>
                <div class="pb-7 pt-8"><p class="font-display text-3xl font-bold tracking-tight sm:text-4xl">What are we<br><span class="text-[var(--acid)]">sending out?</span></p><p class="mt-3 max-w-sm text-sm leading-relaxed text-white/55">Paste a video, stream, or any destination. We’ll give it a clean, memorable exit.</p></div>
                <livewire:public.shorten-form :compact="true" />
                <div class="mt-8 flex items-center justify-between border-t border-white/15 pt-4 font-mono text-[10px] uppercase tracking-[.16em] text-white/45"><span>youtube · twitch · everywhere</span><span>7 chars / direct</span></div>
            </div>
        </section>

        <div class="yt-marquee mt-24 -mx-5 overflow-hidden py-3 lg:-mx-10"><div class="yt-marquee-track font-mono text-xs font-bold uppercase tracking-[.25em]">timestamp ready&nbsp; ✦&nbsp; zero ad walls&nbsp; ✦&nbsp; creator-owned&nbsp; ✦&nbsp; timestamp ready&nbsp; ✦&nbsp; zero ad walls&nbsp; ✦&nbsp; creator-owned&nbsp; ✦&nbsp;</div></div>

        <section id="why" class="mt-24 grid gap-10 lg:grid-cols-[.8fr_1.2fr]">
            <div><p class="yt-kicker font-mono text-[11px] font-bold uppercase">The edit / 02</p><h2 class="yt-display mt-4 max-w-sm font-display text-4xl font-bold sm:text-5xl">Less link.<br>More signal.</h2><p class="mt-6 max-w-sm text-sm leading-relaxed text-[var(--muted)]">href.yt is deliberately narrow: make the handoff from your idea to someone else’s screen feel instant.</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <article class="yt-card rounded-3xl p-6"><span class="font-mono text-xs text-[var(--signal-dark)]">01 / TIME</span><h3 class="mt-12 font-display text-xl font-bold">Land on the moment</h3><p class="mt-3 text-sm leading-relaxed text-[var(--muted)]">Keep <code>?t=...</code> intact and send people directly to the punchline, chorus, or chapter.</p></article>
                <article class="yt-card rounded-3xl p-6"><span class="font-mono text-xs text-[var(--signal-dark)]">02 / TRUST</span><h3 class="mt-12 font-display text-xl font-bold">No strange detours</h3><p class="mt-3 text-sm leading-relaxed text-[var(--muted)]">Direct TLS redirects. No countdowns, bait buttons, or third-party cookies between click and content.</p></article>
                <article class="yt-card rounded-3xl p-6"><span class="font-mono text-xs text-[var(--signal-dark)]">03 / SHAPE</span><h3 class="mt-12 font-display text-xl font-bold">Fits the frame</h3><p class="mt-3 text-sm leading-relaxed text-[var(--muted)]">Seven characters for chat, descriptions, overlays, bios, and anywhere attention is already scarce.</p></article>
                <article class="yt-card rounded-3xl p-6"><span class="font-mono text-xs text-[var(--signal-dark)]">04 / RANGE</span><h3 class="mt-12 font-display text-xl font-bold">Not just video</h3><p class="mt-3 text-sm leading-relaxed text-[var(--muted)]">YouTube, Twitch, TikTok, Vimeo—or the ordinary web page you need to put somewhere better.</p></article>
            </div>
        </section>

        <section id="faq" class="mt-24 border-t border-[var(--line)] pt-12"><div class="flex flex-col justify-between gap-4 sm:flex-row"><h2 class="font-display text-3xl font-bold">Notes from the desk</h2><span class="font-mono text-xs text-[var(--muted)]">FAQ / 04</span></div><div class="mt-8 grid gap-4 sm:grid-cols-2">@foreach ($faqs as $faq)<article class="border-b border-[var(--line)] pb-5"><h3 class="font-display font-bold">{{ $faq['q'] }}</h3><p class="mt-2 text-sm leading-relaxed text-[var(--muted)]">{!! $faq['a'] !!}</p></article>@endforeach</div></section>
    </main>
    <footer class="relative z-10 border-t border-[var(--line)] px-5 py-8 lg:px-10"><div class="mx-auto flex max-w-7xl flex-col justify-between gap-4 text-xs text-[var(--muted)] sm:flex-row"><span class="font-display text-lg font-bold text-[var(--ink)]">href<span class="opacity-40">.yt</span></span><div class="flex flex-wrap gap-4"><a href="https://ternis.link/pages/legal/privacy" class="yt-link">Privacy</a><a href="https://ternis.link/pages/legal/terms" class="yt-link">Terms</a><a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}" class="yt-link">Imprint</a><a href="https://ternis.dev" class="yt-link">ternis.dev ↗</a></div></div><p class="mx-auto mt-6 max-w-7xl text-[10px] leading-relaxed text-[var(--muted)]">href.yt is an independent utility and is not affiliated with YouTube, Google LLC, Alphabet Inc., or their subsidiaries.</p></footer>
</div>
@livewireScripts
</body>
</html>
