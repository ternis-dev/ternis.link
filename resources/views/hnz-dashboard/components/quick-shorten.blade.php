<div
    x-data="{
        url: '',
        domain: 'href.nz',
        isValid: false,
        checkUrl() {
            try {
                const u = new URL(this.url.startsWith('http') ? this.url : 'https://' + this.url);
                this.isValid = Boolean(u.hostname && u.hostname.includes('.'));
            } catch {
                this.isValid = false;
            }
        },
        openCreator() {
            $dispatch('open-link-creator');
        }
    }"
    class="relative overflow-hidden rounded-3xl border border-violet-500/20 bg-gradient-to-r from-violet-600/10 via-indigo-600/5 to-blue-600/10 p-5 shadow-lg shadow-violet-500/5 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/90 mb-7"
>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-white text-xs font-bold">⚡</span>
                <span class="font-display text-sm font-bold tracking-tight text-neutral-900 dark:text-white">Quick Shorten</span>
                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold text-violet-700 dark:bg-violet-400/20 dark:text-violet-300">Fast Mode</span>
            </div>
            {{-- Domain switcher pills --}}
            <div class="flex items-center gap-1.5 rounded-xl border border-neutral-200/80 bg-white/80 p-1 dark:border-white/10 dark:bg-neutral-800/80">
                <template x-for="d in ['href.nz', 'meinlink.at', 'href.yt']" :key="d">
                    <button
                        type="button"
                        @click="domain = d"
                        class="rounded-lg px-2.5 py-1 text-xs font-bold transition-all"
                        :class="domain === d ? 'bg-violet-600 text-white shadow-sm' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'"
                        x-text="d"
                    ></button>
                </template>
            </div>
        </div>

        <form
            @submit.prevent="openCreator()"
            class="flex flex-col gap-3 sm:flex-row sm:items-center"
        >
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-neutral-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/></svg>
                </div>
                <input
                    type="url"
                    x-model="url"
                    @input="checkUrl()"
                    placeholder="Paste a long destination URL here (e.g. https://github.com/ternis-dev)..."
                    class="w-full rounded-2xl border border-neutral-200 bg-white/95 py-2.5 pr-4 pl-10 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none dark:border-white/10 dark:bg-neutral-800/90 dark:text-white dark:placeholder:text-neutral-500 shadow-sm"
                >
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-violet-600/30 transition hover:from-violet-500 hover:to-blue-500 hover:shadow-lg hover:shadow-violet-600/40 active:scale-95"
                >
                    <span>Shorten link</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
                <a
                    href="{{ \App\Support\PublicDashboard::route('links.create') }}"
                    class="cursor-pointer rounded-2xl border border-neutral-200 bg-white/80 p-2.5 text-neutral-600 transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:border-violet-400"
                    title="Open full options page (alias, expires, tags)"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                </a>
            </div>
        </form>
    </div>
</div>
