<div>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('dashboard.bio') }}" class="text-sm text-neutral-500 hover:underline">&larr; All bio pages</a>
        <span class="font-mono text-xs text-neutral-500">{{ $family->domain?->hostname }}</span>
        <span class="ml-auto flex flex-wrap gap-2">
            <x-ui.button wire:click="makeDraftLink" variant="ghost">Preview draft link</x-ui.button>
            <x-ui.button href="{{ route('dashboard.bio.show', $editing?->id) }}" variant="ghost">Stats</x-ui.button>
        </span>
    </div>

    @if ($draftUrl)
        <x-ui.alert tone="info" class="mb-4">
            <span class="font-mono text-xs select-all">{{ $draftUrl }}</span>
            <span class="text-xs"> — valid until {{ $draftExpires }}, not tracked, not indexed.</span>
        </x-ui.alert>
    @endif

    <div class="mb-4 flex gap-2 overflow-x-auto" role="tablist" aria-label="Pages">
        <button type="button" wire:click="edit('{{ $family->id }}')" @class(['shrink-0 rounded-full px-4 py-1.5 text-sm font-medium', $editing?->id === $family->id ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'border border-neutral-200 dark:border-neutral-700']) role="tab" @if($editing?->id === $family->id) aria-selected="true" @endif>
            ⌂ {{ $family->title }}
        </button>
        @foreach ($family->children()->where('is_removed', false)->orderBy('sort_order')->get() as $sub)
            <button type="button" wire:click="edit('{{ $sub->id }}')" @class(['shrink-0 rounded-full px-4 py-1.5 text-sm font-medium', $editing?->id === $sub->id ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'border border-neutral-200 dark:border-neutral-700']) role="tab" @if($editing?->id === $sub->id) aria-selected="true" @endif>
                /{{ $sub->slug }}
            </button>
        @endforeach
    </div>

    @if ($editing)
    <div class="grid items-start gap-6 xl:grid-cols-[1fr_320px]">
        <div class="min-w-0 space-y-6">
            <x-ui.card title="Page settings">
                <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input label="Title *" name="title" type="text" wire:model="title" required maxlength="80" />
                    <x-ui.input label="Bio" name="bio" type="text" wire:model="bio" maxlength="280" />
                    <x-ui.input label="Avatar URL" name="avatar_url" type="url" wire:model="avatar_url" maxlength="2048" />
                    <x-ui.input label="Cover banner URL" name="cover_url" type="url" wire:model="cover_url" maxlength="2048" />
                    <x-ui.input label="Footer text" name="footer_text" type="text" wire:model="footer_text" maxlength="140" />
                    <x-ui.input label="Announcement" name="announcement_text" type="text" wire:model="announcement_text" maxlength="140" />
                    <x-ui.input label="Announcement link" name="announcement_url" type="url" wire:model="announcement_url" maxlength="2048" />
                    <x-ui.select label="Language" name="locale" wire:model="locale">
                        <option value="en">English</option>
                        <option value="de">Deutsch</option>
                        <option value="fr">Français</option>
                        <option value="es">Español</option>
                        <option value="it">Italiano</option>
                    </x-ui.select>
                    <x-ui.select label="Theme" name="theme" wire:model="theme">
                        <option value="minimal">Minimal</option>
                        <option value="dark">Dark</option>
                        <option value="paper">Paper</option>
                        <option value="auto">Auto (system)</option>
                    </x-ui.select>
                    <x-ui.select label="Button style" name="button_style" wire:model="button_style">
                        <option value="filled">Filled</option>
                        <option value="outline">Outline</option>
                        <option value="soft">Soft</option>
                    </x-ui.select>
                    <x-ui.select label="Layout" name="layout" wire:model="layout">
                        <option value="list">List</option>
                        <option value="grid">Grid</option>
                    </x-ui.select>
                    <x-ui.input label="Page password" name="page_password" type="password" wire:model="page_password" placeholder="Min. 8 chars — blank keeps current" maxlength="72" autocomplete="new-password" />
                    <x-ui.input label="Password hint" name="password_hint" type="text" wire:model="password_hint" maxlength="120" />
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                        <input type="checkbox" wire:model="hide_branding" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white"> Hide footer
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-medium text-neutral-600 dark:text-neutral-400">Accent
                            <input type="color" wire:model="accent" value="{{ $accent ?? '#171717' }}" class="mt-1 block h-10 w-full cursor-pointer rounded-lg border border-neutral-300 dark:border-neutral-700">
                        </label>
                        <label class="block text-sm font-medium text-neutral-600 dark:text-neutral-400">Browser bar
                            <input type="color" wire:model="theme_color" value="{{ $theme_color ?? '#ffffff' }}" class="mt-1 block h-10 w-full cursor-pointer rounded-lg border border-neutral-300 dark:border-neutral-700">
                        </label>
                    </div>
                    <x-ui.input label="Publish at" name="published_at" type="datetime-local" wire:model="published_at" hint="Future dates hide the page until then. Meta tags: assets-upload via static.re — soon." />
                    <x-ui.input label="Expires at" name="expires_at" type="datetime-local" wire:model="expires_at" hint="Page stops resolving after this. Blank = never." />
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
                    </div>
                </form>
                <p class="mt-3 text-xs text-neutral-500">Meta tags (description, theme-color, Open Graph) render automatically. Image uploads via static.re — soon; paste URLs for now.</p>
            </x-ui.card>

            <x-ui.card title="Buttons — drag to reorder">
                <form wire:submit="addButton" class="grid gap-3 sm:grid-cols-3">
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
                    <x-ui.select label="Action" name="newAction" wire:model.live="newAction">
                        <option value="url">Open URL</option>
                        <option value="subpage">Go to sub-page</option>
                        <option value="modal">Open pop-up</option>
                    </x-ui.select>
                    <x-ui.input label="Label" name="newLabel" type="text" wire:model="newLabel" maxlength="60" />
                    <x-ui.input label="Sublabel" name="newSublabel" type="text" wire:model="newSublabel" maxlength="120" />
                    <x-ui.input label="URL" name="newUrl" type="url" wire:model="newUrl" maxlength="2048" />
                    <x-ui.input label="Thumbnail URL" name="newThumbnail" type="url" wire:model="newThumbnail" maxlength="2048" />
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
                    @if ($newKind === 'contact')
                        <x-ui.input label="Contact email" name="newContactEmail" type="email" wire:model="newContactEmail" maxlength="255" />
                        <x-ui.input label="Contact phone" name="newContactPhone" type="tel" wire:model="newContactPhone" maxlength="40" />
                    @endif
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                        <input type="checkbox" wire:model="newOpenNew" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white"> Open in new tab
                    </label>
                    <x-ui.input label="Badge" name="newBadge" type="text" wire:model="newBadge" placeholder="NEW" maxlength="12" />
                    <x-ui.input label="Countdown to" name="newEventAt" type="datetime-local" wire:model="newEventAt" hint="Only for countdown blocks." />
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
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary">Add button</x-ui.button>
                    </div>
                </form>

                <ul class="mt-4 space-y-2" x-data="{ dragging: null }"
                    x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'">
                    @foreach ($editing->buttons()->orderBy('sort_order')->get() as $b)
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
                            class="flex cursor-grab items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm active:cursor-grabbing dark:border-neutral-700 dark:bg-neutral-900">
                            <span class="min-w-0">
                                <span class="text-neutral-400" aria-hidden="true">⠿</span>
                                <strong>{{ $b->kind }}</strong>@if(in_array($b->kind, ['link', 'social'], true))<span class="text-neutral-500"> · {{ $b->action }}</span>@endif — {{ $b->label }}
                                <span class="text-neutral-500">({{ number_format($b->tap_count) }})</span>
                                @unless ($b->is_active)<span class="ml-1 rounded bg-neutral-200 px-1.5 py-0.5 text-[11px] font-semibold dark:bg-neutral-700">paused</span>@endunless
                            </span>
                            <span class="flex shrink-0 items-center gap-1.5 text-xs">
                                <button type="button" wire:click="move('{{ $b->id }}', 'up')" class="cursor-pointer hover:underline" title="Move up">↑</button>
                                <button type="button" wire:click="move('{{ $b->id }}', 'down')" class="cursor-pointer hover:underline" title="Move down">↓</button>
                                <button type="button" wire:click="toggleButton('{{ $b->id }}')" class="cursor-pointer hover:underline">{{ $b->is_active ? 'Pause' : 'Resume' }}</button>
                                <button type="button" wire:click="removeButton('{{ $b->id }}')" class="cursor-pointer text-red-600 hover:underline">Remove</button>
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-neutral-500">Drag rows to reorder — order saves automatically (↑↓ buttons work everywhere, including touch).</p>
            </x-ui.card>
        </div>

        <div class="xl:sticky xl:top-6">
            <p class="mb-2 text-xs font-semibold tracking-widest text-neutral-500 uppercase">Live preview</p>
            <div class="mx-auto w-[280px] overflow-hidden rounded-[2rem] border-[10px] border-neutral-900 bg-white shadow-xl dark:border-black dark:bg-neutral-950">
                <div class="mx-auto mt-2 h-5 w-24 rounded-full bg-neutral-900 dark:bg-black"></div>
                @if ($previewPage)
                    <div inert style="font-family:system-ui,sans-serif;background:{{ $previewPage->theme === 'dark' ? '#111' : ($previewPage->theme === 'paper' ? '#f7f3ea' : '#fff') }};color:{{ $previewPage->theme === 'dark' ? '#f5f5f5' : '#171717' }}">
                        <div style="max-width:480px;margin:0 auto;padding:20px 14px 32px;text-align:center;transform:scale(.92);transform-origin:top center">
                            @include('bio._page', ['page' => $previewPage, 'buttons' => $previewButtons, 'subs' => $previewSubs, 'preview' => true])
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
