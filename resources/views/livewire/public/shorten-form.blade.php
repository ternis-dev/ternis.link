<div class="sk-card{{ ($compact || $minimal) ? ' sk-card-compact' : '' }}{{ $minimal ? ' sk-card-minimal' : '' }}" data-sk-form data-t-copied="{{ $this->t('copy.done') }}" data-t-copyfail="{{ $this->t('result.copyfail') }}">
    @if (! $minimal)
    <span class="sk-tape" aria-hidden="true"></span>
    {{-- sparkles --}}
    <svg class="dk dk-faint dk-hide-sm" style="top: -14px; left: 18px;" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
    <svg class="dk dk-faint dk-hide-sm" style="top: -8px; right: 30px;" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>
    @endif

    <div class="sk-form-head">
        <h2 class="sk-form-title">
            @if (! $minimal)
            {{-- link --}}
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
            {{ $this->t('form.title') }}{{ $minimal ? '' : $this->t('form.title.suffix') }}
        </h2>
        @if (! $minimal)
        <div class="sk-meter" title="{{ $this->t('meter.title') }}">
            <span class="sk-meter-boxes" aria-hidden="true">
                @for ($i = 0; $i < 10; $i++)
                    <span class="sk-meter-box{{ $i < (int) ceil($this->quotaLeft / 5) ? ' is-full' : '' }}"></span>
                @endfor
            </span>
            <span class="sk-meter-text">{{ $this->t('meter.text', $this->quotaLeft, \App\Services\LinkService::ANONYMOUS_DAILY_LIMIT) }}</span>
        </div>
        @endif
    </div>
    <p class="sk-form-sub">{{ $minimal ? $this->t('form.sub.minimal') : $this->t('form.sub') }}</p>

    @if ($shortUrl)
        <div class="sk-ticket" role="status" aria-live="polite">
            @if (! $minimal)
            {{-- dotted ring --}}
            <svg class="dk" style="top: -22px; right: 8px;" width="44" height="44" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="24" r="15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-dasharray="4 5"/><path d="M24 13.5c.5 3.4 1.5 4.9 5.3 5.3-3.8.4-4.8 1.9-5.3 5.3-.5-3.4-1.5-4.9-5.3-5.3 3.8-.4 4.8-1.9 5.3-5.3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            @endif
            <p class="sk-result-kicker">
                {{-- check --}}
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m8.2 12.3 2.5 2.5 5.1-5.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Done — your short link is ready
            </p>
            <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-ticket-link" data-sk-result-link>{{ $shortUrl }}</a>
            @if ($originalUrl)
                <p class="sk-original" title="{{ $originalUrl }}" data-sk-result-original>
                    {{ $this->t('result.from') }} <span>{{ \Illuminate\Support\Str::limit($originalUrl, 72) }}</span>
                </p>
            @endif
            <div class="sk-ticket-perf" aria-hidden="true"></div>
            <div class="sk-result-actions">
                <button
                    type="button"
                    class="sk-copy"
                    data-copy-value="{{ $shortUrl }}"
                    aria-label="{{ $this->t('result.copy.aria') }}"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5" stroke="currentColor" stroke-width="2.2"/><path d="M5.5 15V6.8A2.3 2.3 0 0 1 7.8 4.5h8.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                    {{ $this->t('result.copy') }}
                </button>
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-open">
                    {{ $this->t('result.open') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 4.5h5.5V10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 4.5 11 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M19.5 13.5V18a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2H11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                </a>
                <a href="{{ url('/v1/qr?url='.urlencode($shortUrl).'&format=png') }}" target="_blank" rel="noopener" class="sk-open">{{ $this->t('result.qr') }}</a>
                <button type="button" wire:click="resetForm" class="sk-again">
                    {{-- refresh --}}
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12a7.5 7.5 0 1 1 2.2 5.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M4.5 17.5v-4h4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $this->t('result.again') }}
                </button>
            </div>
            <p class="sk-copied" data-copy-feedback hidden>{{ $this->t('result.copied') }}</p>
        </div>
    @else
        <form wire:submit="create" novalidate>
            <div class="sk-label-row">
                <label class="sk-label" for="public_destination_url">{{ $this->t('label.destination') }}</label>
                <span class="sk-url-state" aria-live="polite">
                    @if ($urlState === 'valid')
                        <span class="sk-state is-valid">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m8.2 12.3 2.5 2.5 5.1-5.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $this->t('state.valid') }}
                        </span>
                    @elseif ($urlState === 'invalid')
                        <span class="sk-state is-invalid">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m9.2 9.2 5.6 5.6M14.8 9.2l-5.6 5.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            {{ $this->t('state.invalid') }}
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
                        placeholder="{{ $this->t('input.placeholder') }}"
                        required
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        inputmode="url"
                        maxlength="{{ \App\Services\LinkService::PUBLIC_MAX_URL_LENGTH }}"
                        aria-describedby="public_destination_hint{{ $errors->has('destination_url') ? ' public_destination_error' : '' }}"
                        @if ($errors->has('destination_url')) aria-invalid="true" @endif
                        @class(['is-error' => $errors->has('destination_url'), 'is-valid' => $urlState === 'valid' && ! $errors->has('destination_url')])
                    >
                    <span class="sk-field-tools">
                        <button type="button" class="sk-tool" data-sk-paste aria-label="{{ $this->t('tool.paste') }}" title="{{ $this->t('tool.paste') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5.5" y="4.5" width="13" height="16" rx="2.5" stroke="currentColor" stroke-width="2.2"/><path d="M9 4.5V3.4c0-1 .8-1.9 1.9-1.9h2.2c1.1 0 1.9.9 1.9 1.9v1.1" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M9 12h6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        </button>
                        <button type="button" class="sk-tool" data-sk-clear aria-label="{{ $this->t('tool.clear') }}" title="{{ $this->t('tool.clear') }}">
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
                    <span wire:loading.remove wire:target="create">{{ $this->t('submit') }}</span>
                    <span wire:loading wire:target="create">
                        <span class="sk-spinner" aria-hidden="true"></span>
                        {{ $this->t('submitting') }}
                    </span>
                </button>
            </div>
            @if (! $minimal)
            <p class="sk-focus-note" aria-hidden="true">
                @if ($urlState === 'valid')
                    {{ $this->t('focus.valid') }}
                @elseif ($urlState === 'invalid')
                    {{ $this->t('focus.invalid') }}
                @else
                    {{ $this->t('focus.idle') }}
                @endif
            </p>
            @endif
            @if ($this->fixablePreview)
                <div class="sk-fix" aria-live="polite">
                    <span class="sk-fix-text">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        {{ $this->t('fix.text') }} <strong>{{ \Illuminate\Support\Str::limit($this->fixablePreview, 56) }}</strong>?
                    </span>
                    <button type="button" wire:click="applyFix" class="sk-fix-btn">
                        {{ $this->t('fix.btn') }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12h15M13.5 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            @endif
            @if ($this->duplicate)
                <div class="sk-dup" aria-live="polite">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <div>
                        <p class="sk-dup-title">{{ $this->t('dup.title') }}</p>
                        <p class="sk-dup-link">
                            <a href="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" target="_blank" rel="noopener">{{ $this->previewHost }}/{{ $this->duplicate->slug }}</a>
                            <button type="button" data-copy-value="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" aria-label="{{ $this->t('dup.copy.aria') }}">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5" stroke="currentColor" stroke-width="2.2"/><path d="M5.5 15V6.8A2.3 2.3 0 0 1 7.8 4.5h8.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                                {{ $this->t('dup.copy') }}
                            </button>
                        </p>
                    </div>
                </div>
            @endif
            @if (! $minimal && $this->normalizedPreview)
                <div class="sk-preview" aria-live="polite">
                    <p class="sk-preview-will">{{ $this->t('preview.will') }} <span title="{{ $this->normalizedPreview }}">{{ \Illuminate\Support\Str::limit($this->normalizedPreview, 64) }}</span></p>
                    <p class="sk-preview-get">{{ $this->t('preview.get') }} <strong>{{ $this->previewHost }}/••••••••</strong></p>
                </div>
            @endif
            <p class="sk-hint" id="public_destination_hint">
                @if ($minimal)
                    {!! $this->t('hint.minimal') !!}
                @else
                    <span class="sk-count" aria-hidden="true">{{ $this->charCount }} / {{ \App\Services\LinkService::PUBLIC_MAX_URL_LENGTH }}</span>
                    {!! $this->t('hint.full', \App\Support\DomainUrls::dashboard('/login')) !!}
                @endif
            </p>
            @error('destination_url')
                <div class="sk-oops" role="alert" id="public_destination_error">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="M12 7.5V13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="16.4" r="1.3" fill="currentColor"/></svg>
                    <div>
                        <p class="sk-oops-title">
                            @if ($errorKind === 'quota')
                                {{ $this->t('oops.title.quota') }}
                            @elseif ($errorKind === 'too_long')
                                {{ $this->t('oops.title.too_long') }}
                            @elseif ($errorKind === 'throttle')
                                {{ $this->t('oops.title.throttle') }}
                            @elseif ($errorKind === 'empty')
                                {{ $this->t('oops.title.empty') }}
                            @elseif ($errorKind === 'junk')
                                {{ $this->t('oops.title.junk') }}
                            @else
                                {{ $this->t('oops.title.invalid') }}
                            @endif
                        </p>
                        <p class="sk-oops-msg">{{ $message }}</p>
                        @if ($quotaExceeded)
                            <p class="sk-oops-nudge">{!! $this->t('oops.nudge', \App\Support\DomainUrls::dashboard('/login')) !!}</p>
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
                            cfNote: '{{ $this->t('cf.loading') }}',
                            boot(component, rootEl) {
                                var self = this;
                                self.wire = component;
                                var container = rootEl.querySelector('[data-cf-container]');
                                var LOADING = '{{ $this->t('cf.loading') }}';
                                function renderWidget() {
                                    if (typeof turnstile === 'undefined') {
                                        self.loadAttempts++;
                                        if (self.loadAttempts > 50) {
                                            self.loadFailed = true;
                                            self.cfNote = '';
                                            return;
                                        }
                                        self.loadTimer = setTimeout(renderWidget, 100);
                                        return;
                                    }
                                    if (self.widgetId !== null || ! container) return;
                                    turnstile.ready(function () {
                                        try {
                                            self.widgetId = turnstile.render(container, {
                                                sitekey: '{{ config('services.turnstile.key') }}',
                                                action: '{{ \App\Services\TurnstileService::ACTION }}',
                                                theme: 'light',
                                                callback: function (token) {
                                                    self.cfNote = '';
                                                    self.wire.set('turnstile_token', token);
                                                },
                                                'expired-callback': function () {
                                                    self.cfNote = '{{ $this->t('cf.expired') }}';
                                                    self.wire.set('turnstile_token', null);
                                                },
                                                'timeout-callback': function () {
                                                    self.cfNote = '{{ $this->t('cf.timeout') }}';
                                                    self.wire.set('turnstile_token', null);
                                                },
                                                'error-callback': function () {
                                                    self.cfNote = '{{ $this->t('cf.error') }}';
                                                    self.wire.set('turnstile_token', null);
                                                },
                                                'unsupported-callback': function () {
                                                    self.cfNote = '{{ $this->t('cf.unsupported') }}';
                                                    self.wire.set('turnstile_token', null);
                                                }
                                            });
                                        } catch (e) {
                                            self.cfNote = '{{ $this->t('cf.startfail') }}';
                                            return;
                                        }
                                        // Invisible render (Invisible-type key or blocked
                                        // frame): say so instead of showing nothing.
                                        setTimeout(function () {
                                            if (self.widgetId === null || self.cfNote !== LOADING) return;
                                            if (container.offsetHeight < 10) {
                                                self.cfNote = '{{ $this->t('cf.invisible') }}';
                                            } else {
                                                self.cfNote = '';
                                            }
                                        }, 1500);
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
                        <p x-show="cfNote" x-text="cfNote" class="sk-hint" style="display: none;"></p>
                        <p x-show="loadFailed" class="sk-hint" style="display: none;">{{ $this->t('cf.blocked') }}</p>
                    </div>
                </div>
            @endif

            @error('turnstile_token')
                <div class="sk-oops" role="alert" id="public_turnstile_error">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="M12 7.5V13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="16.4" r="1.3" fill="currentColor"/></svg>
                    <div>
                        <p class="sk-oops-title">{{ $this->t('oops.title.security') }}</p>
                        <p class="sk-oops-msg">{{ $message }}</p>
                    </div>
                </div>
            @enderror
        </form>
    @endif

    @if (! $minimal)
    <div class="sk-tray" data-sk-tray data-t-copy="{{ $this->t('tray.copy') }}" hidden>
        <div class="sk-tray-head">
            <p class="sk-tray-title">{{ $this->t('tray.title') }}</p>
            <button type="button" class="sk-tray-toggle" data-sk-tray-toggle data-t-show="{{ $this->t('tray.show') }}" data-t-hide="{{ $this->t('tray.hide') }}" aria-expanded="true" aria-controls="sk-recent-links">
                {{ $this->t('tray.hide') }}
            </button>
        </div>
        <div id="sk-recent-links" data-sk-tray-content>
            <p class="sk-tray-empty" data-sk-tray-empty>{{ $this->t('tray.empty') }}</p>
            <ul class="sk-tray-list" data-sk-recent></ul>
            <button type="button" class="sk-tray-clear" data-sk-tray-clear>{{ $this->t('tray.clear') }}</button>
        </div>
    </div>
    @endif
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
            var form = button.closest('[data-sk-form]');
            var doneWord = (form && form.dataset.tCopied) || 'Copied';
            var failText = (form && form.dataset.tCopyfail) || 'Copy failed — select the link manually.';

            if (done) {
                if (label) {
                    var original = label.textContent;
                    label.textContent = doneWord;
                    if (feedback) feedback.hidden = false;
                    setTimeout(function () {
                        label.textContent = original;
                        if (feedback) feedback.hidden = true;
                    }, 2000);
                } else {
                    var originalHtml = button.innerHTML;
                    button.innerHTML = doneWord;
                    if (feedback) feedback.hidden = false;
                    setTimeout(function () {
                        button.innerHTML = originalHtml;
                        if (feedback) feedback.hidden = true;
                    }, 2000);
                }
            } else if (feedback) {
                feedback.textContent = failText;
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
                input.value = (found ? found[0] : (text || '').trim()).slice(0, {{ \App\Services\LinkService::PUBLIC_MAX_URL_LENGTH }});
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
    var TRAY_COLLAPSED_KEY = 'sk-recent-links-collapsed';
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
        tray.hidden = false;
        var empty = tray.querySelector('[data-sk-tray-empty]');
        if (empty) empty.hidden = items.length !== 0;
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
            copy.setAttribute('aria-label', ((tray.dataset && tray.dataset.tCopy) || 'copy') + ' ' + item.short);
            copy.textContent = (tray.dataset && tray.dataset.tCopy) || 'copy';

            var qr = document.createElement('a');
            qr.className = 'sk-tray-qr';
            qr.href = '/v1/qr?url=' + encodeURIComponent(item.short) + '&format=png';
            qr.target = '_blank';
            qr.rel = 'noopener';
            qr.textContent = 'qr';

            li.appendChild(link);
            li.appendChild(copy);
            li.appendChild(qr);
            list.appendChild(li);
        });
    }

    function setTrayCollapsed(collapsed) {
        var tray = document.querySelector('[data-sk-tray]');
        var content = tray ? tray.querySelector('[data-sk-tray-content]') : null;
        var toggle = tray ? tray.querySelector('[data-sk-tray-toggle]') : null;
        if (!tray || !content || !toggle) return;

        tray.classList.toggle('is-collapsed', collapsed);
        content.hidden = collapsed;
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.textContent = collapsed ? (toggle.dataset.tShow || 'show') : (toggle.dataset.tHide || 'hide');
        try {
            localStorage.setItem(TRAY_COLLAPSED_KEY, collapsed ? '1' : '0');
        } catch (e) { /* private mode */ }
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
        if (event.target.closest('[data-sk-tray-toggle]')) {
            var tray = document.querySelector('[data-sk-tray]');
            setTrayCollapsed(!tray || !tray.classList.contains('is-collapsed'));
        }
        if (event.target.closest('[data-sk-tray-clear]')) {
            writeTray([]);
            paintTray();
        }
    });

    paintTray();
    try {
        setTrayCollapsed(localStorage.getItem(TRAY_COLLAPSED_KEY) === '1');
    } catch (e) { /* private mode */ }
    observe();
    document.addEventListener('livewire:navigated', function () {
        observed = false;
        paintTray();
        observe();
    });
})();
</script>
