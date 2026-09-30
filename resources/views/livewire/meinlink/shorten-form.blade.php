<div class="w-full" x-data="{
    copied: false,
    copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            });
        } else {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        }
    },
    pasteFromClipboard() {
        if (navigator.clipboard) {
            navigator.clipboard.readText().then(text => {
                if (text) {
                    $wire.set('destination_url', text.trim());
                }
            });
        }
    }
}">
    @if ($shortUrl)
        {{-- Success State --}}
        <div class="relative overflow-hidden rounded-2xl border border-emerald-500/20 bg-white/80 p-6 shadow-xl backdrop-blur-xl transition-all sm:p-8 dark:border-emerald-500/30 dark:bg-zinc-900/80" role="status" aria-live="polite">
            {{-- Accent glow --}}
            <div class="pointer-events-none absolute -right-20 -top-20 h-48 w-48 rounded-full bg-emerald-500/10 blur-3xl"></div>

            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <div>
                    <h3 class="font-display text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Link erfolgreich gekürzt!</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Dein Kurzlink ist sofort weltweit erreichbar.</p>
                </div>
            </div>

            {{-- Short URL display row --}}
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="group relative flex flex-1 items-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 shadow-inner transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950/60 dark:hover:border-zinc-700">
                    <input
                        type="text"
                        readonly
                        value="{{ $shortUrl }}"
                        class="w-full bg-transparent font-mono text-base font-semibold text-zinc-900 outline-none select-all dark:text-zinc-100"
                        id="meinlink_short_url_result"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="copyToClipboard('{{ $shortUrl }}')"
                        class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 font-semibold text-white shadow-sm transition hover:bg-red-500 active:scale-[0.98] sm:flex-initial"
                        aria-label="Kurzlink in Zwischenablage kopieren"
                    >
                        <svg x-show="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg x-show="copied" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="copied ? 'Kopiert!' : 'Kopieren'">Kopieren</span>
                    </button>

                    <a
                        href="{{ $shortUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 active:scale-[0.98] dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                        title="Link testen"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <a
                        href="{{ url('/v1/qr?url='.urlencode($shortUrl).'&format=png') }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 active:scale-[0.98] dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                        title="QR-Code herunterladen"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Metadata row: original URL & Expiration --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                @if ($originalUrl)
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="font-medium text-zinc-600 dark:text-zinc-300">Ziel:</span>
                        <span class="truncate font-mono" title="{{ $originalUrl }}">{{ $originalUrl }}</span>
                    </div>
                @endif

                @if ($linkExpiresAtFormatted)
                    <div class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Gültig bis: {{ $linkExpiresAtFormatted }}</span>
                    </div>
                @endif
            </div>

            {{-- Reset action --}}
            <div class="mt-6 border-t border-zinc-100 pt-4 dark:border-zinc-800/80">
                <button
                    type="button"
                    wire:click="resetForm"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Weiteren Link kürzen
                </button>
            </div>
        </div>
    @else
        {{-- Creation Form --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white/90 p-5 shadow-2xl backdrop-blur-xl sm:p-7 dark:border-zinc-800 dark:bg-zinc-900/90">
            <form wire:submit="create" novalidate>
                <div class="flex flex-col gap-4">
                    {{-- Options Bar: Domain, Length, Expiration --}}
                    <div class="flex flex-col sm:flex-row sm:flex-wrap items-start sm:items-center justify-between gap-3 border-b border-zinc-100 pb-3 dark:border-zinc-800/80">
                        {{-- Domain Picker --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Domain:</span>
                            <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100/70 p-0.5 text-xs font-medium dark:border-zinc-700 dark:bg-zinc-800">
                                <button
                                    type="button"
                                    wire:click="$set('selectedDomain', 'meinlink.at')"
                                    @class([
                                        'rounded-md px-2.5 py-1 transition',
                                        'bg-white text-zinc-900 shadow-xs font-semibold dark:bg-zinc-900 dark:text-zinc-100' => $selectedDomain === 'meinlink.at',
                                        'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' => $selectedDomain !== 'meinlink.at',
                                    ])
                                >
                                    meinlink.at
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('selectedDomain', 'href.nz')"
                                    @class([
                                        'rounded-md px-2.5 py-1 transition',
                                        'bg-white text-zinc-900 shadow-xs font-semibold dark:bg-zinc-900 dark:text-zinc-100' => $selectedDomain === 'href.nz',
                                        'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' => $selectedDomain !== 'href.nz',
                                    ])
                                >
                                    href.nz
                                </button>
                            </div>
                        </div>

                        {{-- Length Picker: 5 to 9 --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Länge:</span>
                            <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100/70 p-0.5 text-xs font-medium dark:border-zinc-700 dark:bg-zinc-800">
                                @foreach ([5, 6, 7, 8, 9] as $len)
                                    <button
                                        type="button"
                                        wire:click="$set('slugLength', {{ $len }})"
                                        @class([
                                            'rounded-md px-2 py-1 transition',
                                            'bg-white text-zinc-900 shadow-xs font-semibold dark:bg-zinc-900 dark:text-zinc-100' => $slugLength === $len,
                                            'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' => $slugLength !== $len,
                                        ])
                                        title="{{ $len }} Zeichen"
                                    >
                                        {{ $len }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Optional Expiration Date --}}
                        <div class="flex items-center gap-1.5">
                            <label for="expiresAt" class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Ablauf:
                            </label>
                            <div class="relative flex items-center">
                                <input
                                    type="date"
                                    id="expiresAt"
                                    wire:model.live="expiresAt"
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                    max="{{ date('Y-m-d', strtotime('+365 days')) }}"
                                    class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-800 transition outline-none focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                    title="Optionales Ablaufdatum"
                                />
                                @if ($expiresAt)
                                    <button
                                        type="button"
                                        wire:click="$set('expiresAt', null)"
                                        class="ml-1 rounded p-0.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                                        title="Ablaufdatum entfernen"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- URL Input Row --}}
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="destination_url" class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                Ziel-URL
                            </label>
                            @if (! $minimal)
                                <div class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400" title="Verbleibende Gast-Links heute">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span><strong class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->quotaLeft }}</strong>/{{ \App\Services\LinkService::ANONYMOUS_DAILY_LIMIT }} heute frei</span>
                                </div>
                            @endif
                        </div>

                        <div class="relative flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="relative flex-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>

                                <input
                                    type="url"
                                    id="destination_url"
                                    name="destination_url"
                                    wire:model.live.debounce.300ms="destination_url"
                                    wire:loading.attr="disabled"
                                    wire:target="create"
                                    placeholder="https://beispiel.de/sehr-lange-url…"
                                    required
                                    autocomplete="off"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    inputmode="url"
                                    maxlength="{{ \App\Services\LinkService::PUBLIC_MAX_URL_LENGTH }}"
                                    @class([
                                        'w-full rounded-xl border bg-zinc-50/80 py-3.5 pl-10 pr-20 text-sm font-medium text-zinc-900 transition placeholder:text-zinc-600 outline-none focus:bg-white dark:bg-zinc-950/60 dark:text-zinc-100 dark:placeholder:text-zinc-400 dark:focus:bg-zinc-950',
                                        'border-red-500 focus:ring-2 focus:ring-red-500/20' => $errors->has('destination_url'),
                                        'border-emerald-500 focus:ring-2 focus:ring-emerald-500/20' => $urlState === 'valid' && ! $errors->has('destination_url'),
                                        'border-zinc-200 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/20 dark:border-zinc-800 dark:focus:border-zinc-600' => ! $errors->has('destination_url') && $urlState !== 'valid',
                                    ])
                                />

                                {{-- Clear / Paste Quick Actions inside input --}}
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                                    @if ($destination_url)
                                        <button
                                            type="button"
                                            wire:click="$set('destination_url', '')"
                                            class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                            title="Eingabe leeren"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            @click="pasteFromClipboard()"
                                            class="rounded-lg px-2 py-1 text-xs font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                            title="Aus Zwischenablage einfügen"
                                        >
                                            Einfügen
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="create"
                                class="inline-flex h-[46px] items-center justify-center gap-2 rounded-xl bg-red-600 px-6 font-semibold text-white shadow-md transition hover:bg-red-500 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 dark:bg-red-600 dark:hover:bg-red-500"
                            >
                                <span wire:loading.remove wire:target="create" class="flex items-center gap-2">
                                    <span>Kürzen</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="create" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Kürze…</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- Fix suggestion --}}
                    @if ($this->fixablePreview)
                        <div class="flex items-center justify-between rounded-xl border border-amber-500/20 bg-amber-500/5 px-3.5 py-2 text-xs text-amber-700 dark:text-amber-300">
                            <span class="truncate">
                                Meintest du <strong class="font-mono font-medium">{{ \Illuminate\Support\Str::limit($this->fixablePreview, 45) }}</strong>?
                            </span>
                            <button
                                type="button"
                                wire:click="applyFix"
                                class="ml-2 shrink-0 font-semibold underline underline-offset-2 hover:text-amber-800 dark:hover:text-amber-200"
                            >
                                URL korrigieren
                            </button>
                        </div>
                    @endif

                    {{-- Duplicate notice --}}
                    @if ($this->duplicate)
                        <div class="flex items-center justify-between rounded-xl border border-sky-500/20 bg-sky-500/5 px-3.5 py-2.5 text-xs text-sky-800 dark:text-sky-300">
                            <div>
                                <span class="font-semibold">Bereits gekürzt:</span>
                                <span class="ml-1 font-mono">https://{{ $this->selectedDomain }}/{{ $this->duplicate->slug }}</span>
                            </div>
                            <button
                                type="button"
                                @click="copyToClipboard('https://{{ $this->selectedDomain }}/{{ $this->duplicate->slug }}')"
                                class="ml-2 shrink-0 rounded-lg bg-sky-100 px-2 py-1 font-semibold text-sky-800 transition hover:bg-sky-200 dark:bg-sky-900/60 dark:text-sky-200"
                            >
                                Kopieren
                            </button>
                        </div>
                    @endif

                    {{-- Validation errors --}}
                    @error('destination_url')
                        <div class="flex items-start gap-2 text-xs font-medium text-red-600 dark:text-red-400">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    @error('expiresAt')
                        <div class="flex items-start gap-2 text-xs font-medium text-red-600 dark:text-red-400">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    @error('turnstile_token')
                        <div class="flex items-start gap-2 text-xs font-medium text-red-600 dark:text-red-400">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    {{-- Turnstile widget container if configured --}}
                    @if (config('services.turnstile.key'))
                        <div
                            wire:ignore
                            class="mt-1 flex justify-center"
                            x-data="{
                                widgetId: null,
                                init() {
                                    const render = () => {
                                        if (typeof turnstile === 'undefined') {
                                            setTimeout(render, 150);
                                            return;
                                        }
                                        if (this.widgetId !== null) return;
                                        turnstile.ready(() => {
                                            try {
                                                this.widgetId = turnstile.render($refs.cfContainer, {
                                                    sitekey: '{{ config('services.turnstile.key') }}',
                                                    theme: 'auto',
                                                    callback: (token) => $wire.set('turnstile_token', token),
                                                    'expired-callback': () => $wire.set('turnstile_token', null),
                                                    'error-callback': () => $wire.set('turnstile_token', null),
                                                });
                                            } catch (e) {}
                                        });
                                    };
                                    render();
                                    Livewire.on('reset-turnstile', () => {
                                        if (this.widgetId !== null && typeof turnstile !== 'undefined') {
                                            turnstile.reset(this.widgetId);
                                        }
                                    });
                                }
                            }"
                        >
                            <div x-ref="cfContainer"></div>
                        </div>
                    @endif

                    {{-- Subtle hint footer --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                        <span>Kostenlos &amp; ohne Registrierung · Sofort aktiv</span>
                        <a href="{{ url('/login') }}" class="font-medium text-zinc-700 transition hover:text-red-600 dark:text-zinc-300 dark:hover:text-red-400">
                            Eigene Kürzel &amp; Statistiken? Zum Login →
                        </a>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
