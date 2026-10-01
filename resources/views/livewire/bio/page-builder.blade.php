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
            <div class="sm:col-span-2">
                <x-ui.button type="submit" variant="primary">Save page</x-ui.button>
            </div>
        </form>

        <div class="mt-8 border-t border-neutral-100 pt-6 dark:border-neutral-800">
            <h3 class="font-semibold">Buttons ({{ $editing->buttons->count() }}/25)</h3>
            <form wire:submit="addButton" class="mt-3 grid gap-3 sm:grid-cols-4">
                <x-ui.select label="Kind" name="newKind" wire:model="newKind">
                    <option value="link">Link</option>
                    <option value="social">Social</option>
                    <option value="header">Header</option>
                    <option value="divider">Divider</option>
                </x-ui.select>
                <x-ui.input label="Label" name="newLabel" type="text" wire:model="newLabel" maxlength="60" />
                <x-ui.input label="URL" name="newUrl" type="url" wire:model="newUrl" maxlength="2048" />
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary">Add</x-ui.button>
                </div>
            </form>
            <ul class="mt-4 space-y-2">
                @foreach ($editing->buttons()->orderBy('sort_order')->get() as $b)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-neutral-100 px-3 py-2 text-sm dark:border-neutral-800">
                        <span><strong>{{ $b->kind }}</strong> — {{ $b->label }} <span class="text-neutral-500">({{ number_format($b->tap_count) }} taps)</span></span>
                        <button type="button" wire:click="removeButton('{{ $b->id }}')" class="cursor-pointer text-xs text-red-600 hover:underline">Remove</button>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-ui.card>
@endif
