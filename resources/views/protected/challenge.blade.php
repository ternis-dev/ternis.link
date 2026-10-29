<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Security Check — protected.ternis.link</title>
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (! empty($turnstileKey))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-neutral-100 px-4 py-8 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
    <div class="w-full max-w-md rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-xl sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-4 dark:border-neutral-800">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-sm shadow-violet-600/30">
                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
                <span class="font-display text-lg font-bold tracking-tight">protected<span>.ternis.link</span></span>
            </div>
            <x-ui.badge tone="solid">Security Gate</x-ui.badge>
        </div>

        <div class="mt-6 text-center">
            <h1 class="font-display text-2xl font-bold tracking-tight">Verify you are human</h1>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                This short link is protected against automated bots. Please complete the quick check to continue to your destination.
            </p>
            <div class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-neutral-100 px-3 py-1 font-mono text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>{{ $link->domain->hostname ?? 'link' }}/{{ $link->slug }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
                <p class="font-semibold">Verification error:</p>
                <ul class="mt-1 list-disc list-inside text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/verify') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="link" value="{{ $link->id }}">
            <input type="hidden" name="rid" value="{{ $rid }}">
            <input type="hidden" name="ts" value="{{ $ts }}">
            <input type="hidden" name="sig" value="{{ $sig }}">

            @if ($requiresPassword)
                <div class="space-y-1.5 text-left">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                        Link Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter the link password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-500 focus:outline-none focus:ring-1 focus:ring-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                </div>
            @endif

            @if (! empty($turnstileKey))
                <div class="flex justify-center py-2">
                    <div class="cf-turnstile" data-sitekey="{{ $turnstileKey }}" data-action="verify_human" data-theme="auto"></div>
                </div>
            @else
                {{-- Fallback when Turnstile keys are not configured in environment --}}
                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3 text-center text-xs text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    Bot mitigation active &middot; Press continue to proceed
                </div>
            @endif

            <button
                type="submit"
                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-neutral-900 py-3 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 transition-all active:scale-[0.99]"
            >
                <span>Continue to Destination</span>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>

        <div class="mt-6 border-t border-neutral-100 pt-4 text-center text-[11px] text-neutral-400 dark:border-neutral-800 dark:text-neutral-500">
            Request ID: <span class="font-mono">{{ $rid }}</span> &middot; Zero tracking cookies
        </div>
    </div>
</body>
</html>
