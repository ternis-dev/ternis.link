<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Gateway Diagnostics — protected.ternis.link</title>
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
<body class="flex min-h-screen flex-col bg-neutral-900 text-neutral-100 antialiased selection:bg-amber-500 selection:text-neutral-950">

    {{-- Admin Debug Header --}}
    <header class="sticky top-0 z-40 border-b border-neutral-800 bg-neutral-950/90 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('protected.index') }}" class="font-display text-base font-bold tracking-tight text-white hover:opacity-80 transition-opacity sm:text-lg">
                    protected<span class="text-neutral-500">.ternis.link</span>
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-mono font-bold text-amber-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    ADMIN DEBUG MODE
                </span>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('protected.index') }}"
                    class="rounded-lg border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-xs font-semibold text-neutral-200 hover:bg-neutral-700 transition-colors"
                >
                    ← Gateway Landing
                </a>
                <a
                    href="https://admin.ternis.link/threats"
                    class="hidden sm:inline-flex rounded-lg border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-xs font-semibold text-neutral-200 hover:bg-neutral-700 transition-colors"
                >
                    Admin Threat Inspector ↗
                </a>

                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-700 text-neutral-400 transition-colors hover:bg-neutral-800"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col px-4 py-8 sm:px-6 sm:py-10 space-y-8">

        {{-- Top Admin Banner --}}
        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-xl font-bold text-white flex items-center gap-2">
                        <span>⚡ Real-Time Edge Gateway Diagnostics</span>
                    </h1>
                    <p class="mt-1 text-xs text-neutral-400">
                        Admin session authenticated for user: <strong class="text-white">{{ auth()->user()->email ?? 'Admin' }}</strong>. This console displays live request inspection, HMAC signing states, and automated request analysis using AI.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        id="refresh-page-btn"
                        onclick="window.location.reload()"
                        class="cursor-pointer rounded-xl border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-xs font-semibold text-neutral-200 hover:bg-neutral-700"
                    >
                        ↻ Refresh Snapshot
                    </button>
                </div>
            </div>
        </div>

        {{-- Section 1: AI Request Analysis Engine (Real-Time AI Request Analysis) --}}
        <section class="rounded-2xl border border-violet-500/30 bg-neutral-950 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-800 pb-4">
                <div>
                    <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-violet-400">AI Request Intelligence</span>
                    <h2 class="mt-0.5 font-display text-xl font-bold text-white">Live AI Request Analyzer</h2>
                </div>
                <span class="rounded-lg border border-violet-500/30 bg-violet-500/10 px-2.5 py-1 text-xs font-mono text-violet-300">
                    Model: {{ $diagnostics['configuration']['ai_model'] }}
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Input parameters to test --}}
                <div class="space-y-4">
                    <div>
                        <label for="ai-ua-input" class="block text-xs font-semibold uppercase tracking-wider text-neutral-400">
                            Client User-Agent to Evaluate
                        </label>
                        <textarea
                            id="ai-ua-input"
                            rows="3"
                            class="mt-1.5 w-full rounded-xl border border-neutral-700 bg-neutral-900 px-3 py-2 font-mono text-xs text-neutral-200 placeholder:text-neutral-500 focus:border-violet-500 focus:outline-none"
                        >{{ $diagnostics['client']['user_agent'] }}</textarea>
                    </div>

                    <div>
                        <label for="ai-path-input" class="block text-xs font-semibold uppercase tracking-wider text-neutral-400">
                            Requested Target Path
                        </label>
                        <input
                            type="text"
                            id="ai-path-input"
                            value="/"
                            class="mt-1.5 w-full rounded-xl border border-neutral-700 bg-neutral-900 px-3 py-2 font-mono text-xs text-neutral-200 focus:border-violet-500 focus:outline-none"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="run-ai-eval-btn"
                            class="cursor-pointer rounded-xl bg-violet-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-violet-500 transition-colors"
                        >
                            Analyze Request with AI
                        </button>
                        <button
                            type="button"
                            id="reset-ua-btn"
                            class="cursor-pointer rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-xs font-semibold text-neutral-300 hover:bg-neutral-700"
                        >
                            Reset to Current Request
                        </button>
                    </div>
                </div>

                {{-- Live Analysis Outcome --}}
                <div class="rounded-xl border border-neutral-800 bg-neutral-900/60 p-5">
                    <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-neutral-400">Evaluation Outcome</span>

                    <div class="mt-3 flex items-center justify-between gap-3 border-b border-neutral-800 pb-3">
                        <div>
                            <span class="text-xs text-neutral-400">Classification:</span>
                            <div class="mt-0.5">
                                <span id="ai-verdict-display" class="font-display text-lg font-bold text-white">
                                    {{ $diagnostics['ai_analysis']['verdict'] }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-neutral-400">Gateway Action:</span>
                            <div class="mt-0.5">
                                <span id="ai-action-display" class="rounded-lg bg-neutral-800 px-2.5 py-1 text-xs font-bold font-mono">
                                    {{ $diagnostics['ai_analysis']['recommended_action'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div>
                            <div class="flex items-center justify-between text-xs text-neutral-400">
                                <span>Bot Threat Score:</span>
                                <span id="ai-score-display" class="font-mono font-bold text-white">
                                    {{ $diagnostics['ai_analysis']['score'] }}/100
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 w-full rounded-full bg-neutral-800 overflow-hidden">
                                <div
                                    id="ai-score-bar"
                                    class="h-full bg-violet-500 transition-all duration-300"
                                    style="width: {{ $diagnostics['ai_analysis']['score'] }}%"
                                ></div>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs text-neutral-400">Evaluation Mode:</span>
                            <span id="ai-mode-display" class="ml-2 font-mono text-xs text-amber-400">
                                {{ $diagnostics['ai_analysis']['mode'] }}
                            </span>
                        </div>

                        <div>
                            <span class="text-xs text-neutral-400">Signal Reasons:</span>
                            <ul id="ai-reasons-display" class="mt-1 list-disc list-inside text-xs text-neutral-300 space-y-0.5">
                                @foreach ($diagnostics['ai_analysis']['reasons'] as $r)
                                    <li>{{ $r }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 2: Request Inspector & Client IP Analysis --}}
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Client IP & Transport --}}
            <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                <h3 class="font-display text-sm font-bold text-white">Client IP &amp; Transport</h3>
                <dl class="mt-4 space-y-2.5 text-xs font-mono">
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Resolved IP:</dt>
                        <dd class="text-emerald-400 font-bold">{{ $diagnostics['client']['ip'] }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">REMOTE_ADDR:</dt>
                        <dd class="text-neutral-300">{{ $diagnostics['client']['remote_addr'] ?? 'n/a' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">CF-Connecting-IP:</dt>
                        <dd class="text-neutral-300">{{ $diagnostics['client']['cf_connecting_ip'] ?? 'n/a' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">X-Forwarded-For:</dt>
                        <dd class="text-neutral-300 truncate max-w-[150px]">{{ $diagnostics['client']['x_forwarded_for'] ?? 'n/a' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Protocol / Scheme:</dt>
                        <dd class="text-neutral-300">{{ $diagnostics['client']['protocol'] }} &middot; {{ $diagnostics['client']['scheme'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-400">TLS Secure:</dt>
                        <dd class="{{ $diagnostics['client']['is_secure'] ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $diagnostics['client']['is_secure'] ? 'YES' : 'NO' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Verification & Session State --}}
            <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                <h3 class="font-display text-sm font-bold text-white">Human Verification State</h3>
                <dl class="mt-4 space-y-2.5 text-xs font-mono">
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Is Human Verified:</dt>
                        <dd class="{{ $diagnostics['verification']['is_human_verified'] ? 'text-emerald-400 font-bold' : 'text-amber-400' }}">
                            {{ $diagnostics['verification']['is_human_verified'] ? 'TRUE (Exempt)' : 'FALSE (Will Challenge)' }}
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Session Verified At:</dt>
                        <dd class="text-neutral-300">
                            {{ $diagnostics['verification']['session_verified_at'] ? date('H:i:s', $diagnostics['verification']['session_verified_at']) : 'None' }}
                        </dd>
                    </div>
                    @if ($c = $diagnostics['verification']['cookie_details'])
                        <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                            <dt class="text-neutral-400">Cookie Issued At:</dt>
                            <dd class="text-neutral-300">{{ $c['verified_at'] ?? 'n/a' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                            <dt class="text-neutral-400">Cookie TTL Remaining:</dt>
                            <dd class="text-emerald-400 font-bold">{{ $c['ttl_remaining_seconds'] ?? 0 }}s</dd>
                        </div>
                    @else
                        <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                            <dt class="text-neutral-400">HMAC Cookie:</dt>
                            <dd class="text-neutral-500">Not Present</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Rate Limiting & Bucket Control --}}
            <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-sm font-bold text-white">Rate Limit Status</h3>
                    <button
                        type="button"
                        id="clear-rate-btn"
                        class="cursor-pointer text-[11px] font-mono text-amber-400 underline hover:text-amber-300"
                    >
                        Clear Rate Limits
                    </button>
                </div>
                <dl class="mt-4 space-y-2.5 text-xs font-mono">
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Attempts Used:</dt>
                        <dd class="text-white font-bold">{{ $diagnostics['rate_limiting']['attempts_used'] }} / {{ $diagnostics['rate_limiting']['max_attempts'] }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Attempts Remaining:</dt>
                        <dd class="text-emerald-400 font-bold">{{ $diagnostics['rate_limiting']['remaining'] }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-neutral-800/60 pb-1.5">
                        <dt class="text-neutral-400">Lockout Countdown:</dt>
                        <dd class="text-neutral-300">{{ $diagnostics['rate_limiting']['available_in_seconds'] }}s</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-400">Bucket Key:</dt>
                        <dd class="text-neutral-400 truncate max-w-[140px]">{{ $diagnostics['rate_limiting']['key'] }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Section 3: Challenge Signature Generator & Simulator --}}
        <section class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
            <h3 class="font-display text-base font-bold text-white">Challenge Signature Generator &amp; Sandbox</h3>
            <p class="mt-1 text-xs text-neutral-400">
                Generate and verify HMAC-SHA256 signatures for challenge URLs without modifying production links.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="sig-link-id" class="block text-xs font-semibold text-neutral-400">Link ID:</label>
                    <input
                        type="text"
                        id="sig-link-id"
                        value="test_link_123"
                        class="mt-1 w-full rounded-xl border border-neutral-700 bg-neutral-900 px-3 py-2 text-xs font-mono text-white"
                    >
                </div>
                <div>
                    <label for="sig-rid" class="block text-xs font-semibold text-neutral-400">Request ID (rid):</label>
                    <input
                        type="text"
                        id="sig-rid"
                        value="rid_{{ strtolower((string) \Illuminate\Support\Str::ulid()) }}"
                        class="mt-1 w-full rounded-xl border border-neutral-700 bg-neutral-900 px-3 py-2 text-xs font-mono text-white"
                    >
                </div>
                <div class="flex items-end">
                    <button
                        type="button"
                        id="generate-sig-btn"
                        class="w-full cursor-pointer rounded-xl bg-neutral-800 border border-neutral-700 px-4 py-2 text-xs font-bold text-white hover:bg-neutral-700 transition-colors"
                    >
                        Generate Challenge URLs
                    </button>
                </div>
            </div>

            <div id="sig-result-box" class="mt-4 hidden rounded-xl border border-neutral-800 bg-neutral-900/80 p-4 font-mono text-xs space-y-2">
                <div>
                    <span class="text-neutral-500">HMAC-SHA256 Signature:</span>
                    <p id="generated-sig-text" class="text-emerald-400 break-all font-bold"></p>
                </div>
                <div class="pt-2 border-t border-neutral-800">
                    <span class="text-neutral-500">Simulated Challenge URL:</span>
                    <p><a id="generated-challenge-url" href="#" target="_blank" class="text-amber-400 underline break-all hover:text-amber-300"></a></p>
                </div>
                <div class="pt-2 border-t border-neutral-800">
                    <span class="text-neutral-500">Simulated Suspicious UA URL:</span>
                    <p><a id="generated-ua-url" href="#" target="_blank" class="text-amber-400 underline break-all hover:text-amber-300"></a></p>
                </div>
            </div>
        </section>

        {{-- Section 4: Raw Incoming HTTP Request Headers Table --}}
        <section class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
            <h3 class="font-display text-base font-bold text-white">Incoming Request Headers</h3>
            <p class="mt-1 text-xs text-neutral-400">Exact headers delivered to this application request from edge reverse proxy.</p>

            <div class="mt-4 max-h-80 overflow-y-auto rounded-xl border border-neutral-800">
                <table class="w-full text-left font-mono text-xs">
                    <thead class="sticky top-0 bg-neutral-900 text-neutral-400 border-b border-neutral-800">
                        <tr>
                            <th class="p-3">Header Name</th>
                            <th class="p-3">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-800/60">
                        @foreach ($diagnostics['headers'] as $name => $values)
                            <tr class="hover:bg-neutral-900/40">
                                <td class="p-3 text-neutral-300 font-semibold align-top">{{ $name }}</td>
                                <td class="p-3 text-neutral-400 break-all">{{ implode(', ', $values) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <footer class="border-t border-neutral-800 py-6 text-center text-xs text-neutral-500">
        protected.ternis.link &mdash; Admin Diagnostics &middot; Session Authenticated
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // AI Analyzer execution
            const evalBtn = document.getElementById('run-ai-eval-btn');
            const uaInput = document.getElementById('ai-ua-input');
            const pathInput = document.getElementById('ai-path-input');

            evalBtn?.addEventListener('click', () => {
                evalBtn.disabled = true;
                evalBtn.textContent = 'Evaluating...';

                fetch('{{ route("protected.debug.analyze") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        user_agent: uaInput.value,
                        path: pathInput.value,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('ai-verdict-display').textContent = (data.verdict || 'unknown').replace('_', ' ');
                    document.getElementById('ai-action-display').textContent = data.recommended_action || 'ALLOW';
                    document.getElementById('ai-score-display').textContent = (data.score || 0) + '/100';
                    document.getElementById('ai-score-bar').style.width = (data.score || 0) + '%';
                    document.getElementById('ai-mode-display').textContent = data.mode || 'heuristic';

                    const reasonsEl = document.getElementById('ai-reasons-display');
                    reasonsEl.innerHTML = '';
                    (data.reasons || []).forEach(r => {
                        const li = document.createElement('li');
                        li.textContent = r;
                        reasonsEl.appendChild(li);
                    });
                })
                .catch(err => {
                    alert('Error: ' + err);
                })
                .finally(() => {
                    evalBtn.disabled = false;
                    evalBtn.textContent = 'Analyze Request with AI';
                });
            });

            // Reset UA button
            document.getElementById('reset-ua-btn')?.addEventListener('click', () => {
                uaInput.value = @json($diagnostics['client']['user_agent']);
                pathInput.value = '/';
            });

            // Clear rate limits button
            document.getElementById('clear-rate-btn')?.addEventListener('click', () => {
                if (!confirm('Clear rate limit buckets for this IP?')) return;

                fetch('{{ route("protected.debug.clear-ratelimit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ ip: @json($diagnostics['client']['ip']) })
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.message);
                    window.location.reload();
                });
            });

            // Signature generator button
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
