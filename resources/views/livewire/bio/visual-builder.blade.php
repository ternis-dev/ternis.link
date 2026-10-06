<div>
    {{-- Studio Top Bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-neutral-200/80 bg-white p-3.5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/90 backdrop-blur-sm">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('dashboard.bio') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-800/60 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:hover:text-white">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Bio Pages
            </a>
            <div class="h-4 w-px bg-neutral-200 dark:bg-neutral-800"></div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-md bg-neutral-100 px-2 py-0.5 font-mono text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ $family->domain?->hostname }}
                </span>
                @if ($editing)
                    <span class="text-xs font-medium text-neutral-400 dark:text-neutral-500">/</span>
                    <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ $editing->title }}</span>
                @endif
            </div>
            @if ($editing && $family->domain)
                <a
                    href="https://{{ $family->domain->hostname }}{{ $editing->parent_id ? '/' . $editing->slug : '' }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 text-xs font-medium text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
                >
                    Visit &rarr;
                </a>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Saving status indicator --}}
            <div class="mr-1 hidden sm:flex items-center">
                <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Saving changes…
                </span>
                <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-1.5 text-xs text-neutral-400 dark:text-neutral-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Ready
                </span>
            </div>

            <x-ui.button wire:click="makeDraftLink" variant="secondary" size="sm" class="gap-1.5">
                <svg class="h-3.5 w-3.5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1-1"/></svg>
                Draft link
            </x-ui.button>

            @if ($editing)
                <x-ui.button href="{{ route('dashboard.bio.show', $editing->id) }}" variant="secondary" size="sm" class="gap-1.5">
                    <svg class="h-3.5 w-3.5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
                    Stats
                </x-ui.button>
            @endif

            <x-ui.button wire:click="save" variant="primary" size="sm" class="gap-1.5" wire:loading.attr="disabled">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span wire:loading.remove wire:target="save">Save page</span>
                <span wire:loading wire:target="save">Saving…</span>
            </x-ui.button>
        </div>
    </div>

    {{-- Draft URL banner --}}
    @if ($draftUrl)
        <div class="mb-6 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950 sm:flex-row sm:items-center sm:justify-between dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1-1"/></svg>
                    Temporary signed preview draft
                </div>
                <p class="mt-1 font-mono text-xs select-all truncate text-amber-900 dark:text-amber-200">{{ $draftUrl }}</p>
                <p class="text-[11px] text-amber-700 dark:text-amber-400">Valid until {{ $draftExpires }} &bull; Visitors are not tracked or indexed</p>
            </div>
            <div class="shrink-0" x-data="{ copied: false }">
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $draftUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 shadow-sm transition hover:bg-amber-50 dark:border-amber-700 dark:bg-neutral-900 dark:text-amber-200 dark:hover:bg-neutral-800"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <span x-text="copied ? 'Copied!' : 'Copy draft link'">Copy draft link</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Sub-pages navigation strip --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-3 dark:border-neutral-800">
        <div class="flex items-center gap-2 overflow-x-auto" role="tablist" aria-label="Pages">
            <button
                type="button"
                wire:click="edit('{{ $family->id }}')"
                @class([
                    'inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition cursor-pointer',
                    $editing?->id === $family->id
                        ? 'bg-neutral-900 text-white shadow-sm dark:bg-white dark:text-neutral-900'
                        : 'border border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:border-neutral-700 dark:hover:text-white'
                ])
                role="tab"
                @if($editing?->id === $family->id) aria-selected="true" @endif
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>{{ $family->title }}</span>
                <span class="rounded bg-neutral-200/60 px-1 py-0.2 text-[10px] font-normal uppercase tracking-wider dark:bg-neutral-800">Root</span>
            </button>

            @foreach ($family->children()->where('is_removed', false)->orderBy('sort_order')->get() as $sub)
                <button
                    type="button"
                    wire:click="edit('{{ $sub->id }}')"
                    @class([
                        'inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition cursor-pointer',
                        $editing?->id === $sub->id
                            ? 'bg-neutral-900 text-white shadow-sm dark:bg-white dark:text-neutral-900'
                            : 'border border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:border-neutral-700 dark:hover:text-white'
                    ])
                    role="tab"
                    @if($editing?->id === $sub->id) aria-selected="true" @endif
                >
                    <span class="font-mono text-neutral-400 dark:text-neutral-500">/</span>
                    <span>{{ $sub->slug }}</span>
                    @if ($sub->title && $sub->title !== $sub->slug)
                        <span class="text-[11px] font-normal opacity-75">({{ $sub->title }})</span>
                    @endif
                </button>
            @endforeach

            <button
                type="button"
                wire:click="$toggle('showNewSubModal')"
                class="inline-flex shrink-0 items-center gap-1 rounded-full border border-dashed border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-600 transition hover:border-neutral-400 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-neutral-600 dark:hover:text-white cursor-pointer"
            >
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New sub-page
            </button>
        </div>
    </div>

    {{-- Inline New Sub-page drawer --}}
    @if ($showNewSubModal)
        <div class="mb-6 rounded-2xl border border-neutral-200 bg-neutral-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/60">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">Create a sub-page</h3>
                <button type="button" wire:click="$set('showNewSubModal', false)" class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white">&times; Close</button>
            </div>
            <form wire:submit="createSub" class="grid gap-3 sm:grid-cols-3">
                <x-ui.input label="Slug *" name="newSubSlug" type="text" wire:model="newSubSlug" placeholder="socials" maxlength="64" hint="Lowercase letters, numbers, and dashes only." required />
                <x-ui.input label="Title *" name="newSubTitle" type="text" wire:model="newSubTitle" placeholder="My Social Accounts" maxlength="80" required />
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="primary">Add Sub-page</x-ui.button>
                    <x-ui.button type="button" wire:click="$set('showNewSubModal', false)" variant="ghost">Cancel</x-ui.button>
                </div>
            </form>
        </div>
    @endif

    @if ($editing)
    <div class="grid items-start gap-8 xl:grid-cols-[1fr_360px]">
        {{-- Left Studio: Tabbed Workbench --}}
        <div class="min-w-0 space-y-6">
            {{-- Studio Navigation Tabs --}}
            <div class="flex rounded-xl bg-neutral-100 p-1 dark:bg-neutral-800/80">
                <button
                    type="button"
                    wire:click="$set('activeTab', 'content')"
                    @class([
                        'flex-1 flex items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold transition cursor-pointer',
                        $activeTab === 'content'
                            ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-900 dark:text-white'
                            : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                    ])
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    <span>Blocks &amp; Links</span>
                    <span class="rounded-full bg-neutral-200/70 px-1.5 py-0.2 text-[10px] dark:bg-neutral-700">{{ $editing->buttons->count() }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('activeTab', 'design')"
                    @class([
                        'flex-1 flex items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold transition cursor-pointer',
                        $activeTab === 'design'
                            ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-900 dark:text-white'
                            : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                    ])
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                    <span>Theme &amp; Style</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('activeTab', 'profile')"
                    @class([
                        'flex-1 flex items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold transition cursor-pointer',
                        $activeTab === 'profile'
                            ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-900 dark:text-white'
                            : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                    ])
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Header &amp; Profile</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('activeTab', 'settings')"
                    @class([
                        'flex-1 flex items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold transition cursor-pointer',
                        $activeTab === 'settings'
                            ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-900 dark:text-white'
                            : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                    ])
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Settings</span>
                </button>
            </div>

            {{-- TAB 1: BLOCKS & CONTENT --}}
            @if ($activeTab === 'content')
                <div class="space-y-6">
                    {{-- Add Block Card --}}
                    <x-ui.card title="Add a Block">
                        <form wire:submit="addButton" class="space-y-4">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-ui.select label="Block kind" name="newKind" wire:model.live="newKind">
                                    <option value="link">🔗 Link</option>
                                    <option value="social">📱 Social Icon</option>
                                    <option value="header">🏷️ Section Header</option>
                                    <option value="divider">➖ Divider</option>
                                    <option value="video">▶️ Video Embed</option>
                                    <option value="image">🖼️ Image Card</option>
                                    <option value="contact">🪪 Contact Card</option>
                                    <option value="countdown">⏳ Countdown</option>
                                    <option value="quote">💬 Quote</option>
                                    <option value="coupon">🎟️ Coupon Code</option>
                                    <option value="rsvp">✋ RSVP Headcount</option>
                                </x-ui.select>

                                @if (!in_array($newKind, ['divider', 'header', 'countdown', 'quote', 'rsvp'], true))
                                    <x-ui.select label="Action" name="newAction" wire:model.live="newAction">
                                        <option value="url">Open URL</option>
                                        <option value="subpage">Go to Sub-page</option>
                                        <option value="modal">Open Pop-up</option>
                                    </x-ui.select>
                                @endif

                                @if ($newKind !== 'divider')
                                    <x-ui.input label="Label / Heading" name="newLabel" type="text" wire:model="newLabel" placeholder="e.g. Visit our website" maxlength="60" required />
                                @endif
                            </div>

                            {{-- Contextual inputs based on Kind and Action --}}
                            <div class="grid gap-3 sm:grid-cols-2">
                                @if (in_array($newKind, ['link', 'social', 'video'], true) && $newAction === 'url')
                                    <x-ui.input label="Destination URL *" name="newUrl" type="url" wire:model="newUrl" placeholder="https://example.com" maxlength="2048" />
                                @endif

                                @if ($newKind !== 'divider')
                                    <x-ui.input label="Sublabel / Note (optional)" name="newSublabel" type="text" wire:model="newSublabel" placeholder="e.g. Free shipping on orders over $50" maxlength="120" />
                                @endif

                                @if (in_array($newKind, ['link', 'social', 'contact'], true))
                                    <x-ui.select label="Icon" name="newIcon" wire:model="newIcon">
                                        <option value="">None</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="x">X (Twitter)</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="github">GitHub</option>
                                        <option value="globe">Website (Globe)</option>
                                        <option value="mail">Email</option>
                                        <option value="link">Link icon</option>
                                    </x-ui.select>
                                @endif

                                @if (in_array($newKind, ['link', 'image', 'video'], true))
                                    <x-ui.input label="Thumbnail image URL" name="newThumbnail" type="url" wire:model="newThumbnail" placeholder="https://example.com/thumb.jpg" maxlength="2048" />
                                @endif

                                @if ($newKind === 'contact')
                                    <x-ui.input label="Contact Email" name="newContactEmail" type="email" wire:model="newContactEmail" placeholder="contact@example.com" maxlength="255" />
                                    <x-ui.input label="Contact Phone" name="newContactPhone" type="tel" wire:model="newContactPhone" placeholder="+1 555 0199" maxlength="40" />
                                @endif

                                @if ($newKind === 'countdown')
                                    <x-ui.input label="Countdown Target Date &amp; Time" name="newEventAt" type="datetime-local" wire:model="newEventAt" hint="Visitors will see a live timer counting down to this moment." />
                                @endif

                                @if ($newAction === 'subpage')
                                    <x-ui.select label="Target Sub-page *" name="newTargetPage" wire:model="newTargetPage" required>
                                        <option value="">Choose a sub-page…</option>
                                        @foreach ($actionTargets as $target)
                                            <option value="{{ $target->id }}">{{ $target->parent_id ? '/' . $target->slug : '(root)' }} — {{ $target->title }}</option>
                                        @endforeach
                                    </x-ui.select>
                                @endif

                                @if ($newAction === 'modal')
                                    <x-ui.input label="Pop-up Title *" name="newModalTitle" type="text" wire:model="newModalTitle" placeholder="Special Announcement" maxlength="80" required />
                                    <div class="sm:col-span-2">
                                        <x-ui.input label="Pop-up Body" name="newModalBody" type="text" wire:model="newModalBody" placeholder="Full details to display in the modal popup" maxlength="1000" />
                                    </div>
                                @endif

                                @if (in_array($newKind, ['link', 'social'], true))
                                    <x-ui.input label="Highlight badge (optional)" name="newBadge" type="text" wire:model="newBadge" placeholder="NEW or 50% OFF" maxlength="12" />
                                    <div class="flex items-center pt-6">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                            <input type="checkbox" wire:model="newOpenNew" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                                            <span>Open link in new browser tab</span>
                                        </label>
                                    </div>
                                @endif

                                @if (in_array($newKind, ['link', 'social', 'coupon'], true))
                                    <x-ui.input label="Schedule: Show from" name="newStartsAt" type="datetime-local" wire:model="newStartsAt" hint="Leave blank to show immediately." />
                                    <x-ui.input label="Schedule: Show until" name="newEndsAt" type="datetime-local" wire:model="newEndsAt" hint="Leave blank to show indefinitely." />
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200/80 pt-4 dark:border-neutral-800">
                                <div class="flex items-center gap-2">
                                    <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="addButton">+ Add Block</span>
                                        <span wire:loading wire:target="addButton">Adding…</span>
                                    </x-ui.button>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <x-ui.button type="button" wire:click="checkLinks" variant="ghost" size="sm" wire:loading.attr="disabled" class="gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <span wire:loading.remove wire:target="checkLinks">Check all links</span>
                                        <span wire:loading wire:target="checkLinks">Checking URLs…</span>
                                    </x-ui.button>
                                </div>
                            </div>
                        </form>
                    </x-ui.card>

                    {{-- Link Health Status Banner --}}
                    @if ($linkHealth !== [])
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Link Health Verification</h4>
                            <ul class="mt-2 space-y-1.5 text-sm">
                                @foreach ($linkHealth as $result)
                                    <li class="flex items-center justify-between gap-2 rounded-lg bg-neutral-50 px-3 py-1.5 text-xs dark:bg-neutral-800/60">
                                        <span class="flex items-center gap-2 truncate">
                                            <span aria-hidden="true">{{ $result['ok'] ? '✅' : '❌' }}</span>
                                            <span class="font-medium text-neutral-800 dark:text-neutral-200 truncate">{{ $result['label'] }}</span>
                                        </span>
                                        <span class="shrink-0 font-mono text-[11px] text-neutral-500">{{ $result['status'] ?? 'unreachable' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Reorderable Block Cards List --}}
                    <x-ui.card title="Page Blocks ({{ $editing->buttons->count() }})">
                        @if ($editing->buttons->isEmpty())
                            <div class="py-8 text-center text-sm text-neutral-500">
                                <p>No blocks added to this page yet.</p>
                                <p class="mt-1 text-xs">Use the composer above to add links, social icons, headers, or media.</p>
                            </div>
                        @else
                            <ul class="space-y-2.5" x-data="{ dragging: null }" x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'">
                                @foreach ($editing->buttons()->orderBy('sort_order')->get() as $b)
                                    @if ($editingButtonId === $b->id)
                                        {{-- Inline Editor Card --}}
                                        <li class="rounded-xl border-2 border-neutral-900 bg-neutral-50/50 p-4 shadow-md dark:border-white dark:bg-neutral-900">
                                            <div class="mb-3 flex items-center justify-between">
                                                <h4 class="text-xs font-semibold text-neutral-900 dark:text-white uppercase tracking-wider">Editing: {{ $b->label }}</h4>
                                                <button type="button" wire:click="cancelEditButton" class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white">&times; Cancel</button>
                                            </div>
                                            <form wire:submit="updateButton" class="grid gap-3 sm:grid-cols-2">
                                                <x-ui.input label="Label *" name="editLabel" type="text" wire:model="editLabel" maxlength="60" required />
                                                <x-ui.input label="Sublabel" name="editSublabel" type="text" wire:model="editSublabel" maxlength="120" />
                                                <x-ui.input label="URL" name="editUrl" type="url" wire:model="editUrl" maxlength="2048" />
                                                <x-ui.input label="Thumbnail URL" name="editThumbnail" type="url" wire:model="editThumbnail" maxlength="2048" />
                                                <x-ui.select label="Icon" name="editIcon" wire:model="editIcon">
                                                    <option value="">None</option>
                                                    <option value="instagram">Instagram</option>
                                                    <option value="tiktok">TikTok</option>
                                                    <option value="x">X (Twitter)</option>
                                                    <option value="youtube">YouTube</option>
                                                    <option value="github">GitHub</option>
                                                    <option value="globe">Website (Globe)</option>
                                                    <option value="mail">Email</option>
                                                    <option value="link">Link</option>
                                                </x-ui.select>
                                                <x-ui.input label="Badge" name="editBadge" type="text" wire:model="editBadge" placeholder="NEW" maxlength="12" />
                                                <x-ui.input label="Show from" name="editStartsAt" type="datetime-local" wire:model="editStartsAt" />
                                                <x-ui.input label="Show until" name="editEndsAt" type="datetime-local" wire:model="editEndsAt" />
                                                <div class="sm:col-span-2 flex items-center">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                                        <input type="checkbox" wire:model="editOpenNew" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                                                        <span>Open link in new browser tab</span>
                                                    </label>
                                                </div>
                                                <div class="sm:col-span-2 flex items-center gap-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
                                                    <x-ui.button type="submit" variant="primary" size="sm">Save block</x-ui.button>
                                                    <x-ui.button type="button" wire:click="cancelEditButton" variant="ghost" size="sm">Cancel</x-ui.button>
                                                </div>
                                            </form>
                                        </li>
                                    @else
                                        {{-- Normal Block Card with Drag Handle --}}
                                        <li
                                            draggable="true"
                                            data-bid="{{ $b->id }}"
                                            x-on:dragstart="dragging = '{{ $b->id }}'; $event.dataTransfer.effectAllowed = 'move'; $el.classList.add('opacity-40')"
                                            x-on:dragend="$el.classList.remove('opacity-40'); dragging = null"
                                            x-on:drop.prevent="
                                                const list = $el.closest('ul');
                                                const dragged = list.querySelector('[data-bid=\'' + dragging + '\']');
                                                const row = $el.closest('li');
                                                if (dragged && row && dragged !== row) {
                                                    const rect = row.getBoundingClientRect();
                                                    ($event.clientY - rect.top) > rect.height / 2 ? row.after(dragged) : row.before(dragged);
                                                    $wire.reorder([...list.querySelectorAll('[data-bid]')].map(el => el.dataset.bid));
                                                }
                                                dragging = null;
                                            "
                                            class="group flex cursor-grab items-center justify-between gap-3 rounded-xl border border-neutral-200/90 bg-white p-3 text-sm shadow-sm transition hover:border-neutral-300 hover:shadow active:cursor-grabbing dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700"
                                        >
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="text-neutral-400 group-hover:text-neutral-600 dark:text-neutral-600 dark:group-hover:text-neutral-400 select-none text-base" aria-hidden="true" title="Drag to reorder">⠿</span>
                                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 text-xs font-semibold uppercase">
                                                    {{ substr($b->kind, 0, 2) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="truncate font-semibold text-neutral-900 dark:text-white">{{ $b->label }}</span>
                                                        @if ($b->badge)
                                                            <span class="rounded bg-neutral-900 px-1.5 py-0.2 text-[10px] font-bold text-white dark:bg-white dark:text-neutral-900">{{ $b->badge }}</span>
                                                        @endif
                                                        @unless ($b->is_active)
                                                            <span class="rounded bg-amber-100 px-1.5 py-0.2 text-[10px] font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Paused</span>
                                                        @endunless
                                                    </div>
                                                    <p class="truncate text-xs text-neutral-500">
                                                        <span class="font-medium capitalize">{{ $b->kind }}</span>
                                                        @if ($b->destination_url) &bull; <span class="font-mono">{{ parse_url($b->destination_url, PHP_URL_HOST) ?: $b->destination_url }}</span>@endif
                                                        @if ($b->sublabel) &bull; {{ $b->sublabel }}@endif
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-2 text-xs">
                                                <span class="hidden sm:inline-flex rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400" title="Tap analytics">
                                                    {{ number_format($b->tap_count) }} taps
                                                </span>

                                                <button type="button" wire:click="startEditButton('{{ $b->id }}')" class="rounded p-1 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white cursor-pointer" title="Edit block" aria-label="Edit block">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                                </button>

                                                <button type="button" wire:click="duplicateButton('{{ $b->id }}')" class="rounded p-1 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white cursor-pointer" title="Duplicate block" aria-label="Duplicate block">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                </button>

                                                <button type="button" wire:click="move('{{ $b->id }}', 'up')" class="rounded p-1 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white cursor-pointer" title="Move up" aria-label="Move block up">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                                </button>

                                                <button type="button" wire:click="move('{{ $b->id }}', 'down')" class="rounded p-1 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white cursor-pointer" title="Move down" aria-label="Move block down">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                                </button>

                                                <button type="button" wire:click="toggleButton('{{ $b->id }}')" class="cursor-pointer text-xs font-medium text-neutral-600 hover:underline dark:text-neutral-300">
                                                    {{ $b->is_active ? 'Pause' : 'Resume' }}
                                                </button>

                                                <button type="button" wire:click="removeButton('{{ $b->id }}')" class="cursor-pointer text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                                    Remove
                                                </button>
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-4 flex flex-col gap-3 border-t border-neutral-100 pt-3 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between text-xs text-neutral-500">
                            <span>Drag cards to reorder &bull; Order saves automatically</span>
                        </div>
                    </x-ui.card>

                    {{-- Bulk Tools Accordions --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Import from Short Links --}}
                        <details class="group rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-neutral-800 select-none list-none [&::-webkit-details-marker]:hidden dark:text-neutral-200">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Import from My Short Links
                                </span>
                                <svg class="h-4 w-4 text-neutral-400 transition-transform duration-200 group-open:rotate-180 dark:text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mt-3 space-y-2 border-t border-neutral-100 pt-3 dark:border-neutral-800">
                                @php($importable = auth()->user()->links()->notRemoved()->with('domain:id,hostname')->orderByDesc('created_at')->limit(50)->get())
                                <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                    @forelse ($importable as $link)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 text-xs transition hover:bg-neutral-50 dark:hover:bg-neutral-800/60">
                                            <input type="checkbox" wire:model="importLinkIds" value="{{ $link->id }}" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                                            <span class="truncate font-medium text-neutral-800 dark:text-neutral-200">{{ $link->description ?: $link->slug }}</span>
                                            <span class="ml-auto shrink-0 font-mono text-[10px] text-neutral-400">{{ $link->domain?->hostname }}/{{ $link->slug }}</span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-neutral-500">No short links available in your account yet.</p>
                                    @endforelse
                                </div>
                                @if ($importable->isNotEmpty())
                                    <div class="pt-2">
                                        <x-ui.button type="button" wire:click="importLinks" variant="primary" size="sm">Import selected</x-ui.button>
                                    </div>
                                @endif
                            </div>
                        </details>

                        {{-- Quick Add (Paste) --}}
                        <details class="group rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-neutral-800 select-none list-none [&::-webkit-details-marker]:hidden dark:text-neutral-200">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
                                    Quick Add &bull; Paste Lines
                                </span>
                                <svg class="h-4 w-4 text-neutral-400 transition-transform duration-200 group-open:rotate-180 dark:text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mt-3 space-y-2 border-t border-neutral-100 pt-3 dark:border-neutral-800">
                                <p class="text-[11px] text-neutral-500">Paste one per line: <code>Title | https://url</code> or plain URLs</p>
                                <textarea wire:model="quickAdd" rows="3" placeholder="My Portfolio | https://example.com/work&#10;https://github.com/myname" class="w-full rounded-lg border border-neutral-300 bg-white p-2.5 font-mono text-xs text-neutral-900 focus:border-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"></textarea>
                                @error('quickAdd')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                <x-ui.button type="button" wire:click="quickAddButtons" variant="primary" size="sm">Create blocks</x-ui.button>
                            </div>
                        </details>
                    </div>
                </div>
            @endif

            {{-- TAB 2: THEME & DESIGN --}}
            @if ($activeTab === 'design')
                <div class="space-y-6">
                    <x-ui.card title="Theme Presets">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {{-- Minimal --}}
                            <button
                                type="button"
                                wire:click="$set('theme', 'minimal')"
                                @class([
                                    'group flex flex-col items-center justify-between rounded-xl border p-3 text-center transition cursor-pointer',
                                    $theme === 'minimal'
                                        ? 'border-neutral-900 bg-neutral-50 ring-2 ring-neutral-900/10 dark:border-white dark:bg-neutral-800'
                                        : 'border-neutral-200 bg-white hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900'
                                ])
                            >
                                <div class="mb-2 h-14 w-full rounded-lg border border-neutral-200 bg-white p-2 shadow-inner dark:border-neutral-700">
                                    <div class="h-2 w-8 mx-auto rounded-full bg-neutral-900"></div>
                                    <div class="mt-2 h-2.5 w-full rounded bg-neutral-900"></div>
                                    <div class="mt-1 h-2.5 w-full rounded bg-neutral-900"></div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-900 dark:text-white">Minimal</span>
                                <span class="text-[10px] text-neutral-500">Clean &amp; crisp</span>
                            </button>

                            {{-- Dark --}}
                            <button
                                type="button"
                                wire:click="$set('theme', 'dark')"
                                @class([
                                    'group flex flex-col items-center justify-between rounded-xl border p-3 text-center transition cursor-pointer',
                                    $theme === 'dark'
                                        ? 'border-neutral-900 bg-neutral-50 ring-2 ring-neutral-900/10 dark:border-white dark:bg-neutral-800'
                                        : 'border-neutral-200 bg-white hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900'
                                ])
                            >
                                <div class="mb-2 h-14 w-full rounded-lg border border-neutral-800 bg-neutral-950 p-2 shadow-inner">
                                    <div class="h-2 w-8 mx-auto rounded-full bg-white"></div>
                                    <div class="mt-2 h-2.5 w-full rounded bg-neutral-800"></div>
                                    <div class="mt-1 h-2.5 w-full rounded bg-neutral-800"></div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-900 dark:text-white">Dark</span>
                                <span class="text-[10px] text-neutral-500">Deep slate</span>
                            </button>

                            {{-- Paper --}}
                            <button
                                type="button"
                                wire:click="$set('theme', 'paper')"
                                @class([
                                    'group flex flex-col items-center justify-between rounded-xl border p-3 text-center transition cursor-pointer',
                                    $theme === 'paper'
                                        ? 'border-neutral-900 bg-neutral-50 ring-2 ring-neutral-900/10 dark:border-white dark:bg-neutral-800'
                                        : 'border-neutral-200 bg-white hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900'
                                ])
                            >
                                <div class="mb-2 h-14 w-full rounded-lg border border-amber-200/80 bg-[#f7f3ea] p-2 shadow-inner">
                                    <div class="h-2 w-8 mx-auto rounded-full bg-stone-800"></div>
                                    <div class="mt-2 h-2.5 w-full rounded bg-stone-800"></div>
                                    <div class="mt-1 h-2.5 w-full rounded bg-stone-800"></div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-900 dark:text-white">Paper</span>
                                <span class="text-[10px] text-neutral-500">Warm editorial</span>
                            </button>

                            {{-- Auto --}}
                            <button
                                type="button"
                                wire:click="$set('theme', 'auto')"
                                @class([
                                    'group flex flex-col items-center justify-between rounded-xl border p-3 text-center transition cursor-pointer',
                                    $theme === 'auto'
                                        ? 'border-neutral-900 bg-neutral-50 ring-2 ring-neutral-900/10 dark:border-white dark:bg-neutral-800'
                                        : 'border-neutral-200 bg-white hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900'
                                ])
                            >
                                <div class="mb-2 h-14 w-full rounded-lg border border-neutral-300 overflow-hidden shadow-inner flex">
                                    <div class="h-full w-1/2 bg-white p-1">
                                        <div class="h-2 w-4 rounded-full bg-neutral-900"></div>
                                        <div class="mt-1 h-2 w-full rounded bg-neutral-900"></div>
                                    </div>
                                    <div class="h-full w-1/2 bg-neutral-950 p-1">
                                        <div class="h-2 w-4 rounded-full bg-white"></div>
                                        <div class="mt-1 h-2 w-full rounded bg-neutral-800"></div>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-900 dark:text-white">Auto</span>
                                <span class="text-[10px] text-neutral-500">System mode</span>
                            </button>
                        </div>
                    </x-ui.card>

                    <div class="grid gap-6 sm:grid-cols-2">
                        {{-- Layout Style --}}
                        <x-ui.card title="Layout Arrangement">
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex flex-col items-center rounded-xl border p-3 text-center cursor-pointer transition @if($layout === 'list') border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800 @else border-neutral-200 dark:border-neutral-800 @endif">
                                    <input type="radio" wire:model.live="layout" value="list" class="sr-only">
                                    <div class="mb-2 space-y-1 w-full max-w-[80px]">
                                        <div class="h-2.5 w-full rounded bg-neutral-400"></div>
                                        <div class="h-2.5 w-full rounded bg-neutral-400"></div>
                                    </div>
                                    <span class="text-xs font-semibold">List</span>
                                    <span class="text-[10px] text-neutral-500">Stacked</span>
                                </label>

                                <label class="flex flex-col items-center rounded-xl border p-3 text-center cursor-pointer transition @if($layout === 'grid') border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800 @else border-neutral-200 dark:border-neutral-800 @endif">
                                    <input type="radio" wire:model.live="layout" value="grid" class="sr-only">
                                    <div class="mb-2 grid grid-cols-2 gap-1 w-full max-w-[80px]">
                                        <div class="h-5 rounded bg-neutral-400"></div>
                                        <div class="h-5 rounded bg-neutral-400"></div>
                                    </div>
                                    <span class="text-xs font-semibold">Grid</span>
                                    <span class="text-[10px] text-neutral-500">2 Columns</span>
                                </label>
                            </div>
                        </x-ui.card>

                        {{-- Button Shape & Style --}}
                        <x-ui.card title="Button Styling">
                            <div class="grid grid-cols-3 gap-2">
                                <label class="flex flex-col items-center rounded-xl border p-2.5 text-center cursor-pointer transition @if($button_style === 'filled') border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800 @else border-neutral-200 dark:border-neutral-800 @endif">
                                    <input type="radio" wire:model.live="button_style" value="filled" class="sr-only">
                                    <div class="mb-2 h-4 w-12 rounded bg-neutral-900 dark:bg-white"></div>
                                    <span class="text-xs font-semibold">Filled</span>
                                </label>

                                <label class="flex flex-col items-center rounded-xl border p-2.5 text-center cursor-pointer transition @if($button_style === 'soft') border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800 @else border-neutral-200 dark:border-neutral-800 @endif">
                                    <input type="radio" wire:model.live="button_style" value="soft" class="sr-only">
                                    <div class="mb-2 h-4 w-12 rounded bg-neutral-200 dark:bg-neutral-700"></div>
                                    <span class="text-xs font-semibold">Soft</span>
                                </label>

                                <label class="flex flex-col items-center rounded-xl border p-2.5 text-center cursor-pointer transition @if($button_style === 'outline') border-neutral-900 bg-neutral-50 dark:border-white dark:bg-neutral-800 @else border-neutral-200 dark:border-neutral-800 @endif">
                                    <input type="radio" wire:model.live="button_style" value="outline" class="sr-only">
                                    <div class="mb-2 h-4 w-12 rounded border-2 border-neutral-900 dark:border-white"></div>
                                    <span class="text-xs font-semibold">Outline</span>
                                </label>
                            </div>
                        </x-ui.card>
                    </div>

                    {{-- Colors & Branding --}}
                    <x-ui.card title="Color Accents &amp; Branding">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">Accent Color
                                    <div class="mt-1.5 flex items-center gap-3">
                                        <input type="color" wire:model.live="accent" value="{{ $accent ?? '#171717' }}" class="h-10 w-14 cursor-pointer rounded-lg border border-neutral-300 dark:border-neutral-700 p-0.5">
                                        <input type="text" wire:model.live="accent" placeholder="#171717" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-mono dark:border-neutral-700 dark:bg-neutral-950">
                                    </div>
                                </label>
                                <p class="mt-1 text-[11px] text-neutral-500">Highlights buttons, announcements, and accents.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">Browser Toolbar Color
                                    <div class="mt-1.5 flex items-center gap-3">
                                        <input type="color" wire:model.live="theme_color" value="{{ $theme_color ?? '#ffffff' }}" class="h-10 w-14 cursor-pointer rounded-lg border border-neutral-300 dark:border-neutral-700 p-0.5">
                                        <input type="text" wire:model.live="theme_color" placeholder="#ffffff" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-mono dark:border-neutral-700 dark:bg-neutral-950">
                                    </div>
                                </label>
                                <p class="mt-1 text-[11px] text-neutral-500">Colors the mobile Safari/Chrome top status bar.</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 space-y-3">
                            <label class="flex cursor-pointer items-start gap-2.5 text-xs text-neutral-700 dark:text-neutral-300">
                                <input type="checkbox" wire:model="hide_branding" class="mt-0.5 h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                                <div>
                                    <span class="font-semibold">Hide &ldquo;Powered by ternis.link&rdquo; badge</span>
                                    <p class="text-[11px] text-neutral-500">Removes system footer attribution (Pro &amp; Business plans).</p>
                                </div>
                            </label>
                            <x-ui.input label="Custom footer copyright or note" name="footer_text" type="text" wire:model="footer_text" placeholder="e.g. &copy; 2026 Acme Corp. All rights reserved." maxlength="140" />
                        </div>
                    </x-ui.card>
                </div>
            @endif

            {{-- TAB 3: HEADER & PROFILE --}}
            @if ($activeTab === 'profile')
                <div class="space-y-6">
                    <x-ui.card title="Profile Information">
                        <div class="space-y-4">
                            <x-ui.input label="Page Title *" name="title" type="text" wire:model="title" placeholder="e.g. Jane Doe" maxlength="80" required />

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-ui.input label="Profile Avatar image URL" name="avatar_url" type="url" wire:model.live="avatar_url" placeholder="https://example.com/avatar.jpg" maxlength="2048" hint="Square aspect ratio recommended (PNG, JPG, WebP)." />
                                </div>
                                <div class="flex items-center gap-3 pt-2">
                                    @if ($avatar_url)
                                        <img src="{{ $avatar_url }}" alt="Avatar preview" class="h-14 w-14 rounded-full object-cover border-2 border-neutral-200 dark:border-neutral-700 shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=Avatar'">
                                        <span class="text-xs text-neutral-500">Live avatar preview</span>
                                    @else
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-600 text-xs font-semibold">
                                            No pic
                                        </div>
                                        <span class="text-xs text-neutral-500">Paste URL to show profile photo</span>
                                    @endif
                                </div>
                            </div>

                            <x-ui.input label="Bio / Tagline" name="bio" type="text" wire:model="bio" placeholder="Creator, Designer &amp; Tech enthusiast. Building the future." maxlength="280" hint="Brief introduction shown right under your title." />

                            <x-ui.input label="Cover Banner Image URL" name="cover_url" type="url" wire:model="cover_url" placeholder="https://example.com/banner.jpg" maxlength="2048" hint="Optional hero banner at the top of your bio page." />
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Announcement Banner">
                        <div class="space-y-3">
                            <x-ui.input label="Announcement Headline" name="announcement_text" type="text" wire:model="announcement_text" placeholder="🎉 Tickets for our Europe Tour are now on sale!" maxlength="140" />
                            <x-ui.input label="Announcement Link (optional)" name="announcement_url" type="url" wire:model="announcement_url" placeholder="https://tickets.example.com" maxlength="2048" hint="When set, visitors tapping the announcement pill are redirected here." />
                        </div>
                    </x-ui.card>
                </div>
            @endif

            {{-- TAB 4: SETTINGS & ACCESS --}}
            @if ($activeTab === 'settings')
                <div class="space-y-6">
                    <x-ui.card title="Localization &amp; Display">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.select label="Language" name="locale" wire:model="locale">
                                <option value="en">English (en)</option>
                                <option value="de">Deutsch (de)</option>
                                <option value="fr">Français (fr)</option>
                                <option value="es">Español (es)</option>
                                <option value="it">Italiano (it)</option>
                            </x-ui.select>

                            <div class="flex items-center pt-6">
                                <label class="flex cursor-pointer items-start gap-2.5 text-xs text-neutral-700 dark:text-neutral-300">
                                    <input type="checkbox" wire:model="show_stats" class="mt-0.5 h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                                    <div>
                                        <span class="font-semibold">Show public view counter</span>
                                        <p class="text-[11px] text-neutral-500">Displays total page visits in the header.</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Password Protection">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input label="Page password" name="page_password" type="password" wire:model="page_password" placeholder="Min. 8 characters (blank keeps current)" maxlength="72" autocomplete="new-password" />
                            <x-ui.input label="Password hint (optional)" name="password_hint" type="text" wire:model="password_hint" placeholder="e.g. Favorite book title" maxlength="120" />
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Publishing &amp; Expiry Schedule">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-ui.input label="Publish at" name="published_at" type="datetime-local" wire:model="published_at" hint="Future dates hold the page unpublished until then." />
                            <x-ui.input label="Expires at" name="expires_at" type="datetime-local" wire:model="expires_at" hint="Page stops resolving after this date." />
                            <x-ui.input label="After expiry redirect URL" name="gone_url" type="url" wire:model="gone_url" placeholder="https://example.com/expired" maxlength="2048" hint="Optional fallback destination." />
                        </div>
                    </x-ui.card>

                    {{-- Sub-pages Management Panel --}}
                    @if ($family->id === $editing->id)
                        <x-ui.card title="Manage Sub-pages ({{ $family->children()->where('is_removed', false)->count() }}/10)">
                            <div class="space-y-3">
                                @forelse ($family->children()->where('is_removed', false)->orderBy('sort_order')->get() as $sub)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 bg-neutral-50/50 p-3 text-xs dark:border-neutral-800 dark:bg-neutral-800/40">
                                        <div class="min-w-0">
                                            <span class="font-mono font-semibold text-neutral-900 dark:text-white">/{{ $sub->slug }}</span>
                                            <span class="ml-2 text-neutral-500">&bull; {{ $sub->title }}</span>
                                            <span class="ml-2 text-neutral-400">({{ $sub->buttons->count() }} blocks)</span>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <button type="button" wire:click="edit('{{ $sub->id }}')" class="font-semibold text-neutral-700 hover:underline dark:text-neutral-200 cursor-pointer">Edit page</button>
                                            <button type="button" wire:click="duplicateSub('{{ $sub->id }}')" class="text-neutral-500 hover:text-neutral-800 dark:hover:text-white cursor-pointer">Duplicate</button>
                                            @if ($confirmingSubDelete === $sub->id)
                                                <button type="button" wire:click="deleteSub('{{ $sub->id }}')" class="font-bold text-red-600 hover:underline cursor-pointer">Confirm delete?</button>
                                            @else
                                                <button type="button" wire:click="deleteSub('{{ $sub->id }}')" class="text-red-600 hover:underline cursor-pointer">Delete</button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-neutral-500">No sub-pages configured for this domain yet.</p>
                                @endforelse

                                <div class="pt-2">
                                    <x-ui.button type="button" wire:click="$toggle('showNewSubModal')" variant="secondary" size="sm">+ Add a Sub-page</x-ui.button>
                                </div>
                            </div>
                        </x-ui.card>
                    @endif
                </div>
            @endif

            {{-- Bottom Save Banner --}}
            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                <div class="text-xs text-neutral-500">
                    <p class="font-medium text-neutral-700 dark:text-neutral-300">Ready to publish your edits?</p>
                    <p>Changes apply instantly to your live page and draft link.</p>
                </div>
                <x-ui.button wire:click="save" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save Changes</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </x-ui.button>
            </div>
        </div>

        {{-- Right Column: Interactive Phone Device Live Preview --}}
        <div class="xl:sticky xl:top-6">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-xs font-semibold tracking-widest text-neutral-500 uppercase">Live preview</p>
                @if ($editing && $family->domain)
                    <a
                        href="https://{{ $family->domain->hostname }}{{ $editing->parent_id ? '/' . $editing->slug : '' }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-[11px] font-medium text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
                    >
                        Open full page &rarr;
                    </a>
                @endif
            </div>

            {{-- Realistic iPhone Device Frame --}}
            <div class="relative mx-auto w-[320px] rounded-[48px] border-[10px] border-neutral-900 bg-neutral-950 p-2 shadow-2xl ring-1 ring-black/10 dark:border-neutral-800 dark:bg-black">
                {{-- Dynamic Island / Camera Pill --}}
                <div class="absolute top-4 left-1/2 -translate-x-1/2 z-20 flex h-5 w-24 items-center justify-center rounded-full bg-black">
                    <div class="h-2.5 w-2.5 rounded-full bg-neutral-900/90 ml-auto mr-2"></div>
                </div>

                {{-- Status Bar Mockup --}}
                <div class="relative z-10 flex items-center justify-between px-6 pt-3 pb-1 text-[11px] font-medium text-neutral-400 select-none">
                    <span>9:41</span>
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L12 22l7.03-4.39C20.26 16.07 21 14.12 21 12c0-4.97-4.03-9-9-9z"/></svg>
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><rect width="18" height="10" x="2" y="7" rx="2"/><circle cx="21" cy="12" r="1"/></svg>
                    </div>
                </div>

                {{-- Device Inner Screen Canvas --}}
                <div class="relative h-[620px] w-full overflow-y-auto rounded-[36px] bg-white shadow-inner scrollbar-thin dark:bg-neutral-950" style="background:{{ $theme === 'dark' ? '#111' : ($theme === 'paper' ? '#f7f3ea' : '#fff') }};color:{{ $theme === 'dark' ? '#f5f5f5' : '#171717' }}">
                    @if ($previewPage)
                        <div inert style="font-family:system-ui,sans-serif;min-height:100%;padding:16px 12px 40px;text-align:center;">
                            @include('bio._page', [
                                'page' => $previewPage,
                                'buttons' => $previewButtons,
                                'subs' => $previewSubs,
                                'preview' => true
                            ])
                        </div>
                    @endif
                </div>

                {{-- Home Indicator Bar --}}
                <div class="mx-auto mt-2 h-1 w-28 rounded-full bg-neutral-600 dark:bg-neutral-700"></div>
            </div>
        </div>
    </div>
    @endif
</div>
