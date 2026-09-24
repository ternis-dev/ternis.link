<x-ui.card>
    <h2 class="font-display text-xl font-bold tracking-tight">Shorten a link — no account needed</h2>
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Paste any long URL. Guests get an auto-generated 8-character link on this domain.</p>

    @if ($shortUrl)
        <div class="mt-5 rounded-lg border-2 border-neutral-900 p-4 dark:border-white" role="status" aria-live="polite">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <span aria-hidden="true" class="flex h-5 w-5 items-center justify-center rounded-full bg-neutral-900 text-[11px] text-white dark:bg-white dark:text-neutral-900">✓</span>
                Done — your short link is ready
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate font-mono text-sm underline underline-offset-2">{{ $shortUrl }}</a>
                <x-ui.button
                    size="sm"
                    variant="primary"
                    data-copy-value="{{ $shortUrl }}"
                    aria-label="Copy short link to clipboard"
                >Copy</x-ui.button>
            </div>
            <p class="mt-2 text-xs text-neutral-500" data-copy-feedback hidden>Copied to clipboard.</p>
            @if ($originalUrl)
                <p class="mt-2 truncate text-xs text-neutral-500 dark:text-neutral-500" title="{{ $originalUrl }}">
                    Shortened from <span>{{ \Illuminate\Support\Str::limit($originalUrl, 72) }}</span>
                </p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button href="{{ $shortUrl }}" size="sm" target="_blank" rel="noopener">Open link ↗</x-ui.button>
                <x-ui.button wire:click="resetForm" size="sm">Shorten another</x-ui.button>
            </div>
        </div>
    @else
        <form wire:submit="create" novalidate class="mt-5">
            <label class="mb-1.5 block text-sm font-medium text-neutral-600 dark:text-neutral-400" for="public_destination_url">Destination URL</label>
            <div class="flex flex-col gap-2 sm:flex-row">
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
                    class="w-full flex-1 rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-2 focus:ring-neutral-900/15 focus:outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:placeholder:text-neutral-600 dark:focus:border-white dark:focus:ring-white/15"
                >
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="create"
                    class="shrink-0"
                >
                    <span wire:loading.remove wire:target="create">Shorten</span>
                    <span wire:loading wire:target="create">Shortening…</span>
                </x-ui.button>
            </div>
            <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-500" id="public_destination_hint">
                Include <code>https://</code>. Guests get auto-made codes —
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="underline underline-offset-2">log in</a> for custom slugs, shorter links &amp; click stats.
            </p>
            @error('destination_url')
                <p class="mt-1.5 text-xs font-medium" role="alert">{{ $message }}</p>
            @enderror
        </form>
    @endif
</x-ui.card>

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
