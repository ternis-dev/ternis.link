<div class="ml-panel" data-sk-form data-t-copied="{{ $this->t('copy.done') }}" data-t-copyfail="{{ $this->t('result.copyfail') }}">
    <div class="ml-panel-head">
        <h2 class="ml-panel-title">{{ $this->t('form.title') }}</h2>
        <span class="ml-status is-{{ $urlState === 'idle' ? 'idle' : $urlState }}" aria-live="polite">
            <span class="dot" aria-hidden="true"></span>@if ($urlState === 'valid'){{ $this->t('state.valid') }}@elseif ($urlState === 'invalid'){{ $this->t('state.invalid') }}@else {{ $this->t('state.ready') }}@endif</span>
    </div>

    @if ($shortUrl)
        <div class="ml-departure" role="status" aria-live="polite">
            <p class="ml-departure-kicker"><span class="dot" aria-hidden="true"></span>{{ $this->t('result.kicker') }}</p>
            <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="ml-departure-link" data-sk-result-link>{{ $shortUrl }}</a>
            @if ($originalUrl)
                <p class="ml-departure-from" title="{{ $originalUrl }}" data-sk-result-original>
                    {{ $this->t('result.from') }} {{ \Illuminate\Support\Str::limit($originalUrl, 72) }}
                </p>
            @endif
            <div class="ml-departure-actions">
                <button
                    type="button"
                    class="ml-ghost-btn"
                    data-copy-value="{{ $shortUrl }}"
                    aria-label="{{ $this->t('result.copy.aria') }}"
                >
                    {{ $this->t('result.copy') }}
                </button>
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="ml-ghost-btn">
                    {{ $this->t('result.open') }}
                </a>
                <a href="{{ url('/v1/qr?url='.urlencode($shortUrl).'&format=png') }}" target="_blank" rel="noopener" class="ml-ghost-btn">{{ $this->t('result.qr') }}</a>
                <button type="button" wire:click="resetForm" class="ml-ghost-btn">
                    {{ $this->t('result.again') }}
                </button>
            </div>
            <p class="ml-copied-note" data-copy-feedback hidden>{{ $this->t('result.copied') }}</p>
        </div>
    @else
        <form wire:submit="create" novalidate>
            <label class="ml-field-label" for="public_destination_url">{{ $this->t('label.destination') }}</label>
            <div class="ml-input-row">
                <div class="ml-input-wrap">
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
                </div>
                <button
                    type="submit"
                    class="ml-btn"
                    wire:loading.attr="disabled"
                    wire:target="create"
                >
                    <span wire:loading.remove wire:target="create">{{ $this->t('submit') }} ▸</span>
                    <span wire:loading wire:target="create">{{ $this->t('submitting') }}</span>
                </button>
            </div>
            <div class="ml-meta">
                <span class="ml-hint" id="public_destination_hint">
                    @if ($minimal)
                        {!! $this->t('hint.minimal') !!}
                    @else
                        {!! $this->t('hint.full', \App\Support\DomainUrls::dashboard('/login')) !!}
                    @endif
                </span>
                @if (! $minimal)
                    <span class="ml-quota" aria-hidden="true"><strong>{{ $this->quotaLeft }}</strong>/{{ \App\Services\LinkService::ANONYMOUS_DAILY_LIMIT }}</span>
                @endif
            </div>
            <div class="ml-meta">
                <span></span>
                <span>
                    <button type="button" class="ml-ghost-btn" data-sk-paste>{{ $this->t('tool.paste') }}</button>
                    <button type="button" class="ml-ghost-btn" data-sk-clear>{{ $this->t('tool.clear') }}</button>
                </span>
            </div>
            @if ($this->fixablePreview)
                <div class="ml-notice" aria-live="polite">
                    <div class="row">
                        <p>{{ $this->t('fix.text') }} <strong>{{ \Illuminate\Support\Str::limit($this->fixablePreview, 56) }}</strong>?</p>
                        <button type="button" wire:click="applyFix" class="ml-ghost-btn">
                            {{ $this->t('fix.btn') }}
                        </button>
                    </div>
                </div>
            @endif
            @if ($this->duplicate)
                <div class="ml-notice is-ok" aria-live="polite">
                    <p class="ml-notice-title">{{ $this->t('dup.title') }}</p>
                    <p>
                        <a href="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" target="_blank" rel="noopener">{{ $this->previewHost }}/{{ $this->duplicate->slug }}</a>
                        <button type="button" class="ml-ghost-btn" data-copy-value="https://{{ $this->previewHost }}/{{ $this->duplicate->slug }}" aria-label="{{ $this->t('dup.copy.aria') }}">
                            {{ $this->t('dup.copy') }}
                        </button>
                    </p>
                </div>
            @endif
            @if (! $minimal && $this->normalizedPreview)
                <div class="ml-notice" aria-live="polite">
                    <p>{{ $this->t('preview.will') }} <strong title="{{ $this->normalizedPreview }}">{{ \Illuminate\Support\Str::limit($this->normalizedPreview, 64) }}</strong> → {{ $this->t('preview.get') }} <strong>{{ $this->previewHost }}/••••••••</strong></p>
                </div>
            @endif
            @error('destination_url')
                <div class="ml-notice is-error" role="alert" id="public_destination_error">
                    <p class="ml-notice-title">
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
                    <p class="ml-notice-msg">{{ $message }}</p>
                    @if ($quotaExceeded)
                        <p>{!! $this->t('oops.nudge', \App\Support\DomainUrls::dashboard('/login')) !!}</p>
                    @endif
                </div>
            @enderror

            @include('livewire.public.partials.turnstile')
        </form>
    @endif

    @if (! $minimal)
    <div class="sk-tray ml-tray" data-sk-tray data-t-copy="{{ $this->t('tray.copy') }}" hidden>
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
