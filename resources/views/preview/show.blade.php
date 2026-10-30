<x-layouts.app title="Link Preview — href.nz">
    <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full border-2 border-neutral-900 dark:border-white" aria-hidden="true">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5c0 4.5 3 7.6 7.5 9 4.5-1.4 7.5-4.5 7.5-9V6Z"/><path d="m9.2 12 2 2 3.6-4.2"/></svg>
        </span>
        <h1 class="mt-4 text-xl font-semibold">Link preview</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Nothing opens automatically — look first, then decide.</p>

        @if ($mode === 'slug' && $link)
            <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">
                Short link <code>https://{{ $link->domain->hostname }}/{{ $link->slug }}</code>
            </p>
        @endif

        <div class="mt-6 w-full rounded-xl border border-neutral-200 bg-white p-5 text-left dark:border-neutral-800 dark:bg-neutral-900">
            <p class="text-xs font-semibold tracking-widest text-neutral-500 uppercase dark:text-neutral-400">Goes to</p>
            <p class="mt-1 font-mono text-sm break-all">{{ $destination }}</p>

            <div class="mt-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <p class="text-xs font-semibold tracking-widest text-neutral-500 uppercase dark:text-neutral-400">Social preview</p>
                <p class="mt-2 font-semibold">{{ $og['title'] ?? $destination }}</p>
                @if(!empty($og['description']))<p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">{{ $og['description'] }}</p>@endif
                @if(!empty($og['image']))
                    <img src="{{ $og['image'] }}" alt="" class="mt-3 max-h-48 rounded-lg" loading="lazy">
                @else
                    <p class="mt-1 text-xs text-neutral-500">No custom image — shares fall back to the destination site.</p>
                @endif
            </div>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-neutral-500 dark:text-neutral-400">Host</dt>
                    <dd class="font-mono break-all">{{ $host !== '' ? $host : '—' }}</dd>
                </div>
                @if ($mode === 'slug' && $link)
                    <div class="flex justify-between gap-4">
                        <dt class="text-neutral-500 dark:text-neutral-400">Created</dt>
                        <dd>{{ $link->created_at?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-neutral-500 dark:text-neutral-400">Clicks so far</dt>
                        <dd>{{ number_format((int) $link->click_count) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-neutral-500 dark:text-neutral-400">Safety check</dt>
                    <dd class="text-right font-medium {{ ($threat && (in_array($threat->verdict, ['malware', 'phishing']) || $threat->action_taken === 'quarantined')) || $junkReasons !== [] ? 'text-red-600 dark:text-red-400 font-bold' : ($threat && $threat->verdict === 'spam' ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-emerald-600 dark:text-emerald-400') }}">
                        @if ($threat)
                            @if (in_array($threat->verdict, ['malware', 'phishing']) || $threat->action_taken === 'quarantined')
                                High Risk: {{ ucfirst($threat->verdict) }} ({{ $threat->threat_score }}/100)
                            @elseif ($threat->verdict === 'spam' || $threat->action_taken === 'bot_protected')
                                Suspicious / Bot Challenge ({{ $threat->threat_score }}/100)
                            @else
                                Verified Safe ({{ $threat->threat_score }}/100)
                            @endif
                        @else
                            {{ $junkReasons !== [] ? 'Flagged — see below' : 'No known risk patterns' }}
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($threat && (in_array($threat->verdict, ['malware', 'phishing']) || $threat->action_taken === 'quarantined'))
                <div class="mt-4 rounded-xl border-2 border-rose-600 bg-rose-50/80 p-4 text-left text-sm text-rose-900 dark:border-rose-500 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                    <div class="flex items-center gap-2 font-bold text-base">
                        <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Security Alert: Malicious or Deceptive Destination</span>
                    </div>
                    <p class="mt-1 text-xs text-rose-800 dark:text-rose-300">
                        This link was quarantined by automated AI security screening. It has been classified as <strong>{{ ucfirst($threat->verdict) }}</strong> with a critical threat score of <strong>{{ $threat->threat_score }}/100</strong>.
                    </p>
                    @if ($threat->reasoning)
                        <p class="mt-2 rounded bg-rose-100/80 p-2 font-mono text-[11px] text-rose-900 dark:bg-rose-900/40 dark:text-rose-200">
                            {{ $threat->reasoning }}
                        </p>
                    @endif
                </div>
            @elseif ($threat && $threat->verdict === 'safe')
                <div class="mt-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5 text-xs text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span><strong>TypeSafe AI Verified:</strong> No phishing, malware, or exploit payloads detected.</span>
                    </div>
                    <span class="font-mono font-bold">{{ $threat->threat_score }}/100</span>
                </div>
            @endif

            @if ($junkReasons !== [])
                <div class="mt-4 rounded-lg border-2 border-red-600 px-4 py-3 text-sm text-red-700 dark:border-red-400 dark:text-red-300" role="alert">
                    <strong>This destination matches automated-scan patterns.</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($junkReasons as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-1">Only continue if you trust it.</p>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                @if (($threat && (in_array($threat->verdict, ['malware', 'phishing']) || $threat->action_taken === 'quarantined')) || (!empty($link) && in_array('quarantine', $link->tags ?? [])))
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-neutral-200 px-4 py-2 text-sm font-semibold text-neutral-500 cursor-not-allowed dark:bg-neutral-800 dark:text-neutral-400" title="This link is quarantined and cannot be visited">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        <span>Destination Quarantined</span>
                    </span>
                @else
                    <x-ui.button href="{{ $destination }}" variant="primary" target="_blank" rel="noopener">Visit link</x-ui.button>
                @endif
                <x-ui.button
                    onclick="navigator.clipboard.writeText(@js($destination)).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy link', 2000); })"
                >Copy link</x-ui.button>
            </div>
        </div>

        <x-ui.button href="https://href.nz/" class="mt-6" variant="ghost">Back to href.nz</x-ui.button>
    </div>
</x-layouts.app>
