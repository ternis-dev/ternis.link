<x-layouts.dashboard title="Submit Spam Appeal — ternis.link">
    <x-ui.page-header
        title="Spam & Threat Appeal"
        subtitle="Request manual human review if your link was falsely flagged as spam, phishing, or malware by AI."
        :backHref="route($theme === 'public' ? 'public-dashboard.links' : 'dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route($theme === 'public' ? 'public-dashboard.appeals.store' : 'dashboard.appeals.store') }}">
            @csrf

            <x-ui.card class="space-y-6">
                @if ($selectedLink)
                    <input type="hidden" name="link_id" value="{{ $selectedLink->id }}">
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/60">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm font-bold text-neutral-900 dark:text-neutral-100">
                                {{ ($selectedLink->domain->hostname ?? 'href.nz').'/'.$selectedLink->slug }}
                            </span>
                            @if (in_array('quarantine', $selectedLink->tags ?? [], true))
                                <span class="inline-flex items-center gap-1 rounded bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    <span>Quarantined</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    <span>Flagged</span>
                                </span>
                            @endif
                        </div>
                        <p class="mt-2 truncate text-xs text-neutral-500 dark:text-neutral-400">
                            <strong>Destination:</strong> {{ $selectedLink->destination_url }}
                        </p>
                    </div>
                @elseif ($flaggedLinks->isNotEmpty())
                    <div>
                        <label for="link_id" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Select Flagged Link <span class="text-rose-500">*</span>
                        </label>
                        <select name="link_id" id="link_id" required class="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white">
                            <option value="">— Select a link to appeal —</option>
                            @foreach ($flaggedLinks as $link)
                                <option value="{{ $link->id }}" @selected(old('link_id') === $link->id)>
                                    {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }} ({{ \Illuminate\Support\Str::limit($link->destination_url, 45) }})
                                </option>
                            @endforeach
                        </select>
                        @error('link_id')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div>
                        <label for="link_id" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Link ID or UUID <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="link_id"
                            id="link_id"
                            value="{{ old('link_id') }}"
                            placeholder="e.g. 01h9y4f3..."
                            required
                            class="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                        >
                        @error('link_id')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="contact_email" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Contact Email <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="email"
                        name="contact_email"
                        id="contact_email"
                        value="{{ old('contact_email', $contactEmail) }}"
                        required
                        class="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                    >
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">We will notify you at this email address once an admin reviews your appeal.</p>
                    @error('contact_email')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Reason for Appeal <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        id="reason"
                        rows="5"
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="Please explain why this URL is benign and why the AI verdict was a false positive (e.g. nature of the site, ownership, purpose of link)..."
                        class="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white"
                    >{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Minimum 10 characters. Provide clear context to expedite review.</p>
                    @error('reason')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
                    <x-ui.button href="{{ route($theme === 'public' ? 'public-dashboard.links' : 'dashboard.links') }}" variant="secondary">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Submit Appeal for Review
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>
    </div>
</x-layouts.dashboard>
