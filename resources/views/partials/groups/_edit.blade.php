    <x-ui.page-header
        title="Edit {{ $group->name }}"
        backHref="{{ route($groupRoutePrefix.'groups.show', $group->id) }}"
        backLabel="Back to {{ $group->name }}"
    />

    <x-ui.card class="w-full">
        <form method="POST" action="{{ route($groupRoutePrefix.'groups.update', $group->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <x-ui.input id="name" name="name" :value="old('name', $group->name)" required maxlength="120" />
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-medium">Description <span class="font-normal text-neutral-400">(optional)</span></label>
                <textarea id="description" name="description" rows="3" maxlength="1000" class="w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900">{{ old('description', $group->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="color" class="mb-1 block text-sm font-medium">Color</label>
                    <input type="color" id="color" name="color" value="{{ old('color', $group->color ?? '#6366f1') }}" class="h-9 w-12 cursor-pointer rounded-lg border border-neutral-200 dark:border-neutral-800" />
                    @error('color')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="parent_id" class="mb-1 block text-sm font-medium">Parent group</label>
                    <select id="parent_id" name="parent_id" class="w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <option value="">— Top level —</option>
                        @foreach ($candidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected(old('parent_id', $group->parent_id) === $candidate->id)>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200/80 bg-neutral-50/50 p-4 space-y-3 dark:border-neutral-800 dark:bg-neutral-900/40">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">Group Protection Policy (optional)</span>
                </div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Links inside this group inherit this protection strength unless explicitly overridden on the link.</p>
                <div>
                    <label for="bot_protection_level" class="mb-1 block text-sm font-medium">Protection Level</label>
                    <select id="bot_protection_level" name="bot_protection_level" class="w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <option value="off" @selected(old('bot_protection_level', $group->bot_protection_level ?? 'off') === 'off')>Off — Direct redirect</option>
                        <option value="standard" @selected(old('bot_protection_level', $group->bot_protection_level ?? '') === 'standard')>Standard — Smart verification challenge</option>
                        <option value="strict" @selected(old('bot_protection_level', $group->bot_protection_level ?? '') === 'strict')>Strict — Mandatory verification before redirect</option>
                        <option value="custom_captcha" @selected(old('bot_protection_level', $group->bot_protection_level ?? '') === 'custom_captcha')>Custom Privacy CAPTCHA — Self-hosted check (0 external trackers)</option>
                    </select>
                </div>
            </div>

            <div class="pt-1">
                <x-ui.checkbox
                    name="is_active"
                    value="1"
                    :checked="old('is_active', $group->is_active)"
                    label="Active"
                    description="When disabled, this group is archived from active filters."
                />
            </div>

            <div class="flex items-center gap-2 pt-2">
                <x-ui.button type="submit" variant="primary">Save</x-ui.button>
                <x-ui.button href="{{ route($groupRoutePrefix.'groups.show', $group->id) }}" variant="secondary">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-4 w-full border-red-200 dark:border-red-900">
        <h2 class="text-sm font-semibold text-red-700 dark:text-red-300">Danger zone</h2>
        <p class="mt-1 text-sm text-neutral-500">Deleting removes the group only — links stay, subgroups move up one level.</p>
        <form method="POST" action="{{ route($groupRoutePrefix.'groups.delete', $group->id) }}" class="mt-3" onsubmit="return confirm('Delete group “{{ $group->name }}”?')">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger" size="sm">Delete Group</x-ui.button>
        </form>
    </x-ui.card>