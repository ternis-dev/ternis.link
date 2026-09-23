<div class="nz-form">
    <h2 class="nz-form-title">Shorten a link — no account needed</h2>
    <p class="nz-form-sub">Paste any long URL. Guests get an auto-generated 8-character link on this domain.</p>

    @if ($shortUrl)
        <div class="nz-result" role="status" aria-live="polite">
            <p class="nz-result-kicker">
                <span class="nz-check" aria-hidden="true">✓</span>
                Done — your short link is ready
            </p>
            <div class="nz-result-row">
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="nz-result-link">{{ $shortUrl }}</a>
                <button
                    type="button"
                    class="nz-copy"
                    data-copy-value="{{ $shortUrl }}"
                    aria-label="Copy short link to clipboard"
                >
                    Copy
                </button>
            </div>
            <p class="nz-copied" data-copy-feedback hidden>Copied to clipboard.</p>
            @if ($originalUrl)
                <p class="nz-original" title="{{ $originalUrl }}">
                    Shortened from <span>{{ \Illuminate\Support\Str::limit($originalUrl, 72) }}</span>
                </p>
            @endif
            <div class="nz-result-actions">
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="nz-open">Open link ↗</a>
                <button type="button" wire:click="resetForm" class="nz-again">Shorten another</button>
            </div>
        </div>
    @else
        <form wire:submit="create" novalidate>
            <div class="nz-field">
                <label class="nz-label" for="public_destination_url">Destination URL</label>
                <div class="nz-input-row">
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
                        class="nz-submit"
                        wire:loading.attr="disabled"
                        wire:target="create"
                    >
                        <span wire:loading.remove wire:target="create">Shorten</span>
                        <span wire:loading wire:target="create" class="nz-spinner-row">
                            <span class="nz-spinner" aria-hidden="true"></span>
                            Shortening…
                        </span>
                    </button>
                </div>
                <p class="nz-hint" id="public_destination_hint">
                    Include <code>https://</code>. Guests get auto-made codes —
                    <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">log in</a> for custom slugs, shorter links &amp; click stats.
                </p>
                @error('destination_url')
                    <p class="nz-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </form>
    @endif
</div>

<script>
(function () {
    if (typeof document === 'undefined') return;
    if (document.documentElement.hasAttribute('data-nz-copy-bound')) return;
    document.documentElement.setAttribute('data-nz-copy-bound', '1');

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

        var scope = button.closest('.nz-result');
        var feedback = scope ? scope.querySelector('[data-copy-feedback]') : null;

        if (done) {
            var original = button.textContent;
            button.textContent = 'Copied ✓';
            button.classList.add('is-copied');
            if (feedback) feedback.hidden = false;
            setTimeout(function () {
                button.textContent = original;
                button.classList.remove('is-copied');
                if (feedback) feedback.hidden = true;
            }, 2000);
        } else if (feedback) {
            feedback.textContent = 'Copy failed — select the link manually.';
            feedback.hidden = false;
        }
    });
})();
</script>
