<x-layouts.app title="Network Security — ternis.link stats">
    <x-slot:head><link rel="alternate" type="text/markdown" href="{{ route('pages.stats.security-md') }}"></x-slot:head>
    <x-ui.page-header title="Network Security" subtitle="Understand how the network evaluates and protects short links." />
    <x-stats.nav />
    <div data-stats-page="security">
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.stat :value="number_format($stats['threat_evaluations'])" label="Security evaluations" description="All recorded evaluations" :key="'threat_evaluations'" />
        <x-ui.stat :value="number_format($stats['threats_quarantined'])" label="Threats Quarantined" description="Recorded actions and legacy tagged links" :key="'threats_quarantined'" />
        <x-ui.stat :value="number_format($stats['threats_bot_protected'])" label="Bot protection enabled" description="Recorded protection actions" :key="'threats_bot_protected'" />
        <x-ui.stat :value="number_format($stats['threat_evaluations_today'])" label="Evaluations today" description="Since midnight, application timezone" :key="'threat_evaluations_today'" />
        <x-ui.stat :value="number_format($stats['threats_cleared'] ?? 0)" label="Benign destinations" description="Clean destinations cleared by AI" :key="'threats_cleared'" />
        <x-ui.stat :value="($stats['threats_avg_score'] ?? 0).'/100'" label="Average Threat Score" description="Cross-network threat severity index" :key="'threats_avg_score'" />
    </div>

    @if (!empty($breakdown['categories']))
        <x-ui.card title="AI Threat Triage Distribution" description="Classification breakdown across inspected destinations via TypeSafe AI." class="mb-6">
            {{-- Multi-segment distribution bar --}}
            <div class="mb-5 flex h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800" data-stats-security-bar>
                @foreach ($breakdown['categories'] as $catKey => $cat)
                    <div
                        class="{{ $catKey === 'malware' ? 'bg-rose-500' : ($catKey === 'phishing' ? 'bg-orange-500' : ($catKey === 'spam' ? 'bg-amber-400' : 'bg-emerald-500')) }}"
                        style="width: {{ max(1, $cat['percent']) }}%"
                        title="{{ $cat['label'] }}: {{ $cat['percent'] }}% ({{ number_format($cat['count']) }})"
                        data-cat-bar="{{ $catKey }}"
                    ></div>
                @endforeach
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($breakdown['categories'] as $catKey => $cat)
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-3.5 dark:border-neutral-800 dark:bg-neutral-900/60">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
                                <span class="h-2 w-2 rounded-full {{ $catKey === 'malware' ? 'bg-rose-500' : ($catKey === 'phishing' ? 'bg-orange-500' : ($catKey === 'spam' ? 'bg-amber-400' : 'bg-emerald-500')) }}"></span>
                                {{ $cat['label'] }}
                            </span>
                            <span class="font-mono text-neutral-900 dark:text-white" data-cat-percent="{{ $catKey }}">{{ $cat['percent'] }}%</span>
                        </div>
                        <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-white" data-cat-count="{{ $catKey }}">
                            {{ number_format($cat['count']) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <x-ui.card title="How these numbers are counted" description="Transparent totals, with historical limits.">
        <p class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">Quarantine totals include recorded quarantine actions and links carrying a legacy quarantine tag without a matching action. Repeat actions can include rescans; this is not a count of currently blocked unique links. Historical links deleted before logging cannot be reconstructed. Bot protection counts recorded enablement actions, not visitor challenges or verified visitors.</p>
        <p class="mt-4 text-sm text-neutral-600 dark:text-neutral-400">A human verification check confirms a visitor can continue. It does not certify that the destination is safe.</p>
    </x-ui.card>
    </div>
</x-layouts.app>
