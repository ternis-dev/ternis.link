<x-layouts.admin title="AI Threat Inspector & Security — Admin">
    <x-ui.page-header
        title="AI Threat Inspector"
        subtitle="Real-time TypeSafe AI model triage for malware, phishing, scam payloads, and automated burst link attacks."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-semibold dark:border-neutral-800 dark:bg-neutral-900">
                <span class="h-2 w-2 rounded-full {{ $stats['ai_enabled'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                Model: <code class="font-mono text-violet-600 dark:text-violet-400">{{ $stats['model_name'] }}</code>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-semibold dark:border-neutral-800 dark:bg-neutral-900">
                <span class="h-2 w-2 rounded-full {{ $stats['bypassed_admin_keys'] > 0 ? 'bg-violet-500' : 'bg-neutral-400' }}"></span>
                AI-Exempt Keys: <strong class="text-neutral-900 dark:text-white">{{ $stats['bypassed_admin_keys'] }}</strong>
            </span>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Overview Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat :value="number_format($stats['total_evaluations'])" label="Total Scans (All Time)" />
        <x-ui.stat :value="number_format($stats['total_quarantined'])" label="Malware / Phishing Quarantined" />
        <x-ui.stat :value="number_format($stats['total_bot_protected'])" label="Bot Mitigation Protected" />
        <x-ui.stat :value="$stats['avg_score'].'/100'" label="Average Threat Score" />
    </div>

    {{-- Live Testing Sandbox --}}
    <x-ui.card class="mb-8" title="TypeSafe AI Sandbox" description="Test any URL directly against the TypeSafe AI model to inspect the live verdict, probabilities, and scores.">
        <form method="POST" action="{{ route('admin.threats.test') }}" class="space-y-4">
            @csrf
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <input
                        type="url"
                        name="test_url"
                        required
                        placeholder="https://not-a-scam.com/download.exe"
                        value="{{ session('test_result.url') ?? '' }}"
                        class="w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                </div>
                <button
                    type="submit"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-500 transition-colors"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Inspect with TypeSafe AI</span>
                </button>
            </div>
        </form>

        @if ($test = session('test_result'))
            <div class="mt-6 rounded-2xl border border-violet-200 bg-violet-50/50 p-5 dark:border-violet-900/50 dark:bg-violet-950/20">
                @if (isset($test['error']))
                    <p class="text-sm font-semibold text-rose-600 dark:text-rose-400">{{ $test['error'] }}</p>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-violet-200 pb-3 dark:border-violet-900/60">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-violet-600 dark:text-violet-400">Live AI Verdict</span>
                            <h3 class="font-display text-xl font-bold text-neutral-900 dark:text-white">{{ $test['url'] }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide',
                                'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300' => in_array($test['verdict'], ['malware', 'phishing']),
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' => $test['verdict'] === 'spam',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => $test['verdict'] === 'safe',
                            ])>
                                {{ $test['verdict'] }} ({{ round($test['confidence'] * 100) }}% confidence)
                            </span>
                            @if (!empty($test['recommended_action']))
                                <span @class([
                                    'rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide',
                                    'bg-rose-700 text-white' => $test['recommended_action'] === 'quarantine',
                                    'bg-amber-600 text-white' => $test['recommended_action'] === 'bot_protect',
                                    'bg-emerald-700 text-white' => $test['recommended_action'] === 'allow',
                                ])>
                                    Action: {{ $test['recommended_action'] }}
                                </span>
                            @endif
                            <span class="rounded-lg bg-neutral-900 px-2.5 py-1 text-xs font-bold text-white dark:bg-white dark:text-neutral-900">
                                Score: {{ $test['threat_score'] }}/100
                            </span>
                        </div>
                    </div>

                    {{-- Probabilities breakdown --}}
                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($test['probabilities'] as $cat => $prob)
                            <div class="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="uppercase tracking-wider text-neutral-500">{{ $cat }}</span>
                                    <span class="text-neutral-900 dark:text-white font-mono">{{ round($prob * 100, 1) }}%</span>
                                </div>
                                <div class="mt-2 h-1.5 w-full rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                                    <div class="h-full rounded-full {{ $cat === 'malware' || $cat === 'phishing' ? 'bg-rose-500' : ($cat === 'spam' ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $prob * 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Collapsible prompt and metadata --}}
                    <details class="mt-4 rounded-xl border border-neutral-200 bg-white p-3 text-xs dark:border-neutral-800 dark:bg-neutral-900">
                        <summary class="cursor-pointer font-semibold text-neutral-600 dark:text-neutral-300">View State Prompt &amp; Raw JSON</summary>
                        <pre class="mt-3 overflow-x-auto rounded-lg bg-neutral-950 p-3 font-mono text-[11px] text-neutral-200">{!! json_encode($test, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}</pre>
                    </details>
                @endif
            </div>
        @endif
    </x-ui.card>

    {{-- Filter Bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.threats') }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ !request('verdict') && !request('action') && !request('bypassed') ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">All</a>
            <a href="{{ route('admin.threats', ['verdict' => 'malware']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('verdict') === 'malware' ? 'bg-rose-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">Malware</a>
            <a href="{{ route('admin.threats', ['verdict' => 'phishing']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('verdict') === 'phishing' ? 'bg-orange-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">Phishing</a>
            <a href="{{ route('admin.threats', ['verdict' => 'spam']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('verdict') === 'spam' ? 'bg-amber-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">Spam</a>
            <a href="{{ route('admin.threats', ['action' => 'quarantined']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('action') === 'quarantined' ? 'bg-rose-700 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">Quarantined</a>
            <a href="{{ route('admin.threats', ['action' => 'bot_protected']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('action') === 'bot_protected' ? 'bg-blue-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">Bot Protected</a>
            <a href="{{ route('admin.threats', ['bypassed' => request('bypassed') ? null : 1]) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('bypassed') ? 'bg-violet-600 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">Admin Key Exempt</a>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.threats') }}" class="flex items-center gap-2">
                @if (request('verdict')) <input type="hidden" name="verdict" value="{{ request('verdict') }}"> @endif
                @if (request('action')) <input type="hidden" name="action" value="{{ request('action') }}"> @endif
                @if (request('bypassed')) <input type="hidden" name="bypassed" value="1"> @endif
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search URL or host..."
                    class="rounded-xl border border-neutral-300 bg-white px-3 py-1.5 text-xs text-neutral-900 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                >
                <button type="submit" class="rounded-xl bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white dark:bg-white dark:text-neutral-900">Search</button>
            </form>

            <div class="flex items-center gap-1.5 border-l border-neutral-200 pl-2 dark:border-neutral-800">
                <a
                    href="{{ route('admin.threats.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                    title="Export filtered security audit logs as CSV"
                    class="inline-flex items-center gap-1 rounded-xl border border-neutral-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-neutral-700 hover:border-neutral-900 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:border-white dark:hover:text-white transition-colors"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    <span>CSV</span>
                </a>
                <a
                    href="{{ route('admin.threats.export', array_merge(request()->query(), ['format' => 'json'])) }}"
                    title="Export filtered security audit logs as JSON"
                    class="inline-flex items-center gap-1 rounded-xl border border-neutral-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-neutral-700 hover:border-neutral-900 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:border-white dark:hover:text-white transition-colors"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    <span>JSON</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Evaluations Log Table --}}
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-xs font-semibold text-neutral-500 uppercase tracking-wider dark:border-neutral-800">
                        <th class="py-3 px-3">Destination URL &amp; Host</th>
                        <th class="py-3 px-3">Verdict</th>
                        <th class="py-3 px-3">Score</th>
                        <th class="py-3 px-3">Action</th>
                        <th class="py-3 px-3">Time</th>
                        <th class="py-3 px-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800" x-data="{ openRow: null }">
                    @forelse ($evaluations as $eval)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors">
                            <td class="py-3 px-3 max-w-xs truncate">
                                <p class="font-semibold text-neutral-900 dark:text-white truncate" title="{{ $eval->destination_url }}">{{ $eval->destination_url }}</p>
                                <p class="text-xs text-neutral-400 font-mono">{{ $eval->destination_host }}</p>
                            </td>
                            <td class="py-3 px-3">
                                <span @class([
                                    'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold uppercase',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300' => in_array($eval->verdict, ['malware', 'phishing']),
                                    'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' => $eval->verdict === 'spam',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => $eval->verdict === 'safe',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300' => $eval->verdict === 'burst_attack',
                                ])>
                                    {{ $eval->verdict }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-mono font-bold text-xs">{{ $eval->threat_score }}/100</span>
                            </td>
                            <td class="py-3 px-3">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-medium',
                                    'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300 font-bold' => $eval->action_taken === 'quarantined',
                                    'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' => $eval->action_taken === 'bot_protected',
                                    'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400' => $eval->action_taken === 'cleared',
                                ])>
                                    {{ $eval->action_taken }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-xs text-neutral-400 whitespace-nowrap">
                                {{ $eval->created_at->diffForHumans() }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button
                                    type="button"
                                    @click="openRow = (openRow === '{{ $eval->id }}' ? null : '{{ $eval->id }}')"
                                    class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-semibold text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 transition-colors"
                                >
                                    <span x-text="openRow === '{{ $eval->id }}' ? 'Close' : 'Inspect'"></span>
                                </button>
                            </td>
                        </tr>
                        {{-- Expandable Details Row --}}
                        <tr x-show="openRow === '{{ $eval->id }}'" x-cloak class="bg-neutral-50 dark:bg-neutral-800/60">
                            <td colspan="6" class="p-4">
                                <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900 space-y-3">
                                    <div class="flex items-center justify-between text-xs font-bold border-b border-neutral-100 pb-2 dark:border-neutral-800">
                                        <span>Evaluation ID: <code class="font-mono text-violet-600 dark:text-violet-400">{{ $eval->id }}</code></span>
                                        <span>Confidence: <code class="font-mono text-neutral-900 dark:text-white">{{ round($eval->confidence * 100) }}%</code></span>
                                        <span>Model: <code class="font-mono">{{ $eval->metadata['model'] ?? 'jev-latest' }}</code></span>
                                    </div>
                                    <p class="text-xs text-neutral-600 dark:text-neutral-300"><strong>Reasoning:</strong> {{ $eval->reasoning }}</p>
                                    @if (!empty($eval->metadata['probabilities']))
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 pt-1">
                                            @foreach ($eval->metadata['probabilities'] as $k => $v)
                                                <div class="rounded-lg bg-neutral-100 p-2 text-center dark:bg-neutral-800">
                                                    <span class="block text-[10px] uppercase font-bold text-neutral-500">{{ $k }}</span>
                                                    <span class="font-mono text-xs font-bold">{{ round($v * 100, 1) }}%</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <details class="text-[11px]">
                                        <summary class="cursor-pointer text-violet-600 font-semibold dark:text-violet-400">View Full Raw Metadata JSON</summary>
                                        <pre class="mt-2 overflow-x-auto rounded bg-neutral-950 p-2.5 font-mono text-neutral-200">{!! json_encode($eval->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}</pre>
                                    </details>

                                    @if ($eval->link)
                                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 pt-3 dark:border-neutral-800">
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="text-neutral-500">Short Link:</span>
                                                <span class="font-mono font-semibold text-violet-600 dark:text-violet-400">/{{ $eval->link->slug }}</span>
                                                <span @class([
                                                    'rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => $eval->link->is_active,
                                                    'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300' => ! $eval->link->is_active,
                                                ])>
                                                    {{ $eval->link->is_active ? 'Active' : 'Inactive / Quarantined' }}
                                                </span>
                                                @if ($eval->link->apiKey?->shouldBypassAi())
                                                    <span class="rounded bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase text-violet-800 dark:bg-violet-900/40 dark:text-violet-300">
                                                        Admin Key AI-Bypass
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <form method="POST" action="{{ route('admin.threats.rescan', $eval->link) }}" class="inline">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 transition-colors"
                                                    >
                                                        <svg class="h-3.5 w-3.5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                                                        <span>Re-scan Link</span>
                                                    </button>
                                                </form>
                                                @if (! $eval->link->is_active || in_array('quarantine', $eval->link->tags ?? []))
                                                    <form method="POST" action="{{ route('admin.threats.unquarantine', $eval->link) }}" class="inline">
                                                        @csrf
                                                        <button
                                                            type="submit"
                                                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-500 shadow-sm transition-colors"
                                                        >
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                                                            <span>Unquarantine &amp; Restore</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-sm text-neutral-500">
                                No threat evaluations recorded yet. Run a live check in the sandbox above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($evaluations->hasPages())
            <div class="mt-6 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                {{ $evaluations->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.admin>
