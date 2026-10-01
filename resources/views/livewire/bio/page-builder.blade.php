<div class="grid gap-6 lg:grid-cols-2">
    <x-ui.card title="New bio page">
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
        <form wire:submit="savePage" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Title *" name="title" type="text" wire:model="title" required maxlength="80" />
            <x-ui.select label="Theme" name="theme" wire:model="theme">
                <option value="minimal">Minimal</option>
                <option value="dark">Dark</option>
                <option value="paper">Paper</option>
            </x-ui.select>
            <x-ui.input label="Bio" name="bio" type="text" wire:model="bio" maxlength="280" />
            <x-ui.input label="Avatar URL" name="avatar_url" type="url" wire:model="avatar_url" maxlength="2048" />
            <x-ui.input label="Publish at (optional)" name="published_at" type="datetime-local" wire:model="published_at" hint="Future dates hide the page until then. Blank = visible now." />
            <div class="flex items-end">
                <x-ui.button type="submit" variant="primary">Save page</x-ui.button>
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
                    <option value="header">Header</option>
                    <option value="divider">Divider</option>
                </x-ui.select>
                <x-ui.input label="Label" name="newLabel" type="text" wire:model="newLabel" maxlength="60" />
                <x-ui.input label="Sublabel" name="newSublabel" type="text" wire:model="newSublabel" maxlength="120" />
                <x-ui.input label="URL" name="newUrl" type="url" wire:model="newUrl" maxlength="2048" />
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
            <ul class="mt-4 space-y-2">
                @php($buttonList = $editing->buttons()->orderBy('sort_order')->get())
                @foreach ($buttonList as $index => $b)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-neutral-100 px-3 py-2 text-sm dark:border-neutral-800">
                        <span class="min-w-0">
                            <strong>{{ $b->kind }}</strong> — {{ $b->label }}
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
        </div>
    </x-ui.card>
@endif
