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
            <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 text-sm">
                    🛡️
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
            <div class="mt-3 inline-block rounded-lg bg-neutral-100 px-3 py-1 font-mono text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                {{ $link->domain->hostname ?? 'link' }}/{{ $link->slug }}
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
                class="w-full rounded-xl bg-neutral-900 py-3 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 cursor-pointer transition-all"
            >
                Continue to Destination &rarr;
            </button>
        </form>

        <div class="mt-6 border-t border-neutral-100 pt-4 text-center text-[11px] text-neutral-400 dark:border-neutral-800 dark:text-neutral-500">
            Request ID: <span class="font-mono">{{ $rid }}</span> &middot; Zero tracking cookies
        </div>
    </div>
</body>
</html>
