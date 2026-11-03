<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Protection Debugger — protected.ternis.link</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon-protected.svg') }}">
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
</head>
<body class="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 antialiased selection:bg-amber-500 selection:text-neutral-950">

    {{-- Clean Header --}}
    <header class="border-b border-neutral-200 dark:border-neutral-800 bg-white/80 dark:bg-neutral-950/80 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('protected.index') }}" class="font-display text-base font-bold tracking-tight text-neutral-950 dark:text-white sm:text-lg">
                    protected<span class="text-neutral-500">.ternis.link</span>
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-mono font-bold text-amber-700 dark:text-amber-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    ADMIN DEBUG MODE
                </span>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('protected.index') }}"
                    class="rounded-lg border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                >
                    ← Gateway
                </a>
                <a
                    href="https://admin.ternis.link/bot-protection"
                    class="rounded-lg border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                >
                    Admin Console ↗
                </a>

                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-200 dark:border-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4 py-8 sm:px-6 sm:py-10 space-y-6">

        {{-- Flash message --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 flex items-center justify-between">
                <span>✓ {{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-neutral-600 dark:text-neutral-400 hover:text-neutral-950 dark:hover:text-white">&times;</button>
            </div>
        @endif

        {{-- Panel 1: Protection Decision Sandbox & Trace --}}
        <section class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-neutral-200/80 dark:border-neutral-800/80 pb-3">
                <div>
                    <h2 class="font-display text-base font-bold text-neutral-950 dark:text-white">Protection Decision Sandbox</h2>
                    <p class="text-xs text-neutral-600 dark:text-neutral-400">Debug why protection measures trigger across different links, client signatures, and cookie states.</p>
                </div>
                <span class="rounded bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[11px] font-mono text-neutral-600 dark:text-neutral-400">Live Simulator</span>
            </div>

            <div class="mt-5 space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="sim-link-slug" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                            Short Link or ID
                        </label>
                        <input
                            type="text"
                            id="sim-link-slug"
                            placeholder="e.g. href.nz/promo or link ID"
                            value="{{ $diagnostics['test_link']->short_url ?? '' }}"
                            class="mt-1 w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-2 text-xs font-mono text-neutral-950 dark:text-white placeholder:text-neutral-600 focus:border-neutral-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label for="sim-user-agent" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                            Client User-Agent
                        </label>
                        <input
                            type="text"
                            id="sim-user-agent"
                            value="{{ $diagnostics['client']['user_agent'] }}"
                            class="mt-1 w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-2 text-xs font-mono text-neutral-950 dark:text-white placeholder:text-neutral-600 focus:border-neutral-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label for="sim-has-cookie" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                            Human Verification Cookie
                        </label>
                        <select
                            id="sim-has-cookie"
                            class="mt-1 w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-2 text-xs text-neutral-950 dark:text-white focus:border-neutral-500 focus:outline-none"
                        >
                            <option value="auto">Use Current State ({{ $diagnostics['verification']['cookie_details']['status'] ?? 'missing' }})</option>
                            <option value="yes">Force Verified Human (Valid Cookie)</option>
                            <option value="no">Force Unverified (No Cookie)</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <div class="flex flex-wrap items-center gap-1.5 text-xs text-neutral-500">
                        <span class="text-[11px]">Presets:</span>
                        <button type="button" class="sim-preset rounded border border-neutral-200 dark:border-neutral-800 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:hover:bg-neutral-800" data-ua="{{ $diagnostics['client']['user_agent'] }}">Current Browser</button>
                        <button type="button" class="sim-preset rounded border border-neutral-200 dark:border-neutral-800 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:hover:bg-neutral-800" data-ua="curl/8.4.0">cURL 8.4</button>
                        <button type="button" class="sim-preset rounded border border-neutral-200 dark:border-neutral-800 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:hover:bg-neutral-800" data-ua="python-requests/2.31.0">Python</button>
                        <button type="button" class="sim-preset rounded border border-neutral-200 dark:border-neutral-800 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:hover:bg-neutral-800" data-ua="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">Googlebot</button>
                        <button type="button" class="sim-preset rounded border border-neutral-200 dark:border-neutral-800 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:hover:bg-neutral-800" data-ua="sqlmap/1.7.11#stable">sqlmap</button>
                    </div>

                    <button
                        type="button"
                        id="run-sim-btn"
                        class="cursor-pointer rounded-xl bg-white px-4 py-2 text-xs font-bold text-neutral-900 hover:bg-neutral-200 transition-colors"
                    >
                        Simulate Decision
                    </button>
                </div>

                {{-- Live Trace Container --}}
                <div id="sim-result-box" class="mt-4 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/60 dark:bg-neutral-900/60 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-200 dark:border-neutral-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span id="sim-decision-badge" @class([
                                'rounded px-2.5 py-0.5 text-xs font-mono font-bold uppercase',
                                'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' => ($diagnostics['decision_explanation']['decision'] ?? '') === 'ALLOW',
                                'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' => ($diagnostics['decision_explanation']['decision'] ?? '') === 'CHALLENGE_TURNSTILE',
                                'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300' => ($diagnostics['decision_explanation']['decision'] ?? '') === 'CHALLENGE_USER_AGENT',
                            ])>
                                {{ $diagnostics['decision_explanation']['decision'] ?? 'ALLOW' }}
                            </span>
                            <span id="sim-action-text" class="text-xs font-semibold text-neutral-950 dark:text-white">
                                {{ $diagnostics['decision_explanation']['action'] ?? 'Direct 302 Redirect' }}
                            </span>
                        </div>
                        <span id="sim-reason-text" class="text-xs text-neutral-600 dark:text-neutral-400">
                            {{ $diagnostics['decision_explanation']['reason'] ?? '' }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <span class="text-[11px] font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Evaluation Trace:</span>
                        <div id="sim-trace-list" class="mt-2 space-y-1.5 font-mono text-xs">
                            @foreach ($diagnostics['decision_explanation']['trace'] ?? [] as $t)
                                <div class="flex items-center justify-between rounded-lg border border-neutral-200/80 dark:border-neutral-800/80 bg-white dark:bg-neutral-950 px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-neutral-100 dark:bg-neutral-800 px-1.5 py-0.5 text-[10px] text-neutral-600 dark:text-neutral-400">Step {{ $t['step'] }}</span>
                                        <span class="text-neutral-800 dark:text-neutral-200 font-semibold">{{ $t['rule'] }}</span>
                                    </div>
                                    <span class="{{ $t['passed'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-500' }}">
                                        {{ $t['passed'] ? 'PASS' : 'SKIP' }} &middot; {{ $t['detail'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Panel 2: Cookie & Human Session Debugger --}}
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 p-5">
                <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-800 pb-3">
                    <h3 class="font-display text-sm font-bold text-neutral-950 dark:text-white">Human Verification Cookie Cockpit</h3>
                    @php $cookie = $diagnostics['verification']['cookie_details']; @endphp
                    <span @class([
                        'rounded px-2 py-0.5 text-[11px] font-mono font-bold uppercase',
                        'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' => ($cookie['status'] ?? '') === 'valid',
                        'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' => ($cookie['status'] ?? '') === 'expired',
                        'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400' => ($cookie['status'] ?? '') === 'missing',
                        'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300' => ($cookie['status'] ?? '') === 'tampered' || ($cookie['status'] ?? '') === 'malformed',
                    ])>
                        Status: {{ $cookie['status'] ?? 'missing' }}
                    </span>
                </div>

                <dl class="mt-4 space-y-2 text-xs font-mono">
                    <div class="flex justify-between border-b border-neutral-200/60 dark:border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-600 dark:text-neutral-400">Cookie Name:</dt>
                        <dd class="text-neutral-800 dark:text-neutral-200">{{ config('bot_protection.cookie_name', 'tl_human_verified') }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-200/60 dark:border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-600 dark:text-neutral-400">TTL Remaining:</dt>
                        <dd class="{{ ($cookie['is_valid'] ?? false) ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-neutral-500' }}">
                            {{ $cookie['ttl_remaining_seconds'] ?? 0 }} seconds
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-200/60 dark:border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-600 dark:text-neutral-400">Issued Timestamp:</dt>
                        <dd class="text-neutral-700 dark:text-neutral-300">{{ $cookie['verified_at'] ?? 'None' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600 dark:text-neutral-400">Random Token Preview:</dt>
                        <dd class="text-neutral-700 dark:text-neutral-300">{{ $cookie['token_prefix'] ?? 'None' }}</dd>
                    </div>
                </dl>

                <p class="mt-3 text-xs text-neutral-600 dark:text-neutral-400">The cookie is encrypted and authenticated with the server key. Modified values are rejected. The token preview is diagnostic only; a stolen valid cookie can be reused until expiry.</p>

                {{-- Quick Cookie Actions --}}
                <div class="mt-5 flex gap-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
                    <form method="POST" action="{{ route('protected.debug.cookie') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="action" value="issue">
                        <button
                            type="submit"
                            class="w-full cursor-pointer rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-neutral-950 dark:text-white hover:bg-emerald-500 transition-colors"
                        >
                            ⚡ Issue Human Cookie (120 min)
                        </button>
                    </form>

                    <form method="POST" action="{{ route('protected.debug.cookie') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="action" value="clear">
                        <button
                            type="submit"
                            class="w-full cursor-pointer rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-100 dark:bg-neutral-800 px-3 py-2 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors"
                        >
                            ✕ Clear Cookie
                        </button>
                    </form>
                </div>
            </div>

            {{-- Rate Limiter & Edge IP Cockpit --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 p-5">
                <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-800 pb-3">
                    <h3 class="font-display text-sm font-bold text-neutral-950 dark:text-white">Rate Limit &amp; IP Transport</h3>
                    <form method="POST" action="{{ route('protected.debug.clear-ratelimit') }}">
                        @csrf
                        <input type="hidden" name="ip" value="{{ $diagnostics['client']['ip'] }}">
                        <button type="submit" class="cursor-pointer text-xs font-mono text-amber-700 dark:text-amber-400 underline hover:text-amber-700 dark:hover:text-amber-300">
                            Reset IP Bucket
                        </button>
                    </form>
                </div>

                <dl class="mt-4 space-y-2 text-xs font-mono">
                    <div class="flex justify-between border-b border-neutral-200/60 dark:border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-600 dark:text-neutral-400">Client IP:</dt>
                        <dd class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $diagnostics['client']['ip'] }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-200/60 dark:border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-600 dark:text-neutral-400">Rate Limit Consumption:</dt>
                        <dd class="text-neutral-950 dark:text-white font-bold">{{ $diagnostics['rate_limiting']['attempts_used'] }} / {{ $diagnostics['rate_limiting']['max_attempts'] }} attempts</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-200/60 dark:border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-600 dark:text-neutral-400">Remaining Attempts:</dt>
                        <dd class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $diagnostics['rate_limiting']['remaining'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600 dark:text-neutral-400">Lockout Reset Countdown:</dt>
                        <dd class="text-neutral-700 dark:text-neutral-300">{{ $diagnostics['rate_limiting']['available_in_seconds'] }}s</dd>
                    </div>
                </dl>

                <div class="mt-5 rounded-xl border border-neutral-200/60 dark:border-neutral-800/60 bg-neutral-50/50 dark:bg-neutral-900/50 p-2.5 text-[11px] font-mono text-neutral-600 dark:text-neutral-400">
                    <div>Turnstile Site Key: {{ $diagnostics['configuration']['turnstile_site_key_set'] ? 'Active (Configured)' : 'Missing (Fail-Open)' }}</div>
                    <div class="mt-1">Suspicious UA Screening: {{ $diagnostics['configuration']['filter_suspicious_ua'] ? 'Active (' . $diagnostics['configuration']['suspicious_ua_patterns_count'] . ' patterns)' : 'Disabled' }}</div>
                </div>
            </div>
        </section>

        {{-- Panel 3: Challenge Signature Generator & Anti-Replay Sandbox --}}
        <section class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 p-5">
            <h3 class="font-display text-sm font-bold text-neutral-950 dark:text-white">Challenge Signature Generator &amp; Anti-Replay Validator</h3>
            <p class="mt-1 text-xs text-neutral-600 dark:text-neutral-400">Test generating and verifying signed challenge URLs with 15-minute anti-replay expiration.</p>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <input
                    type="text"
                    id="sig-link-id"
                    placeholder="Link ID"
                    value="test_link_123"
                    class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-2 text-xs font-mono text-neutral-950 dark:text-white"
                >
                <input
                    type="text"
                    id="sig-rid"
                    placeholder="Request ID (rid)"
                    value="rid_{{ strtolower((string) \Illuminate\Support\Str::ulid()) }}"
                    class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-3 py-2 text-xs font-mono text-neutral-950 dark:text-white"
                >
                <button
                    type="button"
                    id="generate-sig-btn"
                    class="cursor-pointer rounded-xl bg-neutral-100 dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700 px-4 py-2 text-xs font-bold text-neutral-950 dark:text-white hover:bg-neutral-200 dark:hover:bg-neutral-700"
                >
                    Generate Challenge URLs
                </button>
            </div>

            <div id="sig-result-box" class="hidden mt-3 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 p-3 font-mono text-xs space-y-1.5">
                <div>
                    <span class="text-neutral-500">HMAC-SHA256 Signature:</span>
                    <span id="generated-sig-text" class="text-emerald-700 dark:text-emerald-400 font-bold ml-1"></span>
                </div>
                <div>
                    <span class="text-neutral-500">Live Challenge URL:</span>
                    <a id="generated-challenge-url" href="#" target="_blank" class="text-amber-700 dark:text-amber-400 underline ml-1 hover:text-amber-700 dark:hover:text-amber-300"></a>
                </div>
                <div>
                    <span class="text-neutral-500">Live UA Gate URL:</span>
                    <a id="generated-ua-url" href="#" target="_blank" class="text-amber-700 dark:text-amber-400 underline ml-1 hover:text-amber-700 dark:hover:text-amber-300"></a>
                </div>
            </div>
        </section>

    </main>

    <footer class="border-t border-neutral-200 dark:border-neutral-800 py-6 text-center text-xs text-neutral-500">
        protected.ternis.link &middot; Protection Measures Debugging Cockpit
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Simulator presets
            document.querySelectorAll('.sim-preset').forEach(btn => {
                btn.addEventListener('click', () => {
                    const ua = btn.getAttribute('data-ua');
                    if (ua) {
                        document.getElementById('sim-user-agent').value = ua;
                        runSimulation();
                    }
                });
            });

            // Run simulation button
            const simBtn = document.getElementById('run-sim-btn');
            simBtn?.addEventListener('click', runSimulation);

            function runSimulation() {
                const slug = document.getElementById('sim-link-slug').value;
                const ua = document.getElementById('sim-user-agent').value;
                const cookieState = document.getElementById('sim-has-cookie').value;

                simBtn.disabled = true;
                simBtn.textContent = 'Simulating...';

                fetch('{{ route("protected.debug.simulate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        link_slug_or_id: slug,
                        user_agent: ua,
                        has_cookie: cookieState,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.result) {
                        document.getElementById('sim-reason-text').textContent = data.message || 'Simulation failed.';
                        return;
                    }
                    const res = data.result;
                    const badge = document.getElementById('sim-decision-badge');
                    badge.textContent = res.decision;

                    if (res.decision === 'ALLOW') {
                        badge.className = 'rounded px-2.5 py-0.5 text-xs font-mono font-bold uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300';
                    } else if (res.decision === 'CHALLENGE_TURNSTILE') {
                        badge.className = 'rounded px-2.5 py-0.5 text-xs font-mono font-bold uppercase bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300';
                    } else {
                        badge.className = 'rounded px-2.5 py-0.5 text-xs font-mono font-bold uppercase bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300';
                    }

                    document.getElementById('sim-action-text').textContent = res.action;
                    document.getElementById('sim-reason-text').textContent = res.reason;

                    const traceList = document.getElementById('sim-trace-list');
                    traceList.innerHTML = '';
                    (res.trace || []).forEach(t => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between rounded-lg border border-neutral-200/80 dark:border-neutral-800/80 bg-white dark:bg-neutral-950 px-3 py-2';
                        div.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-neutral-100 dark:bg-neutral-800 px-1.5 py-0.5 text-[10px] text-neutral-600 dark:text-neutral-400">Step ${t.step}</span>
                                <span class="text-neutral-800 dark:text-neutral-200 font-semibold">${t.rule}</span>
                            </div>
                            <span class="${t.passed ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-500'}">
                                ${t.passed ? 'PASS' : 'SKIP'} &middot; ${t.detail}
                            </span>
                        `;
                        traceList.appendChild(div);
                    });
                })
                .finally(() => {
                    simBtn.disabled = false;
                    simBtn.textContent = 'Simulate Decision';
                });
            }

            // Signature generator
            document.getElementById('generate-sig-btn')?.addEventListener('click', () => {
                const linkId = document.getElementById('sig-link-id').value;
                const rid = document.getElementById('sig-rid').value;

                fetch('{{ route("protected.debug.signature") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ link_id: linkId, rid: rid })
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('sig-result-box').classList.remove('hidden');
                    document.getElementById('generated-sig-text').textContent = data.signature;
                    const chal = document.getElementById('generated-challenge-url');
                    chal.href = data.challenge_url;
                    chal.textContent = data.challenge_url;
                    const ua = document.getElementById('generated-ua-url');
                    ua.href = data.user_agent_url;
                    ua.textContent = data.user_agent_url;
                });
            });
        });
    </script>
</body>
</html>
