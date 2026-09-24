<div class="sk-card tilt-l" data-sk-form>
    <span class="sk-tape" aria-hidden="true"></span>
    {{-- sparkle --}}
    <svg class="dk dk-faint dk-hide-sm" style="top: -16px; left: 16px; transform: rotate(-12deg);" width="22" height="22" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>
    {{-- sparkle --}}
    <svg class="dk dk-faint dk-hide-sm" style="top: -10px; right: 28px; transform: rotate(14deg);" width="16" height="16" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>

    <div class="sk-form-head">
        <h2 class="sk-form-title">
            {{-- chain link --}}
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M13 19 L19 13 M15 8 L18 5 a5.5 5.5 0 0 1 8 8 l-3 3 M17 24 l-3 3 a5.5 5.5 0 0 1-8-8 l3-3" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
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
            {{-- burst --}}
            <svg class="dk" style="top: -24px; right: 6px; transform: rotate(8deg);" width="46" height="46" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 3 L27 17 L41 12 L31 23 L45 28 L30 30 L34 44 L24 33 L14 44 L18 30 L3 28 L17 23 L7 12 L21 17 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
            <p class="sk-result-kicker">
                {{-- done check --}}
                <svg width="22" height="20" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
                    Copy
                </button>
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-open">Open link ↗</a>
                <button type="button" wire:click="resetForm" class="sk-again">
                    {{-- redo arrow --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12 C 4 7, 8 4, 13 4 C 18 4, 21 8, 20.5 12 C 20 16, 16 20, 11 20 M11 20 l-4 -1 M11 20 l1 -4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Shorten another
                </button>
            </div>
            <p class="sk-copied" data-copy-feedback hidden>Copied to clipboard.</p>
        </div>
    @else
        <form wire:submit="create" novalidate>
            <div class="sk-label-row">
                <label class="sk-label" for="public_destination_url">Destination URL
                    {{-- arrow into the field --}}
                    <svg style="display:inline-block; vertical-align: -4px;" width="30" height="14" viewBox="0 0 40 18" fill="none" aria-hidden="true"><path d="M2 12 C 12 10, 22 9, 33 10 M33 10 l-7 -4 M33 10 l-7 4" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </label>
                <span class="sk-url-state" aria-hidden="true">
                    @if ($urlState === 'valid')
                        <svg width="20" height="18" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="#2f7a3d" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @elseif ($urlState === 'invalid')
                        <svg width="18" height="18" viewBox="0 0 22 22" fill="none" aria-hidden="true"><path d="M5 5 C 9 9, 13 13, 17 17 M17 5 C 13 9, 9 13, 5 17" stroke="#b33636" stroke-width="2.6" stroke-linecap="round"/></svg>
                    @endif
                </span>
            </div>
            <div class="sk-input-row">
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
                    aria-describedby="public_destination_hint"
                    @if ($errors->has('destination_url')) aria-invalid="true" @endif
                    @class(['is-error' => $errors->has('destination_url'), 'is-valid' => $urlState === 'valid' && ! $errors->has('destination_url')])
                >
                <button type="button" class="sk-tool" data-sk-paste aria-label="Paste from clipboard" title="Paste from clipboard">
                    <svg width="18" height="20" viewBox="0 0 22 26" fill="none" aria-hidden="true"><path d="M6 4 L6 23 L16 23 L16 4 M6 7 L4 7 L4 23 L18 23 L18 7 M8 4 C 8 2, 14 2, 14 4 M8 4 L6 4 M14 4 L16 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="sk-tool" data-sk-clear aria-label="Clear field" title="Clear">
                    <svg width="16" height="16" viewBox="0 0 22 22" fill="none" aria-hidden="true"><path d="M5 5 C 9 9, 13 13, 17 17 M17 5 C 13 9, 9 13, 5 17" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                </button>
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
                    looking good — hit Shorten when ready ↓
                @elseif ($urlState === 'invalid')
                    hmm — that needs to start with https:// …
                @else
                    paste anything long in here ↓
                @endif
            </p>
            @if ($this->fixablePreview)
                <div class="sk-fix" aria-live="polite">
                    <span>did you mean <strong>{{ \Illuminate\Support\Str::limit($this->fixablePreview, 56) }}</strong>?</span>
                    <button type="button" wire:click="applyFix" class="sk-fix-btn">yes, fix it ✓</button>
                </div>
            @endif
            @if ($this->duplicate)
                <div class="sk-dup" aria-live="polite">
                    <svg width="22" height="22" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M13 19 L19 13 M15 8 L18 5 a5.5 5.5 0 0 1 8 8 l-3 3 M17 24 l-3 3 a5.5 5.5 0 0 1-8-8 l3-3" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
                    <div>
                        <p class="sk-dup-title">already on file — no need to shorten twice!</p>
                        <p class="sk-dup-link">
                            <a href="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" target="_blank" rel="noopener">{{ $this->previewHost }}/{{ $this->duplicate->slug }}</a>
                            <button type="button" data-copy-value="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" aria-label="Copy existing short link">copy</button>
                        </p>
                    </div>
                </div>
            @endif
            @if ($this->normalizedPreview)
                <div class="sk-preview" aria-live="polite">
                    <p class="sk-preview-will">will shorten <span title="{{ $this->normalizedPreview }}">{{ \Illuminate\Support\Str::limit($this->normalizedPreview, 64) }}</span></p>
                    <p class="sk-preview-get">you'll get <strong>{{ $this->previewHost }}/○○○○○○○○</strong></p>
                </div>
            @endif
            <p class="sk-hint" id="public_destination_hint">
                <span class="sk-count" aria-hidden="true">{{ $this->charCount }} / 2048</span>
                Include <code>https://</code>. Guests get auto-made codes —
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" style="color: inherit; font-weight: 600;">log in</a> for custom slugs, shorter links &amp; click stats.
                {{-- asterisk --}}
                <svg style="display:inline-block; vertical-align: super;" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 2 L8 14 M2.5 5 L13.5 11 M13.5 5 L2.5 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </p>
            @error('destination_url')
                <div class="sk-oops" role="alert">
                    <svg width="34" height="34" viewBox="0 0 38 38" fill="none" aria-hidden="true"><path d="M19 4 C 10 4, 4 11, 4 19 C 4 27, 10 34, 19 34 C 28 34, 34 27, 34 19 C 34 11, 28 4, 19 4" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/><path d="M13 13 C 17 17, 21 21, 25 25 M25 13 C 21 17, 17 21, 13 25" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
                    <div>
                        <p class="sk-oops-title">
                            @if ($errorKind === 'quota')
                                out of today's pile!
                            @elseif ($errorKind === 'too_long')
                                whoa, that's a long one!
                            @elseif ($errorKind === 'throttle')
                                take a breath!
                            @else
                                oops — that didn't stick!
                            @endif
                        </p>
                        <p class="sk-oops-msg">{{ $message }}</p>
                        @if ($quotaExceeded)
                            <p class="sk-oops-nudge">Members get a bigger daily pile — <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">log in</a> and keep going.</p>
                        @endif
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

            if (done) {
                var original = button.textContent;
                button.textContent = 'Copied ✓';
                if (feedback) feedback.hidden = false;
                setTimeout(function () {
                    button.textContent = original;
                    if (feedback) feedback.hidden = true;
                }, 2000);
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
