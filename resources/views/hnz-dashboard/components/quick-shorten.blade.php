<div
    x-data="{
        url: '',
        domain: 'href.nz',
        isValid: false,
        domains: ['href.nz', 'meinlink.at', 'href.yt'],
        checkUrl() {
            try {
                const target = this.url.trim();
                if (!target) {
                    this.isValid = false;
                    return;
                }
                const parsed = new URL(target.startsWith('http://') || target.startsWith('https://') ? target : 'https://' + target);
                this.isValid = Boolean(parsed.hostname && parsed.hostname.includes('.'));
            } catch {
                this.isValid = false;
            }
        },
        clearInput() {
            this.url = '';
            this.isValid = false;
        },
        openCreator() {
            $dispatch('open-link-creator', { url: this.url, domain: this.domain });
        }
    }"
    class="relative mb-8 overflow-hidden rounded-3xl border border-violet-500/20 bg-gradient-to-r from-violet-600/10 via-indigo-600/5 to-blue-600/10 p-6 shadow-xl shadow-violet-500/5 backdrop-blur-2xl dark:border-white/10 dark:bg-neutral-900/90"
>
    {{-- Ambient decorative background aura --}}
    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-violet-600/15 blur-3xl dark:bg-violet-500/20" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-16 -bottom-16 h-48 w-48 rounded-full bg-blue-600/10 blur-3xl dark:bg-blue-500/15" aria-hidden="true"></div>

    <div class="relative flex flex-col gap-5">
        {{-- Header row --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-md shadow-violet-600/30">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </span>
                <span class="font-display text-base font-bold tracking-tight text-neutral-900 dark:text-white">Quick Shorten</span>
                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold text-violet-700 dark:bg-violet-400/20 dark:text-violet-300">Fast Mode</span>
            </div>

            {{-- Domain selector pills --}}
            <div class="flex items-center gap-1.5 rounded-2xl border border-neutral-200/80 bg-white/80 p-1 shadow-xs dark:border-white/10 dark:bg-neutral-800/80">
                <template x-for="d in domains" :key="d">
                    <button
                        type="button"
                        @click="domain = d"
                        class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition-all duration-150"
                        :class="domain === d ? 'bg-gradient-to-r from-violet-600 to-blue-600 text-white shadow-sm shadow-violet-600/25 ring-1 ring-white/20' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'"
                        x-text="d"
                    ></button>
                </template>
            </div>
        </div>

        {{-- Input row form --}}
        <form
            @submit.prevent="openCreator()"
            class="flex flex-col gap-3 sm:flex-row sm:items-center"
        >
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-neutral-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/></svg>
                </div>

                <input
                    type="url"
                    x-model="url"
                    @input="checkUrl()"
                    placeholder="Paste a long destination URL here (e.g. https://github.com/ternis-dev)..."
                    class="w-full rounded-2xl border border-neutral-200 bg-white/95 py-3 pr-10 pl-11 text-sm font-medium text-neutral-900 placeholder:text-neutral-400 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/15 focus:outline-none dark:border-white/10 dark:bg-neutral-800/90 dark:text-white dark:placeholder:text-neutral-500 shadow-sm transition-all"
                    :class="isValid ? 'border-violet-500/60 dark:border-violet-400/60 ring-2 ring-violet-500/20' : ''"
                >

                <button
                    type="button"
                    x-show="url.length > 0"
                    x-cloak
                    @click="clearInput()"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
                    aria-label="Clear URL input"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md shadow-violet-600/30 transition-all hover:from-violet-500 hover:to-blue-500 hover:shadow-lg hover:shadow-violet-600/40 active:scale-95"
                >
                    <span>Shorten link</span>
                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

                <a
                    href="{{ \App\Support\PublicDashboard::route('links.create') }}"
                    class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-neutral-200 bg-white/80 p-3 text-neutral-600 shadow-sm transition hover:border-violet-300 hover:bg-neutral-50 hover:text-violet-700 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:border-violet-400"
                    title="Open full options page (custom alias, password, tags, expiry)"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                </a>
            </div>
        </form>
    </div>
</div>
