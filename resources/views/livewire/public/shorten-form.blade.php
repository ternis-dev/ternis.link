<div class="sk-card tilt-l">
    <span class="sk-tape" aria-hidden="true"></span>
    {{-- 22 · sparkle --}}
    <svg class="dk dk-faint dk-hide-sm" style="top: -16px; left: 16px; transform: rotate(-12deg);" width="22" height="22" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>
    {{-- 23 · sparkle --}}
    <svg class="dk dk-faint dk-hide-sm" style="top: -10px; right: 28px; transform: rotate(14deg);" width="16" height="16" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>

    <h2 class="sk-form-title">
        {{-- 24 · chain link --}}
        <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M13 19 L19 13 M15 8 L18 5 a5.5 5.5 0 0 1 8 8 l-3 3 M17 24 l-3 3 a5.5 5.5 0 0 1-8-8 l3-3" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
        Shorten a link — no account needed
    </h2>
    <p class="sk-form-sub">Paste any long URL. Guests get an auto-generated 8-character link on this domain.</p>

    @if ($shortUrl)
        <div class="sk-result" role="status" aria-live="polite">
            {{-- 25 · result burst --}}
            <svg class="dk" style="top: -24px; right: 4px; transform: rotate(8deg);" width="46" height="46" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 3 L27 17 L41 12 L31 23 L45 28 L30 30 L34 44 L24 33 L14 44 L18 30 L3 28 L17 23 L7 12 L21 17 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
            <p class="sk-result-kicker">
                {{-- 26 · done check --}}
                <svg width="22" height="20" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Done — your short link is ready
            </p>
            <div class="sk-result-row">
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-result-link">{{ $shortUrl }}</a>
                <button
                    type="button"
                    class="sk-copy"
                    data-copy-value="{{ $shortUrl }}"
                    aria-label="Copy short link to clipboard"
                >
                    Copy
                </button>
            </div>
            <p class="sk-copied" data-copy-feedback hidden>Copied to clipboard.</p>
            @if ($originalUrl)
                <p class="sk-original" title="{{ $originalUrl }}">
                    Shortened from <span>{{ \Illuminate\Support\Str::limit($originalUrl, 72) }}</span>
                </p>
            @endif
            <div class="sk-result-actions">
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="sk-open">Open link ↗</a>
                <button type="button" wire:click="resetForm" class="sk-again">
                    {{-- 27 · redo arrow --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12 C 4 7, 8 4, 13 4 C 18 4, 21 8, 20.5 12 C 20 16, 16 20, 11 20 M11 20 l-4 -1 M11 20 l1 -4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Shorten another
                </button>
            </div>
        </div>
    @else
        <form wire:submit="create" novalidate>
            <label class="sk-label" for="public_destination_url">Destination URL
                {{-- arrow into the field --}}
                <svg style="display:inline-block; vertical-align: -4px;" width="30" height="14" viewBox="0 0 40 18" fill="none" aria-hidden="true"><path d="M2 12 C 12 10, 22 9, 33 10 M33 10 l-7 -4 M33 10 l-7 4" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </label>
            <div class="sk-input-row">
                <input
                    type="url"
                    id="public_destination_url"
                    name="destination_url"
                    wire:model="destination_url"
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
                >
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
            <p class="sk-hint" id="public_destination_hint">
                Include <code>https://</code>. Guests get auto-made codes —
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" style="color: inherit; font-weight: 600;">log in</a> for custom slugs, shorter links &amp; click stats.
                {{-- asterisk --}}
                <svg style="display:inline-block; vertical-align: super;" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 2 L8 14 M2.5 5 L13.5 11 M13.5 5 L2.5 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </p>
            @error('destination_url')
                <p class="sk-error" role="alert">{{ $message }}</p>
            @enderror
        </form>
    @endif
</div>

<script>
(function () {
    if (typeof document === 'undefined') return;
    if (document.documentElement.hasAttribute('data-sk-copy-bound')) return;
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

        var scope = button.closest('[role="status"]');
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
})();
</script>
