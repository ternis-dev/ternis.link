<div>
<div class="grid gap-6 lg:grid-cols-2">
    <x-ui.card title="New bio page">
        @if ($domains->isEmpty())
            <x-ui.alert tone="info" class="mb-4">
                Bio pages need your own verified custom domain (eligible plans only).
                <a href="{{ route('dashboard.domains') }}" class="font-semibold underline">Register and verify one under Domains</a>,
                then come back here.
            </x-ui.alert>
        @endif
        <p class="mb-4 text-sm text-neutral-500">Needs one of your verified custom domains. System domains can't host bio pages.</p>
        <form wire:submit="createRoot" class="space-y-4">
            <x-ui.select label="Domain *" name="domain_id" wire:model="domain_id" required>
                @foreach ($domains as $domain)
                    <option value="{{ $domain->id }}">{{ $domain->hostname }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input label="Title *" name="title" type="text" wire:model="title" placeholder="My links" required maxlength="80" />
            <x-ui.input label="Bio" name="bio" type="text" wire:model="bio" placeholder="One line about you" maxlength="280" />
            <x-ui.input label="Avatar URL" name="avatar_url" type="url" wire:model="avatar_url" placeholder="https://…" maxlength="2048" />
            <div class="flex gap-3 pt-1">
                <x-ui.button type="submit" variant="primary">Create page</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Your pages">
        @forelse ($pages as $p)
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-3 last:border-0 dark:border-neutral-800">
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $p->title }}</p>
                    <p class="font-mono text-xs text-neutral-500">{{ $p->domain?->hostname }}{{ $p->parent_id ? '/' . $p->slug : '' }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <x-ui.button wire:click="selectPage('{{ $p->id }}')" variant="ghost">Edit</x-ui.button>
                    @if ($p->parent_id !== null)
                        <x-ui.button wire:click="duplicateSub('{{ $p->id }}')" variant="ghost">Duplicate</x-ui.button>
                    @endif
                    @if (Route::has('dashboard.bio.build'))
                        <x-ui.button href="{{ route('dashboard.bio.build', $p->id) }}" variant="ghost">Visual</x-ui.button>
                    @endif
                    <x-ui.button href="{{ route('dashboard.bio.show', $p->id) }}" variant="ghost">Stats</x-ui.button>
                </div>
            </div>
        @empty
            <p class="text-sm text-neutral-500">No bio pages yet — create one to get started.</p>
        @endforelse
    </x-ui.card>
</div>

@if ($editing)
    <x-ui.card title="Editing: {{ $editing->title }}" class="mt-6">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <x-ui.button href="{{ route('dashboard.bio.show', $editing->id) }}" variant="ghost">Stats</x-ui.button>
            <x-ui.button wire:click="makeDraftLink" variant="ghost">Preview draft link</x-ui.button>
            @if ($draftUrl)
                <span class="inline-flex items-center gap-2 rounded-lg bg-amber-100 px-3 py-1.5 font-mono text-xs text-amber-900 dark:bg-amber-900/40 dark:text-amber-200" x-data="{ copied: false }">
                    <span class="select-all">{{ $draftUrl }}</span>
                    <button type="button" class="cursor-pointer font-sans font-semibold underline" x-on:click="navigator.clipboard.writeText('{{ $draftUrl }}'); copied = true; setTimeout(() => copied = false, 2000)" x-text="copied ? 'Copied!' : 'Copy'">Copy</button>
                    <span class="font-sans">expires {{ $draftExpires }}</span>
                </span>
            @endif
        </div>
        <div class="grid gap-8 lg:grid-cols-[1fr_300px]">
            <div>
        <form wire:submit="savePage" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Title *" name="title" type="text" wire:model="title" required maxlength="80" />
            <x-ui.select label="Theme" name="theme" wire:model="theme">
                <option value="minimal">Minimal</option>
                <option value="dark">Dark</option>
                <option value="paper">Paper</option>
                <option value="auto">Auto (system)</option>
            </x-ui.select>
            <x-ui.input label="Bio" name="bio" type="text" wire:model="bio" maxlength="280" />
            <x-ui.input label="Avatar URL" name="avatar_url" type="url" wire:model="avatar_url" maxlength="2048" />
            <x-ui.input label="Cover banner URL" name="cover_url" type="url" wire:model="cover_url" placeholder="https://…" maxlength="2048" />
            <x-ui.input label="Footer text" name="footer_text" type="text" wire:model="footer_text" placeholder="Blank = Powered by ternis.link" maxlength="140" />
            <x-ui.input label="Announcement" name="announcement_text" type="text" wire:model="announcement_text" placeholder="Banner line, e.g. New dates live!" maxlength="140" />
            <x-ui.input label="Announcement link" name="announcement_url" type="url" wire:model="announcement_url" placeholder="https://… (optional)" maxlength="2048" />
            <x-ui.select label="Language" name="locale" wire:model="locale">
                <option value="en">English</option>
                <option value="de">Deutsch</option>
                <option value="fr">Français</option>
                <option value="es">Español</option>
                <option value="it">Italiano</option>
            </x-ui.select>
            <x-ui.input label="Browser bar color" name="theme_color" type="text" wire:model="theme_color" placeholder="#ffffff" maxlength="7" />
            <x-ui.input label="Publish at (optional)" name="published_at" type="datetime-local" wire:model="published_at" hint="Future dates hide the page until then. Blank = visible now." />
            <x-ui.input label="Expires at (optional)" name="expires_at" type="datetime-local" wire:model="expires_at" hint="Page stops resolving after this. Blank = never." />
            <x-ui.select label="Button style" name="button_style" wire:model="button_style">
                <option value="filled">Filled</option>
                <option value="outline">Outline</option>
                <option value="soft">Soft</option>
            </x-ui.select>
            <x-ui.select label="Layout" name="layout" wire:model="layout">
                <option value="list">List</option>
                <option value="grid">Grid</option>
            </x-ui.select>
            <x-ui.input label="Page password (optional)" name="page_password" type="password" wire:model="page_password" placeholder="Min. 8 characters — blank keeps current" maxlength="72" autocomplete="new-password" />
            <x-ui.input label="Password hint (optional)" name="password_hint" type="text" wire:model="password_hint" placeholder="Shown on the locked page" maxlength="120" />
            <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                <input type="checkbox" wire:model="hide_branding" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white"> Hide “Powered by” footer
                <span class="font-normal text-neutral-500">(eligible plans only)</span>
            </label>
            <div class="flex flex-wrap items-end gap-2">
                <x-ui.button type="submit" variant="primary">Save page</x-ui.button>
                @if ($editing->password_hash)
                    <x-ui.button type="button" wire:click="removePassword" variant="ghost">Remove password</x-ui.button>
                @endif
            </div>
        </form>

        @if ($editing->parent_id === null)
            <div class="mt-8 border-t border-neutral-100 pt-6 dark:border-neutral-800">
                <h3 class="font-semibold">Sub-pages ({{ $editing->children->count() }}/10)</h3>
                <form wire:submit="createSub" class="mt-3 grid gap-3 sm:grid-cols-4">
                    <x-ui.input label="Slug *" name="slug" type="text" wire:model="slug" placeholder="socials" maxlength="64" hint="Lowercase, numbers, dashes." />
                    <x-ui.input label="Title *" name="subTitle" type="text" wire:model="subTitle" maxlength="80" />
                    <div class="flex items-end gap-2">
                        <x-ui.button type="submit" variant="primary">Add sub-page</x-ui.button>
                    </div>
                </form>
            </div>
        @endif

        <div class="mt-8 border-t border-neutral-100 pt-6 dark:border-neutral-800">
            <h3 class="font-semibold">Buttons ({{ $editing->buttons->count() }}/25)</h3>
            <form wire:submit="addButton" class="mt-3 grid gap-3 sm:grid-cols-4">
                <x-ui.select label="Kind" name="newKind" wire:model.live="newKind">
                    <option value="link">Link</option>
                    <option value="social">Social</option>
                    <option value="contact">Contact card</option>
                    <option value="video">Video</option>
                    <option value="image">Image</option>
                    <option value="countdown">Countdown</option>
                    <option value="header">Header</option>
                    <option value="divider">Divider</option>
                </x-ui.select>
                <x-ui.input label="Label" name="newLabel" type="text" wire:model="newLabel" maxlength="60" />
                <x-ui.input label="Sublabel" name="newSublabel" type="text" wire:model="newSublabel" maxlength="120" />
                <x-ui.input label="URL" name="newUrl" type="url" wire:model="newUrl" maxlength="2048" />
                <x-ui.input label="Thumbnail URL" name="newThumbnail" type="url" wire:model="newThumbnail" placeholder="https://…" maxlength="2048" />
                @if ($newKind === 'contact')
                    <x-ui.input label="Contact email" name="newContactEmail" type="email" wire:model="newContactEmail" maxlength="255" />
                    <x-ui.input label="Contact phone" name="newContactPhone" type="tel" wire:model="newContactPhone" maxlength="40" />
                @endif
                <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                    <input type="checkbox" wire:model="newOpenNew" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white"> Open in new tab
                </label>
                <x-ui.input label="Badge" name="newBadge" type="text" wire:model="newBadge" placeholder="NEW" maxlength="12" />
                <x-ui.input label="Countdown to" name="newEventAt" type="datetime-local" wire:model="newEventAt" hint="Only for countdown blocks." />
                <x-ui.select label="Action" name="newAction" wire:model.live="newAction">
                    <option value="url">Open URL</option>
                    <option value="subpage">Go to sub-page</option>
                    <option value="modal">Open pop-up</option>
                </x-ui.select>
                @if ($newAction === 'subpage')
                    <x-ui.select label="Sub-page" name="newTargetPage" wire:model="newTargetPage">
                        <option value="">Pick…</option>
                        @foreach ($actionTargets as $target)
                            <option value="{{ $target->id }}">{{ $target->parent_id ? '/' . $target->slug : '(root)' }} — {{ $target->title }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                @if ($newAction === 'modal')
                    <x-ui.input label="Pop-up title" name="newModalTitle" type="text" wire:model="newModalTitle" maxlength="80" />
                    <x-ui.input label="Pop-up text" name="newModalBody" type="text" wire:model="newModalBody" maxlength="1000" />
                @endif
                <x-ui.select label="Icon" name="newIcon" wire:model="newIcon">
                    <option value="">None</option>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="x">X</option>
                    <option value="youtube">YouTube</option>
                    <option value="github">GitHub</option>
                    <option value="globe">Website</option>
                    <option value="mail">Email</option>
                    <option value="link">Link</option>
                </x-ui.select>
                <x-ui.input label="Show from" name="newStartsAt" type="datetime-local" wire:model="newStartsAt" />
                <x-ui.input label="Show until" name="newEndsAt" type="datetime-local" wire:model="newEndsAt" />
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary">Add</x-ui.button>
                </div>
            </form>
            <ul class="mt-4 space-y-2" x-data="{ dragging: null }"
                x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'">
                @php($buttonList = $editing->buttons()->orderBy('sort_order')->get())
                @foreach ($buttonList as $index => $b)
                    <li draggable="true" data-bid="{{ $b->id }}"
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
                        class="flex cursor-grab items-center justify-between gap-3 rounded-lg border border-neutral-100 px-3 py-2 text-sm active:cursor-grabbing dark:border-neutral-800">
                        <span class="min-w-0">
                            <span class="text-neutral-400" aria-hidden="true">⠿</span>
                            <strong>{{ $b->kind }}</strong>@if(in_array($b->kind, ['link', 'social'], true))<span class="text-neutral-500"> · {{ $b->action }}</span>@endif — {{ $b->label }}
                            <span class="text-neutral-500">({{ number_format($b->tap_count) }} taps)</span>
                            @unless ($b->is_active)<span class="ml-1 rounded bg-neutral-200 px-1.5 py-0.5 text-[11px] font-semibold dark:bg-neutral-700">paused</span>@endunless
                            @if ($b->starts_at || $b->ends_at)<span class="ml-1 text-xs text-neutral-500">⏱ {{ $b->starts_at?->format('M j') ?? '…' }} → {{ $b->ends_at?->format('M j') ?? '…' }}</span>@endif
                        </span>
                        <span class="flex shrink-0 items-center gap-1.5 text-xs">
                            <button type="button" wire:click="moveButton('{{ $b->id }}', 'up')" class="cursor-pointer hover:underline disabled:cursor-default disabled:opacity-30" @disabled($index === 0)>↑</button>
                            <button type="button" wire:click="moveButton('{{ $b->id }}', 'down')" class="cursor-pointer hover:underline disabled:cursor-default disabled:opacity-30" @disabled($index === $buttonList->count() - 1)>↓</button>
                            <button type="button" wire:click="toggleButton('{{ $b->id }}')" class="cursor-pointer hover:underline">{{ $b->is_active ? 'Pause' : 'Resume' }}</button>
                            <button type="button" wire:click="removeButton('{{ $b->id }}')" class="cursor-pointer text-red-600 hover:underline">Remove</button>
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-neutral-500">Drag rows to reorder — order saves automatically (↑↓ buttons work everywhere, including touch).</p>
        </div>
            </div>
            <div class="hidden lg:block">
                <p class="mb-2 text-xs font-semibold tracking-widest text-neutral-500 uppercase">Live preview</p>
                <div class="mx-auto w-[280px] overflow-hidden rounded-[2rem] border-[10px] border-neutral-900 bg-white shadow-xl dark:border-black dark:bg-neutral-950">
                    <div class="mx-auto mt-2 h-5 w-24 rounded-full bg-neutral-900 dark:bg-black"></div>
                    @if ($previewPage)
                        <div inert style="font-family:system-ui,sans-serif;background:{{ $previewPage->theme === 'dark' ? '#111' : ($previewPage->theme === 'paper' ? '#f7f3ea' : '#fff') }};color:{{ $previewPage->theme === 'dark' ? '#f5f5f5' : '#171717' }}">
                            <div style="max-width:480px;margin:0 auto;padding:20px 14px 32px;text-align:center;transform:scale(.92);transform-origin:top center">
                                @include('bio._page', ['page' => $previewPage, 'buttons' => $previewButtons, 'subs' => $editing->children()->where('is_removed', false)->where('is_active', true)->orderBy('sort_order')->get(), 'preview' => true])
                            </div>
                        </div>
                    @endif
                </div>
                <p class="mt-2 text-center text-xs text-neutral-500">Title, bio, avatar &amp; theme update live. Buttons refresh on every change.</p>
            </div>
        </div>
    </x-ui.card>
@endif
</div>
