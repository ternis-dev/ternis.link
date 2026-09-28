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
