@php
    $isDe = ($locale ?? 'en') === 'de';
    $title = $isDe ? 'Link-Sicherheits- & Bedrohungsprüfung — meinlink.at' : 'Link Threat & Security Inspector — ' . ($domain ?? 'ternis.link');
@endphp
<x-layouts.app :title="$title">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/scan') }}">
        <meta name="description" content="{{ $isDe ? 'Kostenlose Sicherheitsanalyse für beliebige Webadressen. Prüfe Phishing-Risiken, Spam-Muster und Malware-Gefahren mit TypeSafe AI.' : 'Inspect any web destination URL for phishing risks, malware payloads, and spam reputation using TypeSafe AI.' }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $isDe ? 'Kostenlose Sicherheitsanalyse für Webadressen mit TypeSafe AI.' : 'Free link threat and security inspection with TypeSafe AI.' }}">
    </x-slot:head>

    <div class="relative mx-auto flex max-w-4xl flex-col items-center px-4 py-12 text-center sm:px-6 sm:py-16">
        {{-- Header Badge --}}
        <x-ui.badge tone="solid" class="mb-4 inline-flex items-center gap-2">
            <svg class="h-3.5 w-3.5 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
            <span>TypeSafe AI SystemOne</span>
        </x-ui.badge>

        {{-- Headline --}}
        <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">
            {{ $isDe ? 'Link-Sicherheit' : 'Link Threat' }}
            <span class="text-neutral-400 dark:text-neutral-500">{{ $isDe ? 'in Echtzeit prüfen.' : 'Inspector.' }}</span>
        </h1>
        <p class="mt-3 max-w-2xl text-base text-neutral-500 dark:text-neutral-400">
            {{ $isDe
                ? 'Füge eine beliebige Zieladresse ein, um Phishing-Risiken, betrügerische Anmeldeseiten, Spam-Farmen und schädliche Payloads transparent zu analysieren.'
                : 'Paste any destination URL to evaluate phishing deception, credential harvesters, spam networks, and malicious payloads before visiting or sharing.' }}
        </p>

        {{-- Inspection Form --}}
        <div class="mt-8 w-full max-w-2xl">
            <form method="GET" action="{{ url('/scan') }}" class="flex flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input
                        type="url"
                        name="url"
                        value="{{ $url }}"
                        required
                        placeholder="{{ $isDe ? 'https://zieladresse.de/anmeldung' : 'https://destination-url.com/login' }}"
                        class="w-full rounded-xl border border-neutral-300 bg-white py-3 pr-4 pl-10 text-sm shadow-xs transition placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-neutral-100 dark:focus:ring-neutral-100"
                    >
                </div>
                <x-ui.button type="submit" variant="primary" size="lg" class="shrink-0">
                    {{ $isDe ? 'URL prüfen' : 'Inspect URL' }}
                </x-ui.button>
            </form>
            <p class="mt-2 text-left text-xs text-neutral-400 dark:text-neutral-500">
                {{ $isDe ? 'Kostenlos & anonym. Ergebnisse werden zur Ressourcenschonung zwischengespeichert.' : 'Free & anonymous inspection. Results are cached to conserve edge compute.' }}
            </p>
        </div>

        {{-- Results Section --}}
        @if ($result !== null)
            <div class="mt-10 w-full max-w-2xl text-left">
                @if (! ($result['is_valid'] ?? false))
                    <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
                        <div class="flex items-center gap-2 font-bold">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>{{ $isDe ? 'Ungültige oder blockierte URL' : 'Invalid or Blocked URL' }}</span>
                        </div>
                        <p class="mt-2 text-sm">{{ $result['reasoning'] ?? ($isDe ? 'Die angegebene Adresse konnte nicht analysiert werden.' : 'The provided address could not be analyzed.') }}</p>
                    </div>
                @else
                    @php
                        $score = (int) ($result['threat_score'] ?? 0);
                        $category = $result['category'] ?? 'safe';
                        $isSafe = (bool) ($result['is_safe'] ?? false);
                        $tone = match (true) {
                            $score >= 80 || in_array($category, ['phishing', 'malware', 'intranet_blocked']) => 'danger',
                            $score >= 50 || $category === 'spam' => 'warning',
                            default => 'success',
                        };
                    @endphp

                    <x-ui.card class="overflow-hidden border border-neutral-200 shadow-md dark:border-neutral-800">
                        {{-- Threat Status Header --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 pb-4 dark:border-neutral-800">
                            <div>
                                <span class="font-mono text-xs uppercase tracking-wider text-neutral-400">{{ $isDe ? 'Geprüfte Zieladresse' : 'Scanned Destination' }}</span>
                                <h3 class="mt-0.5 truncate font-mono text-base font-bold text-neutral-900 dark:text-white" title="{{ $result['url'] }}">
                                    {{ $result['url'] }}
                                </h3>
                            </div>
                            <div>
                                @if ($tone === 'danger')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700 dark:bg-red-950/60 dark:text-red-400">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        {{ $isDe ? 'Hohe Bedrohung' : 'High Threat' }} ({{ strtoupper($category) }})
                                    </span>
                                @elseif ($tone === 'warning')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
                                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                        {{ $isDe ? 'Verdächtig' : 'Suspicious' }} ({{ strtoupper($category) }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        {{ $isDe ? 'Sicher / Legitim' : 'Safe / Verified' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Score Meter --}}
                        <div class="mt-5">
                            <div class="flex items-center justify-between text-xs font-semibold">
                                <span class="text-neutral-500">{{ $isDe ? 'Bedrohungs-Score (0 = Harmlos, 100 = Gefährlich)' : 'Threat Probability Score (0 = Benign, 100 = Hostile)' }}</span>
                                <span class="font-mono text-sm font-bold text-neutral-900 dark:text-white">{{ $score }} / 100</span>
                            </div>
                            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <div
                                    class="h-full rounded-full transition-all duration-500 {{ $score >= 80 ? 'bg-red-500' : ($score >= 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                    style="width: {{ max(4, $score) }}%"
                                ></div>
                            </div>
                        </div>

                        {{-- Metadata Metrics Grid --}}
                        <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-neutral-950/60">
                                <dt class="text-[11px] font-medium text-neutral-400">{{ $isDe ? 'Ziel-Host' : 'Destination Host' }}</dt>
                                <dd class="mt-0.5 truncate font-mono text-xs font-bold text-neutral-800 dark:text-neutral-200">{{ $result['host'] ?? '—' }}</dd>
                            </div>
                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-neutral-950/60">
                                <dt class="text-[11px] font-medium text-neutral-400">{{ $isDe ? 'Kategorie' : 'Category' }}</dt>
                                <dd class="mt-0.5 font-mono text-xs font-bold uppercase text-neutral-800 dark:text-neutral-200">{{ $category }}</dd>
                            </div>
                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-neutral-950/60">
                                <dt class="text-[11px] font-medium text-neutral-400">{{ $isDe ? 'KI-Konfidenz' : 'AI Confidence' }}</dt>
                                <dd class="mt-0.5 font-mono text-xs font-bold text-neutral-800 dark:text-neutral-200">{{ round(($result['confidence'] ?? 1.0) * 100) }}%</dd>
                            </div>
                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-neutral-950/60">
                                <dt class="text-[11px] font-medium text-neutral-400">{{ $isDe ? 'Empfehlung' : 'Recommended Action' }}</dt>
                                <dd class="mt-0.5 font-mono text-xs font-bold uppercase {{ $result['action_recommended'] === 'quarantine' ? 'text-red-600' : ($result['action_recommended'] === 'bot_protect' ? 'text-amber-600' : 'text-emerald-600') }}">
                                    {{ $result['action_recommended'] ?? 'allow' }}
                                </dd>
                            </div>
                        </dl>

                        {{-- Verdict Advice Note --}}
                        <div class="mt-5 rounded-lg border border-neutral-100 bg-neutral-50/80 p-3 text-xs leading-relaxed text-neutral-600 dark:border-neutral-800/80 dark:bg-neutral-950/40 dark:text-neutral-400">
                            <strong>{{ $isDe ? 'Ergebnis-Bewertung:' : 'Assessment Summary:' }}</strong>
                            {{ $result['reasoning'] ?? '' }}
                            @if ($result['cached'] ?? false)
                                <span class="ml-1 text-[11px] text-neutral-400 dark:text-neutral-500">({{ $isDe ? 'Zwischengespeichert ⚡' : 'Cached lookup ⚡' }})</span>
                            @endif
                        </div>

                        {{-- Call to action --}}
                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 pt-4 dark:border-neutral-800">
                            @if ($isSafe)
                                <span class="text-xs text-neutral-500">
                                    {{ $isDe ? 'Zieladresse ist unbedenklich und kann gekürzt werden.' : 'Clean destination — safe to shorten and share.' }}
                                </span>
                                <a
                                    href="{{ url('/') }}?url={{ urlencode($result['url']) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-neutral-900 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100"
                                >
                                    <span>{{ $isDe ? 'Kurzlink erstellen' : 'Create Short Link' }} &rarr;</span>
                                </a>
                            @else
                                <span class="text-xs font-semibold text-red-600 dark:text-red-400">
                                    {{ $isDe ? 'Warnung: Der Besuch dieser Adresse wird nicht empfohlen.' : 'Warning: Navigating to this destination is not recommended.' }}
                                </span>
                            @endif
                        </div>
                    </x-ui.card>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
