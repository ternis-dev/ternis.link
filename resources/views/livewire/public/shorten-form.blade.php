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
                <svg class="sk-tada" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="m8.2 12.3 2.5 2.5 5.1-5.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
            <div class="sk-domain-row">
                <span class="sk-label">{{ $locale === 'de' ? 'Domain' : 'Short link domain' }}</span>
                <div class="sk-domain-switch">
                    <button
                        type="button"
                        wire:click="$set('selectedDomain', 'href.nz')"
                        class="sk-domain-btn{{ $selectedDomain === 'href.nz' ? ' is-active' : '' }}"
                    >
                        href.nz
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedDomain', 'href.yt')"
                        class="sk-domain-btn{{ $selectedDomain === 'href.yt' ? ' is-active' : '' }}"
                    >
                        href.yt
                    </button>
                </div>
            </div>
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

            @include('livewire.public.partials.turnstile')
        </form>
    @endif

    @if (! $minimal)
    <div class="{{ $theme === 'yt' ? 'yt-tray' : 'sk-tray' }}" data-sk-tray data-t-copy="{{ $this->t('tray.copy') }}" hidden>
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

@include('livewire.public.partials.form-scripts')
