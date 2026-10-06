<div class="w-full" x-data="{
    copied: false,
    copiedIndex: null,
    showQr: false,
    history: [],
    historyCollapsed: false,
    activeQrIndex: null,

    init() {
        this.loadHistory();
        this.historyCollapsed = localStorage.getItem('ml-history-collapsed') === '1';

        @if ($shortUrl)
            this.recordLink('{{ $shortUrl }}', '{{ addslashes($originalUrl ?? '') }}');
        @endif

        this.$watch('$wire.shortUrl', value => {
            if (value) {
                this.recordLink(value, this.$wire.originalUrl);
                this.showQr = false;
            }
        });
    },

    loadHistory() {
        try {
            const raw = localStorage.getItem('ml-recent-links');
            this.history = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(this.history)) this.history = [];
        } catch (e) {
            this.history = [];
        }
    },

    saveHistory() {
        try {
            localStorage.setItem('ml-recent-links', JSON.stringify(this.history.slice(0, 15)));
        } catch (e) {}
    },

    recordLink(short, original) {
        if (!short) return;
        this.loadHistory();
        this.history = this.history.filter(item => item.short !== short);
        const now = new Date();
        const dateFormatted = now.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' }) + ', ' +
                              now.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        this.history.unshift({
            short: short,
            original: original || '',
            created_at: Date.now(),
            date_formatted: dateFormatted
        });
        this.saveHistory();
    },

    toggleHistory() {
        this.historyCollapsed = !this.historyCollapsed;
        try {
            localStorage.setItem('ml-history-collapsed', this.historyCollapsed ? '1' : '0');
        } catch (e) {}
    },

    clearHistory() {
        this.history = [];
        this.activeQrIndex = null;
        try {
            localStorage.removeItem('ml-recent-links');
        } catch (e) {}
    },

    removeHistoryItem(index) {
        this.history.splice(index, 1);
        if (this.activeQrIndex === index) {
            this.activeQrIndex = null;
        }
        this.saveHistory();
    },

    toggleHistoryQr(index) {
        this.activeQrIndex = (this.activeQrIndex === index) ? null : index;
    },

    copyHistoryLink(text, index) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                this.copiedIndex = index;
                setTimeout(() => this.copiedIndex = null, 2000);
            });
        } else {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            this.copiedIndex = index;
            setTimeout(() => this.copiedIndex = null, 2000);
        }
    },

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
                <span class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-500/30 text-emerald-600 ring-1 ring-emerald-500/30 shadow-xs dark:from-emerald-950/80 dark:to-teal-900/60 dark:text-emerald-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17L4 12" />
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
                        <svg x-show="!copied" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        <svg x-show="copied" style="display: none;" class="h-4 w-4 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17L4 12" />
                        </svg>
                        <span x-text="copied ? 'Kopiert!' : 'Kopieren'">Kopieren</span>
                    </button>

                    <a
                        href="{{ $shortUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900 active:scale-[0.98] dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-white"
                        title="Link testen"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                    </a>

                    <button
                        type="button"
                        @click="showQr = !showQr"
                        :class="showQr ? 'border-red-500 bg-red-50 text-red-600 dark:border-red-600 dark:bg-red-950/50 dark:text-red-400' : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-white'"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-xl border shadow-sm transition active:scale-[0.98]"
                        title="QR-Code anzeigen"
                        :aria-expanded="showQr.toString()"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="6" height="6" rx="1"/>
                            <rect x="15" y="3" width="6" height="6" rx="1"/>
                            <rect x="3" y="15" width="6" height="6" rx="1"/>
                            <path d="M15 15h2v2h-2z"/>
                            <path d="M19 19h2v2h-2z"/>
                            <path d="M19 15h2v2h-2z"/>
                            <path d="M15 19h2v2h-2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Inline QR Code Section (generated on demand after clicking the button) --}}
            <div
                x-show="showQr"
                x-transition
                x-cloak
                class="mt-6 flex flex-col items-center rounded-xl border border-zinc-200/90 bg-zinc-50/80 p-5 dark:border-zinc-800 dark:bg-zinc-950/60"
            >
                <div class="relative flex flex-col items-center">
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-700 dark:bg-white">
                        <template x-if="showQr">
                            <img
                                :src="'{{ $shortUrl }}/qr.svg'"
                                alt="QR-Code für {{ $shortUrl }}"
                                class="h-44 w-44 sm:h-48 sm:w-48"
                            />
                        </template>
                    </div>
                    <p class="mt-3 text-xs font-medium text-zinc-600 dark:text-zinc-400">Scannen mit der Smartphone-Kamera zum direkten Öffnen</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a
                            href="{{ $shortUrl }}/qr.png"
                            download="qr-meinlink.png"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span>PNG herunterladen</span>
                        </a>
                        <a
                            href="{{ $shortUrl }}/qr.svg"
                            download="qr-meinlink.svg"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span>SVG herunterladen</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Metadata row: original URL & Expiration --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                @if ($originalUrl)
                    <div class="flex items-center gap-1.5 truncate">
                        <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="22" y1="12" x2="18" y2="12"/>
                            <line x1="6" y1="12" x2="2" y2="12"/>
                            <line x1="12" y1="6" x2="12" y2="2"/>
                            <line x1="12" y1="22" x2="12" y2="18"/>
                        </svg>
                        <span class="font-medium text-zinc-600 dark:text-zinc-300">Ziel:</span>
                        <span class="truncate font-mono" title="{{ $originalUrl }}">{{ $originalUrl }}</span>
                    </div>
                @endif

                @if ($linkExpiresAtFormatted)
                    <div class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
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
                    class="group inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    <svg class="h-3.5 w-3.5 transition-transform group-hover:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
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
                            <span class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="2" y1="12" x2="22" y2="12"/>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                                <span>Domain:</span>
                            </span>
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
                                <button
                                    type="button"
                                    wire:click="$set('selectedDomain', 'href.yt')"
                                    @class([
                                        'rounded-md px-2.5 py-1 transition',
                                        'bg-white text-zinc-900 shadow-xs font-semibold dark:bg-zinc-900 dark:text-zinc-100' => $selectedDomain === 'href.yt',
                                        'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' => $selectedDomain !== 'href.yt',
                                    ])
                                >
                                    href.yt
                                </button>
                            </div>
                        </div>

                        {{-- Length Picker: 5 to 9 --}}
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="4" y1="9" x2="20" y2="9"/>
                                    <line x1="4" y1="15" x2="20" y2="15"/>
                                    <line x1="10" y1="3" x2="8" y2="21"/>
                                    <line x1="16" y1="3" x2="14" y2="21"/>
                                </svg>
                                <span>Länge:</span>
                            </span>
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
                            <label for="expiresAt" class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <span>Ablauf:</span>
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
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"/>
                                            <line x1="6" y1="6" x2="18" y2="18"/>
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
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
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
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            @click="pasteFromClipboard()"
                                            class="group inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                            title="Aus Zwischenablage einfügen"
                                        >
                                            <svg class="h-3.5 w-3.5 text-zinc-400 transition group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                            </svg>
                                            <span>Einfügen</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="create"
                                class="group inline-flex h-[46px] items-center justify-center gap-2 rounded-xl bg-red-600 px-6 font-semibold text-white shadow-md transition hover:bg-red-500 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 dark:bg-red-600 dark:hover:bg-red-500"
                            >
                                <span wire:loading.remove wire:target="create" class="flex items-center gap-2">
                                    <span>Kürzen</span>
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                        <polyline points="12 5 19 12 12 19"/>
                                    </svg>
                                </span>
                                <span wire:loading wire:target="create" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
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
                            <span class="inline-flex items-center gap-2 truncate">
                                <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                </svg>
                                <span class="truncate">Meintest du <strong class="font-mono font-medium">{{ \Illuminate\Support\Str::limit($this->fixablePreview, 45) }}</strong>?</span>
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
                            <div class="flex items-center gap-2 truncate">
                                <svg class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <span class="font-semibold">Bereits gekürzt:</span>
                                <span class="truncate font-mono">https://{{ $this->selectedDomain }}/{{ $this->duplicate->slug }}</span>
                            </div>
                            <button
                                type="button"
                                @click="copyToClipboard('https://{{ $this->selectedDomain }}/{{ $this->duplicate->slug }}')"
                                class="inline-flex items-center gap-1 ml-2 shrink-0 rounded-lg bg-sky-100 px-2.5 py-1 font-semibold text-sky-800 transition hover:bg-sky-200 dark:bg-sky-900/60 dark:text-sky-200"
                            >
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                                <span>Kopieren</span>
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
                                wire: null,
                                widgetId: null,
                                loadTimer: null,
                                loadAttempts: 0,
                                boot(component, rootEl) {
                                    const self = this;
                                    self.wire = component;
                                    const container = rootEl.querySelector('[data-cf-container]');
                                    const renderWidget = () => {
                                        if (typeof turnstile === 'undefined') {
                                            self.loadAttempts++;
                                            if (self.loadAttempts > 50) return;
                                            self.loadTimer = setTimeout(renderWidget, 100);
                                            return;
                                        }
                                        if (self.widgetId !== null || ! container) return;
                                        turnstile.ready(() => {
                                            try {
                                                self.widgetId = turnstile.render(container, {
                                                    sitekey: '{{ config('services.turnstile.key') }}',
                                                    action: '{{ \App\Services\TurnstileService::ACTION }}',
                                                    theme: 'auto',
                                                    callback: (token) => {
                                                        self.wire.set('turnstile_token', token);
                                                    },
                                                    'expired-callback': () => {
                                                        self.wire.set('turnstile_token', null);
                                                    },
                                                    'timeout-callback': () => {
                                                        self.wire.set('turnstile_token', null);
                                                    },
                                                    'error-callback': () => {
                                                        self.wire.set('turnstile_token', null);
                                                    },
                                                });
                                            } catch (e) {}
                                        });
                                    };
                                    renderWidget();
                                },
                                reset() {
                                    if (typeof turnstile !== 'undefined' && this.widgetId !== null) {
                                        turnstile.reset(this.widgetId);
                                    }
                                },
                                destroy() {
                                    if (this.loadTimer !== null) clearTimeout(this.loadTimer);
                                    if (typeof turnstile !== 'undefined' && this.widgetId !== null) {
                                        turnstile.remove(this.widgetId);
                                        this.widgetId = null;
                                    }
                                }
                            }"
                            x-init="boot($wire, $el)"
                            x-on:reset-turnstile.window="reset()"
                        >
                            <div data-cf-container></div>
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

    @if (! $minimal)
        {{-- Guest Link History Tray --}}
        <div
            x-show="history && history.length > 0"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-6 overflow-hidden rounded-2xl border border-zinc-200/90 bg-white/80 p-5 shadow-lg backdrop-blur-xl sm:p-6 dark:border-zinc-800 dark:bg-zinc-900/80"
        >
            {{-- Header --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-800/80">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-500/10 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M12 7v5l4 2"/>
                        </svg>
                    </span>
                    <h4 class="font-display text-sm font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Link-Verlauf
                    </h4>
                    <span
                        class="inline-flex items-center justify-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                        x-text="history.length"
                    ></span>
                    <span class="hidden text-xs text-zinc-600 sm:inline dark:text-zinc-400">· Lokal im Browser</span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="toggleHistory()"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                    >
                        <span x-text="historyCollapsed ? 'Anzeigen' : 'Minimieren'"></span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': !historyCollapsed }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="if (confirm('Möchtest du deinen lokalen Link-Verlauf leeren?')) clearHistory()"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-zinc-600 transition hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                        title="Verlauf leeren"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        <span>Leeren</span>
                    </button>
                </div>
            </div>

            {{-- History List --}}
            <div x-show="!historyCollapsed" x-transition class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800/60">
                <template x-for="(item, index) in history" :key="item.short">
                    <div class="py-3 first:pt-1 last:pb-1">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            {{-- URL info --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a
                                        :href="item.short"
                                        target="_blank"
                                        rel="noopener"
                                        class="truncate font-mono text-sm font-bold text-zinc-900 transition hover:text-red-600 dark:text-zinc-100 dark:hover:text-red-400"
                                        x-text="item.short"
                                    ></a>
                                    <a
                                        :href="item.short"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                        title="Link im neuen Tab öffnen"
                                    >
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                            <polyline points="15 3 21 3 21 9"/>
                                            <line x1="10" y1="14" x2="21" y2="3"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                    <span class="truncate max-w-xs sm:max-w-md font-mono" :title="item.original" x-text="item.original"></span>
                                    <span class="text-zinc-400 dark:text-zinc-600">·</span>
                                    <span class="text-[11px] text-zinc-600 dark:text-zinc-400" x-text="item.date_formatted"></span>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1.5 self-end sm:self-center">
                                <button
                                    type="button"
                                    @click="copyHistoryLink(item.short, index)"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs font-semibold text-zinc-700 shadow-2xs transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                    :title="copiedIndex === index ? 'Kopiert!' : 'In Zwischenablage kopieren'"
                                >
                                    <svg x-show="copiedIndex !== index" class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                    <svg x-show="copiedIndex === index" style="display: none;" class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17L4 12"/>
                                    </svg>
                                    <span x-text="copiedIndex === index ? 'Kopiert' : 'Kopieren'">Kopieren</span>
                                </button>

                                <button
                                    type="button"
                                    @click="toggleHistoryQr(index)"
                                    :class="activeQrIndex === index ? 'border-red-500 bg-red-50 text-red-600 dark:border-red-600 dark:bg-red-950/50 dark:text-red-400' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700'"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border px-2.5 text-xs font-semibold shadow-2xs transition"
                                    title="QR-Code anzeigen"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="6" height="6" rx="1"/>
                                        <rect x="15" y="3" width="6" height="6" rx="1"/>
                                        <rect x="3" y="15" width="6" height="6" rx="1"/>
                                        <path d="M15 15h2v2h-2z"/>
                                        <path d="M19 19h2v2h-2z"/>
                                        <path d="M19 15h2v2h-2z"/>
                                        <path d="M15 19h2v2h-2z"/>
                                    </svg>
                                    <span>QR-Code</span>
                                </button>

                                <button
                                    type="button"
                                    @click="removeHistoryItem(index)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                    title="Aus Verlauf entfernen"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Inline QR Code expansion for history item (loaded on demand) --}}
                        <div
                            x-show="activeQrIndex === index"
                            x-transition
                            class="mt-3 flex flex-col items-center rounded-xl border border-zinc-200/70 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/50"
                        >
                            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-2.5 shadow-2xs dark:border-zinc-700 dark:bg-white">
                                <template x-if="activeQrIndex === index">
                                    <img
                                        :src="item.short + '/qr.svg'"
                                        :alt="'QR-Code für ' + item.short"
                                        class="h-36 w-36 sm:h-40 sm:w-40"
                                    />
                                </template>
                            </div>
                            <p class="mt-2 text-xs font-medium text-zinc-600 dark:text-zinc-400">Scannen mit Smartphone zum direkten Öffnen</p>
                            <div class="mt-2 flex items-center gap-2">
                                <a
                                    :href="item.short + '/qr.png'"
                                    download="qr-code.png"
                                    class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-xs font-semibold text-zinc-700 shadow-2xs transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                >
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                    <span>PNG</span>
                                </a>
                                <a
                                    :href="item.short + '/qr.svg'"
                                    download="qr-code.svg"
                                    class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-xs font-semibold text-zinc-700 shadow-2xs transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                >
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                    <span>SVG</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif
</div>
