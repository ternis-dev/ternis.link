<x-layouts.app title="Report Abuse & Policy Violations — ternis.link">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/pages/abuse') }}">
        <meta name="description" content="Report phishing, malware, spam, fraud, or abusive short links on the Ternis network.">
        <meta property="og:title" content="Report Abuse & Policy Violations — ternis.link">
        <meta property="og:description" content="Report phishing, malware, spam, fraud, or abusive short links on the Ternis network.">
        <meta property="og:url" content="{{ url('/pages/abuse') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ternis.link">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Report Abuse — ternis.link">
        <meta name="twitter:description" content="Report malicious or deceptive short links operating on the Ternis link network.">
        <link rel="alternate" type="text/markdown" title="Abuse Reporting Guidelines (Markdown)" href="{{ url('/pages/abuse.md') }}">
    </x-slot:head>

    <div class="mx-auto max-w-2xl py-12 px-4 sm:px-6">
        {{-- Legal Navigation Bar --}}
        <nav aria-label="Legal & Safety" class="mb-8 flex flex-wrap gap-2 text-sm">
            <a href="{{ url('/pages/legal/privacy') }}" class="rounded-full border border-neutral-300 px-3 py-1 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Privacy Policy</a>
            <a href="{{ url('/pages/legal/terms') }}" class="rounded-full border border-neutral-300 px-3 py-1 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Terms of Service</a>
            <a href="{{ url('/pages/legal/cookies') }}" class="rounded-full border border-neutral-300 px-3 py-1 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Cookie Policy</a>
            <span aria-current="page" class="rounded-full bg-rose-600 px-3 py-1 font-medium text-white dark:bg-rose-500">Report Abuse</span>
            <a href="https://ternis.dev/en/legal/imprint" target="_blank" rel="noopener" class="rounded-full border border-neutral-300 px-3 py-1 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">Imprint</a>
        </nav>

        {{-- Page Header --}}
        <div class="mb-8">
            <div class="inline-flex items-center gap-1.5 rounded-full border border-rose-500/20 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-700 dark:text-rose-400">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Trust & Safety</span>
            </div>
            <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-neutral-900 dark:text-white sm:text-4xl">
                Report Abuse
            </h1>
            <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                We take malicious links, phishing attempts, malware distribution, and terms violations seriously. Fill out the report form below or email <a href="mailto:abuse@ternis.dev" class="font-medium text-rose-600 underline dark:text-rose-400">abuse@ternis.dev</a>.
            </p>
        </div>

        {{-- Submission Success Notification --}}
        @if (session('success') || $submitted)
            <div class="mb-8 rounded-xl border border-emerald-500/30 bg-emerald-50 p-5 dark:border-emerald-500/20 dark:bg-emerald-950/30">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <h2 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Abuse report submitted</h2>
                        <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">
                            Thank you for helping keep our network safe. Our Trust & Safety team has received your report and will investigate promptly.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Card --}}
        <x-ui.card>
            <form method="POST" action="{{ route('pages.abuse.store') }}" class="space-y-6">
                @csrf

                {{-- Honeypot field (hidden from legitimate humans) --}}
                <div class="hidden" aria-hidden="true">
                    <label for="company_website">Website</label>
                    <input type="text" name="company_website" id="company_website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="reported_url" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Suspect URL or Short Link <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="reported_url"
                        id="reported_url"
                        value="{{ old('reported_url', $url) }}"
                        required
                        placeholder="e.g. https://href.nz/sample or target link"
                        class="mt-1.5 block w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 shadow-xs placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                    >
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Provide the full short link (e.g. href.nz/xyz) or the destination address causing harm.
                    </p>
                    @error('reported_url')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Violation Category <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="category"
                        id="category"
                        required
                        class="mt-1.5 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-xs focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                    >
                        <option value="">— Select violation category —</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="reporter_email" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Your Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="email"
                            name="reporter_email"
                            id="reporter_email"
                            value="{{ old('reporter_email') }}"
                            required
                            placeholder="you@example.com"
                            class="mt-1.5 block w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 shadow-xs placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                        >
                        @error('reporter_email')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reporter_name" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Your Name / Organization <span class="text-neutral-400 font-normal">(optional)</span>
                        </label>
                        <input
                            type="text"
                            name="reporter_name"
                            id="reporter_name"
                            value="{{ old('reporter_name') }}"
                            placeholder="e.g. Brand Security Team"
                            class="mt-1.5 block w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 shadow-xs placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                        >
                        @error('reporter_name')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Details & Evidence <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        id="reason"
                        rows="5"
                        required
                        minlength="10"
                        placeholder="Please describe why this link is abusive, evidence of malicious payloads, impersonated brand details, or logs..."
                        class="mt-1.5 block w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 shadow-xs placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                    >{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Include any pertinent details such as screenshots, impersonated brand names, malicious headers, or observed attacks.
                    </p>
                    @error('reason')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-2">
                    <p class="text-xs text-neutral-400 dark:text-neutral-500">
                        Processed under legitimate interest to protect users.
                    </p>
                    <x-ui.button type="submit" variant="danger" size="lg" class="shadow-sm">
                        Submit Abuse Report
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Contact Box --}}
        <div class="mt-8 rounded-xl border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-900/50">
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Additional Contact Options</h2>
            <ul class="mt-2 space-y-1.5 text-xs text-neutral-600 dark:text-neutral-400">
                <li>&bull; Direct email for automated takedown feeds: <a href="mailto:abuse@ternis.dev" class="font-medium text-neutral-900 underline dark:text-neutral-200">abuse@ternis.dev</a></li>
                <li>&bull; Law enforcement and formal legal inquiries: <a href="mailto:legal@ternis.dev" class="font-medium text-neutral-900 underline dark:text-neutral-200">legal@ternis.dev</a></li>
                <li>&bull; API consumers can consume the twin documentation at <a href="{{ url('/pages/abuse.md') }}" class="font-mono text-neutral-900 underline dark:text-neutral-200">/pages/abuse.md</a></li>
            </ul>
        </div>
    </div>
</x-layouts.app>
