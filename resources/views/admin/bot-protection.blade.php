<x-layouts.admin title="Bot Protection & Gateway — Admin">
    <x-ui.page-header
        title="Bot Protection & Gateway"
        subtitle="Manage Cloudflare Turnstile challenges, edge user-agent screening, and zero-trust verification rules."
    >
        <x-slot:actions>
            <a
                href="{{ \App\Support\DomainUrls::protected('/debug') }}"
                target="_blank"
                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-neutral-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 transition-colors"
            >
                <span>Live Gateway Debugger ↗</span>
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Overview Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat :value="number_format($metrics['bot_protected_links'])" label="Bot-Protected Links" />
        <x-ui.stat :value="number_format($metrics['bot_protected_threats'])" label="Turnstile Mitigations" />
        <x-ui.stat :value="number_format($metrics['quarantined_threats'])" label="Threats Quarantined" />
        <x-ui.stat :value="count($config['suspicious_user_agents'])" label="Active UA Filters" />
    </div>

    {{-- Section 1: Protection Decision Sandbox --}}
    <x-ui.card class="mb-8" title="Protection Decision Sandbox" description="Simulate and test how the gateway evaluates any short link slug against different visitor signatures.">
        <form method="POST" action="{{ route('admin.bot-protection.simulate') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="link_slug_or_id" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                        Link Slug or ID (Optional)
                    </label>
                    <input
                        type="text"
                        name="link_slug_or_id"
                        id="link_slug_or_id"
                        placeholder="e.g. promo or leave blank"
                        value="{{ session('simulation_result.input.link_slug_or_id') ?? '' }}"
                        class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2 text-xs text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                </div>

                <div>
                    <label for="user_agent" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                        Visitor User-Agent
                    </label>
                    <input
                        type="text"
                        name="user_agent"
                        id="user_agent"
                        required
                        value="{{ session('simulation_result.input.user_agent') ?? request()->userAgent() ?? 'curl/8.4.0' }}"
                        class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2 text-xs font-mono text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                </div>

                <div>
                    <label for="has_cookie" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                        Simulate Human Cookie
                    </label>
                    <select
                        name="has_cookie"
                        id="has_cookie"
                        class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="auto" @selected(session('simulation_result.input.has_cookie') === 'auto')>Use Current Browser State</option>
                        <option value="no" @selected(session('simulation_result.input.has_cookie') === 'no')>Simulate Unverified (No Cookie)</option>
                        <option value="yes" @selected(session('simulation_result.input.has_cookie') === 'yes')>Simulate Verified Human (Cookie Present)</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                    <span class="font-medium">Presets:</span>
                    <button type="button" class="preset-btn rounded-md border border-neutral-200 px-2 py-0.5 hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36">Consumer Chrome</button>
                    <button type="button" class="preset-btn rounded-md border border-neutral-200 px-2 py-0.5 hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="curl/8.4.0">cURL 8.4</button>
                    <button type="button" class="preset-btn rounded-md border border-neutral-200 px-2 py-0.5 hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="python-requests/2.31.0">Python Requests</button>
                    <button type="button" class="preset-btn rounded-md border border-neutral-200 px-2 py-0.5 hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">Googlebot</button>
                    <button type="button" class="preset-btn rounded-md border border-neutral-200 px-2 py-0.5 hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="sqlmap/1.7.11#stable">sqlmap</button>
                </div>

                <button
                    type="submit"
                    class="cursor-pointer rounded-xl bg-neutral-900 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 transition-colors"
                >
                    Run Decision Simulation
                </button>
            </div>
        </form>

        {{-- Simulation Results --}}
        @if ($sim = session('simulation_result'))
            <div class="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50/80 p-5 dark:border-neutral-800 dark:bg-neutral-950/60">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-3 dark:border-neutral-800">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Simulation Outcome</span>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span @class([
                                'rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $sim['result']['decision'] === 'ALLOW',
                                'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' => $sim['result']['decision'] === 'CHALLENGE_TURNSTILE',
                                'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' => $sim['result']['decision'] === 'CHALLENGE_USER_AGENT',
                            ])>
                                {{ $sim['result']['decision'] }}
                            </span>
                            <span class="font-display font-semibold text-neutral-900 dark:text-white text-sm">
                                &mdash; {{ $sim['result']['action'] }}
                            </span>
                        </div>
                    </div>
                    @if ($sim['link'])
                        <div class="text-right text-xs">
                            <span class="text-neutral-500">Target Link:</span>
                            <span class="font-mono font-semibold text-neutral-800 dark:text-neutral-200">
                                {{ $sim['link']->domain->hostname ?? 'link' }}/{{ $sim['link']->slug }}
                            </span>
                            <div class="text-[11px] text-neutral-400">
                                Bot Protection: {{ $sim['link']->hasBotProtection() ? 'ON' : 'OFF' }} &middot;
                                Allows Bots: {{ $sim['link']->allowsBots() ? 'YES' : 'NO' }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Decision Rationale:</span>
                    <p class="mt-1 text-xs text-neutral-600 dark:text-neutral-400">{{ $sim['result']['reason'] }}</p>

                    <span class="mt-4 block text-xs font-semibold text-neutral-700 dark:text-neutral-300">Rule Evaluation Trace:</span>
                    <div class="mt-2 space-y-1.5 font-mono text-xs">
                        @foreach ($sim['result']['trace'] as $t)
                            <div class="flex items-start gap-2.5 rounded-lg border border-neutral-200/60 bg-white p-2.5 dark:border-neutral-800/80 dark:bg-neutral-900">
                                <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-bold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                    Step {{ $t['step'] }}
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-neutral-800 dark:text-neutral-200">{{ $t['rule'] }}</span>
                                        <span class="{{ $t['passed'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400' }}">
                                            {{ $t['passed'] ? 'PASS' : 'SKIP / NEGATIVE' }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-neutral-500">{{ $t['detail'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- Section 2: Gateway Configuration & Standards --}}
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Operational Parameters &amp; Standards">
            <dl class="divide-y divide-neutral-100 text-xs dark:divide-neutral-800">
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-neutral-500">Master Protection Switch</dt>
                    <dd class="font-semibold {{ $config['enabled'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400' }}">
                        {{ $config['enabled'] ? 'Enabled' : 'Disabled' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-neutral-500">Cloudflare Turnstile Key</dt>
                    <dd class="font-semibold {{ $config['turnstile_key_present'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500' }}">
                        {{ $config['turnstile_key_present'] ? 'Configured (Active)' : 'Missing (Fail-Open Fallback)' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-neutral-500">Edge UA Screening</dt>
                    <dd class="font-semibold {{ $config['filter_suspicious_ua'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400' }}">
                        {{ $config['filter_suspicious_ua'] ? 'Active (Substring Pattern Matching)' : 'Disabled' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-neutral-500">Verification Memory Lifetime</dt>
                    <dd class="font-semibold text-neutral-800 dark:text-neutral-200">
                        {{ $config['remember_duration_minutes'] }} minutes (Encrypted HMAC)
                    </dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-neutral-500">Rate Limit Threshold</dt>
                    <dd class="font-semibold text-neutral-800 dark:text-neutral-200">
                        {{ $config['rate_limit_max'] }} verification attempts / min per IP
                    </dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-neutral-500">Challenge Signature Window</dt>
                    <dd class="font-semibold text-neutral-800 dark:text-neutral-200">
                        900 seconds (15-minute anti-replay protection)
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Active Suspicious Client Signatures" description="User-Agent substring patterns automatically challenged at the gateway edge:">
            <div class="flex flex-wrap gap-1.5 pt-1">
                @foreach ($config['suspicious_user_agents'] as $pattern)
                    <span class="rounded-lg border border-neutral-200 bg-neutral-100 px-2 py-1 font-mono text-[11px] text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                        {{ $pattern }}
                    </span>
                @endforeach
            </div>
            <p class="mt-4 text-[11px] leading-relaxed text-neutral-500">
                Requests matching these signatures are gracefully isolated to a dedicated challenge gate (<code class="font-mono">/user-agent</code>) with Request ID (<code class="font-mono">rid</code>) tracking, allowing humans using automation tools to verify through Turnstile.
            </p>
        </x-ui.card>
    </div>

    {{-- Section 3: Recent Protected Links --}}
    @if ($recentProtectedLinks->isNotEmpty())
        <x-ui.card title="Recent Protected Links" description="Active links requiring Cloudflare Turnstile verification before redirecting:">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-neutral-200 text-neutral-400 dark:border-neutral-800">
                        <tr>
                            <th class="pb-2 font-semibold">Short URL</th>
                            <th class="pb-2 font-semibold">Destination URL</th>
                            <th class="pb-2 font-semibold">Created</th>
                            <th class="pb-2 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800/60 font-mono">
                        @foreach ($recentProtectedLinks as $link)
                            <tr>
                                <td class="py-2.5 font-semibold text-neutral-900 dark:text-white">
                                    {{ $link->domain->hostname ?? 'link' }}/{{ $link->slug }}
                                </td>
                                <td class="py-2.5 text-neutral-500 truncate max-w-xs" title="{{ $link->destination_url }}">
                                    {{ $link->destination_url }}
                                </td>
                                <td class="py-2.5 text-neutral-400 text-[11px]">
                                    {{ $link->created_at->diffForHumans() }}
                                </td>
                                <td class="py-2.5 text-right font-sans">
                                    <a
                                        href="{{ \App\Support\DomainUrls::protected('/debug?slug=' . $link->slug) }}"
                                        target="_blank"
                                        class="rounded-md border border-neutral-200 px-2 py-1 text-[11px] font-semibold text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                    >
                                        Debug Link ↗
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <script>
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const ua = btn.getAttribute('data-ua');
                if (ua) {
                    document.getElementById('user_agent').value = ua;
                }
            });
        });
    </script>
</x-layouts.admin>
