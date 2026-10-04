@props(['code' => '500', 'title' => 'Something went wrong'])

@php
[$kicker, $hint] = match ((string) $code) {
    '404' => ['link not found', 'Creator links are case-sensitive — double-check spelling, or the redirect may have been deactivated or expired.'],
    '403' => ['members only', 'This feature requires an authenticated creator account — sign in to continue.'],
    '419' => ['session expired', 'Your session timed out while the page was open. Please reload and try again.'],
    '429' => ['too fast', 'Too many requests in a short time. Please wait a moment — guest links are rate-limited.'],
    '500' => ['server error', 'An unexpected error occurred on our side and has been logged. Please try again shortly.'],
    '503' => ['brief maintenance', 'We are performing scheduled maintenance or starting up. Redirects and tools will be back in seconds.'],
    default => ['notice', null],
};
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · href.yt</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://href.yt{{ request()->getPathInfo() }}">
    <meta name="theme-color" content="#0f0f0f">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css', 'resources/css/yt.css'])
</head>
<body class="yt-root">
    <a class="yt-skip" href="#error-content">Skip to error message</a>

    <header class="yt-head">
        <a href="/" class="yt-brand" aria-label="href.yt home">
            <span class="yt-brand-play" aria-hidden="true">
                <svg viewBox="0 0 16 16" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
            </span>
            href<span>.yt</span>
        </a>
        <nav class="yt-nav" aria-label="Navigation">
            <a href="/" class="yt-login yt-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>back to shortener</span>
            </a>
        </nav>
    </header>

    <div class="yt-wrap">
        <main id="error-content" class="yt-error-main" tabindex="-1">
            <div class="yt-badge" aria-hidden="true">
                @switch((string) $code)
                    @case('404')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        @break
                    @case('403')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        @break
                    @case('419')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        @break
                    @case('429')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>
                        @break
                    @case('500')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        @break
                    @case('503')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="10" y1="15" x2="10" y2="9"/><line x1="14" y1="15" x2="14" y2="9"/></svg>
                        @break
                    @default
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                @endswitch
                <span>{{ $kicker }}</span>
            </div>

            <p class="yt-error-code">{{ $code }}<span>.</span></p>

            <h1 class="yt-error-title">{{ $title }}</h1>

            <div class="yt-error-body">
                {{ $slot }}
            </div>

            @if ($hint)
                <p class="yt-error-hint">{{ $hint }}</p>
            @endif

            @if (trim($actions ?? '') !== '')
                <div class="yt-error-actions" aria-label="Error actions">
                    {{ $actions }}
                </div>
            @else
                <div class="yt-error-actions">
                    <a href="/" class="yt-play-cta">
                        <span>Back to href.yt</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="/new" class="yt-ghost">
                        <span>Shorten a new link</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            @endif
        </main>

        <footer class="yt-foot">
            <p class="yt-foot-links">
                href.yt &copy; {{ date('Y') }} sketched by <a href="https://ternis.dev">ternis.dev</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="https://ternis.link/pages/legal/privacy">privacy</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="https://ternis.link/pages/legal/terms">terms</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}">imprint</a>
            </p>
            <p class="yt-foot-disclaimer">
                href.yt is an independent link shortening service and is not affiliated with, endorsed by, authorized by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc., or any of their subsidiaries or affiliates. "YouTube" is a registered trademark of Google LLC.
            </p>
        </footer>
    </div>
</body>
</html>
