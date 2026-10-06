<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>log in — my.ternis.link</title>
    <meta name="description" content="Log in to my.ternis.link with Ternis Auth SSO to manage your href.nz, meinlink.at and href.yt links.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://my.ternis.link/login">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/css/public-dashboard.css'])
</head>
<body class="pd" style="background-color: #faf7f1;">
    <div class="pd-root mx-auto flex min-h-screen w-full max-w-2xl flex-col items-center px-4 py-16 text-center sm:px-6">
        <p class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-400/10 dark:text-indigo-300">
            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
            my.ternis.link · public links
        </p>
        <h1 class="mt-4 font-display text-4xl font-bold tracking-tight text-neutral-900 dark:text-white">Log in to your links</h1>
        <p class="mt-3 max-w-md text-sm text-neutral-500 dark:text-neutral-400">
            One button, no password. Sign-in runs through Ternis Auth SSO and lands you
            back here — your href.nz, meinlink.at &amp; href.yt links live on this dashboard.
        </p>

        @if (session('error'))
            <p class="mt-6 w-full rounded-2xl border border-red-300 bg-red-50 p-4 text-left text-sm text-red-800" role="alert">{{ session('error') }}</p>
        @endif

        <a href="{{ url('/auth/redirect') }}" class="mt-8 rounded-full bg-indigo-600 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500">
            Log in with Ternis Auth
        </a>

        <div class="mt-10 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                <h2 class="font-display text-base font-bold">No account yet?</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Accounts come from Ternis Auth. Guests can still <a href="https://href.nz" class="font-semibold text-indigo-600 underline underline-offset-2 dark:text-indigo-300">shorten links</a> with no account at all.</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                <h2 class="font-display text-base font-bold">Looking for other links?</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">clicked.at, ternis.link and href.re links live on <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="font-semibold text-indigo-600 underline underline-offset-2 dark:text-indigo-300">dash.ternis.link</a> — same account, other dashboard.</p>
            </div>
        </div>

        <p class="mt-10 text-xs text-neutral-400">
            <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2">privacy</a>
            ·
            <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2">terms</a>
        </p>
    </div>
</body>
</html>
