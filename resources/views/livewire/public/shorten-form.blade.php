<div class="sk-card tilt-l" data-sk-form>
    <span class="sk-tape" aria-hidden="true"></span>
    {{-- sparkles --}}
    <svg class="dk dk-faint dk-hide-sm" style="top: -14px; left: 18px;" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
    <svg class="dk dk-faint dk-hide-sm" style="top: -8px; right: 30px;" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>

    <div class="sk-form-head">
        <h2 class="sk-form-title">
            {{-- link --}}
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Shorten a link — no account needed
        </h2>
        <div class="sk-meter" title="Guest links left today">
            <span class="sk-meter-boxes" aria-hidden="true">
                @for ($i = 0; $i < 10; $i++)
                    <span class="sk-meter-box{{ $i < (int) ceil($this->quotaLeft / 5) ? ' is-full' : '' }}"></span>
                @endfor
            </span>
            <span class="sk-meter-text">{{ $this->quotaLeft }} of {{ \App\Services\LinkService::ANONYMOUS_DAILY_LIMIT }} free links left today</span>
        </div>
    </div>
    <p class="sk-form-sub">Paste any long URL. Guests get an auto-generated 8-character link on this domain.</p>

    @if ($shortUrl)
        <div class="sk-ticket" role="status" aria-live="polite">
            {{-- dotted ring --}}
            <svg class="dk" style="top: -22px; right: 8px;" width="44" height="44" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="24" r="15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-dasharray="4 5"/><path d="M24 13.5c.5 3.4 1.5 4.9 5.3 5.3-3.8.4-4.8 1.9-5.3 5.3-.5-3.4-1.5-4.9-5.3-5.3 3.8-.4 4.8-1.9 5.3-5.3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            <p class="sk-result-kicker">
                {{-- check --}}
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m8.2 12.3 2.5 2.5 5.1-5.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Done — your short link is ready
            </p>
            <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-ticket-link" data-sk-result-link>{{ $shortUrl }}</a>
            @if ($originalUrl)
                <p class="sk-original" title="{{ $originalUrl }}" data-sk-result-original>
                    Shortened from <span>{{ \Illuminate\Support\Str::limit($originalUrl, 72) }}</span>
                </p>
            @endif
            <div class="sk-ticket-perf" aria-hidden="true"></div>
            <div class="sk-result-actions">
                <button
                    type="button"
                    class="sk-copy"
                    data-copy-value="{{ $shortUrl }}"
                    aria-label="Copy short link to clipboard"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5" stroke="currentColor" stroke-width="2.2"/><path d="M5.5 15V6.8A2.3 2.3 0 0 1 7.8 4.5h8.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                    Copy
                </button>
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-open">
                    Open link
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 4.5h5.5V10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 4.5 11 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M19.5 13.5V18a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2H11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                </a>
                <button type="button" wire:click="resetForm" class="sk-again">
                    {{-- refresh --}}
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12a7.5 7.5 0 1 1 2.2 5.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M4.5 17.5v-4h4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Shorten another
                </button>
            </div>
            <p class="sk-copied" data-copy-feedback hidden>Copied to clipboard.</p>
        </div>
    @else
        <form wire:submit="create" novalidate>
            <div class="sk-label-row">
                <label class="sk-label" for="public_destination_url">Destination URL</label>
                <span class="sk-url-state" aria-live="polite">
                    @if ($urlState === 'valid')
                        <span class="sk-state is-valid">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m8.2 12.3 2.5 2.5 5.1-5.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Looks good
                        </span>
                    @elseif ($urlState === 'invalid')
                        <span class="sk-state is-invalid">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m9.2 9.2 5.6 5.6M14.8 9.2l-5.6 5.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            Check the URL
                        </span>
                    @endif
                </span>
            </div>
            <div class="sk-input-row">
                <div class="sk-field">
                    <input
                        type="url"
                        id="public_destination_url"
                        name="destination_url"
                        wire:model.live.debounce.400ms="destination_url"
                        wire:loading.attr="disabled"
                        wire:target="create"
                        placeholder="https://example.com/very-long-url…"
                        required
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        inputmode="url"
                        maxlength="2048"
                        aria-describedby="public_destination_hint{{ $errors->has('destination_url') ? ' public_destination_error' : '' }}"
                        @if ($errors->has('destination_url')) aria-invalid="true" @endif
                        @class(['is-error' => $errors->has('destination_url'), 'is-valid' => $urlState === 'valid' && ! $errors->has('destination_url')])
                    >
                    <span class="sk-field-tools">
                        <button type="button" class="sk-tool" data-sk-paste aria-label="Paste from clipboard" title="Paste from clipboard">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5.5" y="4.5" width="13" height="16" rx="2.5" stroke="currentColor" stroke-width="2.2"/><path d="M9 4.5V3.4c0-1 .8-1.9 1.9-1.9h2.2c1.1 0 1.9.9 1.9 1.9v1.1" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M9 12h6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        </button>
                        <button type="button" class="sk-tool" data-sk-clear aria-label="Clear field" title="Clear">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        </button>
                    </span>
                </div>
                <button
                    type="submit"
                    class="sk-btn"
                    wire:loading.attr="disabled"
                    wire:target="create"
                >
                    <span wire:loading.remove wire:target="create">Shorten</span>
                    <span wire:loading wire:target="create">
                        <span class="sk-spinner" aria-hidden="true"></span>
                        Shortening…
                    </span>
                </button>
            </div>
            <p class="sk-focus-note" aria-hidden="true">
                @if ($urlState === 'valid')
                    Looks good — hit Shorten when you’re ready.
                @elseif ($urlState === 'invalid')
                    Tip: links start with https://
                @else
                    Paste the full link, starting with https://
                @endif
            </p>
            @if ($this->fixablePreview)
                <div class="sk-fix" aria-live="polite">
                    <span class="sk-fix-text">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        Did you mean <strong>{{ \Illuminate\Support\Str::limit($this->fixablePreview, 56) }}</strong>?
                    </span>
                    <button type="button" wire:click="applyFix" class="sk-fix-btn">
                        Use this URL
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12h15M13.5 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            @endif
            @if ($this->duplicate)
                <div class="sk-dup" aria-live="polite">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <div>
                        <p class="sk-dup-title">Already shortened</p>
                        <p class="sk-dup-link">
                            <a href="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" target="_blank" rel="noopener">{{ $this->previewHost }}/{{ $this->duplicate->slug }}</a>
                            <button type="button" data-copy-value="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" aria-label="Copy existing short link">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5" stroke="currentColor" stroke-width="2.2"/><path d="M5.5 15V6.8A2.3 2.3 0 0 1 7.8 4.5h8.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                                Copy
                            </button>
                        </p>
                    </div>
                </div>
            @endif
            @if ($this->normalizedPreview)
                <div class="sk-preview" aria-live="polite">
                    <p class="sk-preview-will">You’re shortening <span title="{{ $this->normalizedPreview }}">{{ \Illuminate\Support\Str::limit($this->normalizedPreview, 64) }}</span></p>
                    <p class="sk-preview-get">You’ll get <strong>{{ $this->previewHost }}/••••••••</strong></p>
                </div>
            @endif
            <p class="sk-hint" id="public_destination_hint">
                <span class="sk-count" aria-hidden="true">{{ $this->charCount }} / 2048</span>
                Include <code>https://</code>. Guests get auto-made codes —
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" style="color: inherit; font-weight: 600;">log in</a> for custom slugs, shorter links &amp; click stats.
            </p>
            @error('destination_url')
                <div class="sk-oops" role="alert" id="public_destination_error">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="M12 7.5V13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="16.4" r="1.3" fill="currentColor"/></svg>
                    <div>
                        <p class="sk-oops-title">
                            @if ($errorKind === 'quota')
                                Daily limit reached
                            @elseif ($errorKind === 'too_long')
                                That link is too long
                            @elseif ($errorKind === 'throttle')
                                Slow down a moment
                            @elseif ($errorKind === 'empty')
                                Paste a link first
                            @elseif ($errorKind === 'junk')
                                That doesn’t look like a real link
                            @else
                                That link doesn’t look right
                            @endif
                        </p>
                        <p class="sk-oops-msg">{{ $message }}</p>
                        @if ($quotaExceeded)
                            <p class="sk-oops-nudge">Members get a higher daily limit — <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">log in</a> to keep going.</p>
                        @endif
                    </div>
                </div>
            @enderror

            @if (config('services.turnstile.key'))
                <div class="sk-turnstile-zone">
                    <div
                        wire:ignore
                        class="sk-turnstile"
                        x-data="{
                            wire: null,
                            widgetId: null,
                            loadTimer: null,
                            loadAttempts: 0,
                            loadFailed: false,
                            boot(component, rootEl) {
                                var self = this;
                                self.wire = component;
                                var container = rootEl.querySelector('[data-cf-container]');
                                function renderWidget() {
                                    if (typeof turnstile === 'undefined') {
                                        self.loadAttempts++;
                                        if (self.loadAttempts > 50) {
                                            self.loadFailed = true;
                                            return;
                                        }
                                        self.loadTimer = setTimeout(renderWidget, 100);
                                        return;
                                    }
                                    if (self.widgetId !== null || ! container) return;
                                    turnstile.ready(function () {
                                        self.widgetId = turnstile.render(container, {
                                            sitekey: '{{ config('services.turnstile.key') }}',
                                            action: '{{ \App\Services\TurnstileService::ACTION }}',
                                            theme: 'light',
                                            callback: function (token) {
                                                self.wire.set('turnstile_token', token);
                                            },
                                            'expired-callback': function () {
                                                self.wire.set('turnstile_token', null);
                                            },
                                            'timeout-callback': function () {
                                                self.wire.set('turnstile_token', null);
                                            },
                                            'error-callback': function () {
                                                self.wire.set('turnstile_token', null);
                                            },
                                            'unsupported-callback': function () {
                                                self.wire.set('turnstile_token', null);
                                            }
                                        });
                                    });
                                }
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
                        <p x-show="loadFailed" class="sk-hint" style="display: none;">The security challenge failed to load — an ad-blocker may be blocking it. Allow this site and reload the page.</p>
                    </div>
                </div>
            @endif

            @error('turnstile_token')
                <div class="sk-oops" role="alert" id="public_turnstile_error">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="M12 7.5V13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="16.4" r="1.3" fill="currentColor"/></svg>
                    <div>
                        <p class="sk-oops-title">Security check</p>
                        <p class="sk-oops-msg">{{ $message }}</p>
                    </div>
                </div>
            @enderror
        </form>
    @endif

    <div class="sk-tray" data-sk-tray hidden>
        <p class="sk-tray-title">your recent links on this device</p>
        <ul class="sk-tray-list" data-sk-recent></ul>
        <button type="button" class="sk-tray-clear" data-sk-tray-clear>clear history</button>
    </div>
</div>

<script>
(function () {
    if (typeof document === 'undefined') return;

    /* --- copy to clipboard (result + tray links) --- */
    if (!document.documentElement.hasAttribute('data-sk-copy-bound')) {
        document.documentElement.setAttribute('data-sk-copy-bound', '1');

        document.addEventListener('click', async function (event) {
            var button = event.target.closest('[data-copy-value]');
            if (!button) return;

            var value = button.getAttribute('data-copy-value') || '';
            var done = false;

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(value);
                    done = true;
                }
            } catch (e) {
                done = false;
            }

            if (!done) {
                try {
                    var ta = document.createElement('textarea');
                    ta.value = value;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    done = document.execCommand('copy');
                    document.body.removeChild(ta);
                } catch (e) {
                    done = false;
                }
            }

            var scope = button.closest('[role="status"]') || button.closest('[data-sk-tray]');
            var feedback = scope ? scope.querySelector('[data-copy-feedback]') : null;
            var label = button.querySelector('[data-copy-label]') || null;

            if (done) {
                if (label) {
                    var original = label.textContent;
                    label.textContent = 'Copied';
                    if (feedback) feedback.hidden = false;
                    setTimeout(function () {
                        label.textContent = original;
                        if (feedback) feedback.hidden = true;
                    }, 2000);
                } else {
                    var originalHtml = button.innerHTML;
                    button.innerHTML = 'Copied';
                    if (feedback) feedback.hidden = false;
                    setTimeout(function () {
                        button.innerHTML = originalHtml;
                        if (feedback) feedback.hidden = true;
                    }, 2000);
                }
            } else if (feedback) {
                feedback.textContent = 'Copy failed — select the link manually.';
                feedback.hidden = false;
            }
        });
    }

    /* --- paste + clear helpers (sync back to Livewire) --- */
    function wireInput(root) {
        return root ? root.querySelector('#public_destination_url') : null;
    }

    document.addEventListener('click', async function (event) {
        var paste = event.target.closest('[data-sk-paste]');
        var clear = event.target.closest('[data-sk-clear]');
        if (!paste && !clear) return;

        var root = (paste || clear).closest('[data-sk-form]');
        var input = wireInput(root || document);
        if (!input) return;

        if (paste) {
            try {
                var text = await navigator.clipboard.readText();
                /* Grab the first URL when clipboard holds prose. */
                var found = (text || '').match(/https?:\/\/[^\s<>"']+/);
                input.value = (found ? found[0] : (text || '').trim()).slice(0, 2048);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            } catch (e) {
                input.focus();
            }
        } else {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }
    });

    /* --- recent-links tray (localStorage, this device only) --- */
    var TRAY_KEY = 'sk-recent-links';
    var TRAY_MAX = 5;

    function readTray() {
        try {
            var list = JSON.parse(localStorage.getItem(TRAY_KEY) || '[]');
            return Array.isArray(list) ? list : [];
        } catch (e) {
            return [];
        }
    }

    function writeTray(list) {
        try {
            localStorage.setItem(TRAY_KEY, JSON.stringify(list.slice(0, TRAY_MAX)));
        } catch (e) { /* private mode */ }
    }

    function paintTray() {
        var tray = document.querySelector('[data-sk-tray]');
        var list = tray ? tray.querySelector('[data-sk-recent]') : null;
        if (!tray || !list) return;

        var items = readTray();
        tray.hidden = items.length === 0;
        list.innerHTML = '';

        items.forEach(function (item) {
            var li = document.createElement('li');

            var link = document.createElement('a');
            link.href = item.short;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = item.short.replace(/^https?:\/\//, '');

            var copy = document.createElement('button');
            copy.type = 'button';
            copy.setAttribute('data-copy-value', item.short);
            copy.setAttribute('aria-label', 'Copy ' + item.short);
            copy.textContent = 'copy';

            li.appendChild(link);
            li.appendChild(copy);
            list.appendChild(li);
        });
    }

    function recordFromResult(scope) {
        var link = scope.querySelector('[data-sk-result-link]');
        if (!link) return;

        var short = link.href;
        var original = scope.querySelector('[data-sk-result-original]');

        var items = readTray().filter(function (item) { return item.short !== short; });
        items.unshift({
            short: short,
            original: original ? original.getAttribute('title') || '' : '',
            at: Date.now(),
        });
        writeTray(items);
        paintTray();
    }

    /* Livewire swaps the form/result via morphs — watch for fresh results. */
    var observed = false;
    function observe() {
        var zone = document.querySelector('[data-sk-form]');
        if (!zone || observed) return;
        observed = true;

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('[role="status"]')) recordFromResult(node);
                    var nested = node.querySelector ? node.querySelector('[role="status"]') : null;
                    if (nested) recordFromResult(nested);
                });
            });
        }).observe(zone, { childList: true, subtree: true });

        var initial = zone.querySelector('[role="status"]');
        if (initial) recordFromResult(initial);
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-sk-tray-clear]')) {
            writeTray([]);
            paintTray();
        }
    });

    paintTray();
    observe();
    document.addEventListener('livewire:navigated', function () {
        observed = false;
        paintTray();
        observe();
    });
})();
</script>
