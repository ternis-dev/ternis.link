<x-ui.card title="Targeting rules" class="mt-6">
    <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">
        Route visitors by country, device, or split traffic by weight.
        Best match wins (country + device beats either alone); ties split by weight.
        When nothing matches, visitors go to the link's main destination.
    </p>

    @if ($targets->isNotEmpty())
        <ul class="mb-6 space-y-2">
            @foreach ($targets as $t)
                @if ($editingTargetId === $t->id)
                    <li class="rounded-lg border-2 border-neutral-900 px-3 py-3 dark:border-white">
                        <form wire:submit="updateTarget" class="grid gap-2 sm:grid-cols-2">
                            <x-ui.input label="Label" name="label" type="text" wire:model="label" maxlength="60" />
                            <x-ui.input label="Destination URL *" name="destination_url" type="url" wire:model="destination_url" maxlength="2048" />
                            <x-ui.input label="Countries" name="country_codes" type="text" wire:model="country_codes" placeholder="US, DE" maxlength="200" hint="Comma-separated ISO codes, blank = anywhere." />
                            <x-ui.select label="Device" name="device" wire:model="device">
                                <option value="">Any device</option>
                                <option value="desktop">Desktop</option>
                                <option value="mobile">Mobile</option>
                                <option value="tablet">Tablet</option>
                            </x-ui.select>
                            <x-ui.input label="Weight" name="weight" type="number" wire:model="weight" min="0" max="10000" step="1" />
                            <div class="flex items-end gap-2 sm:col-span-2">
                                <x-ui.button type="submit" variant="primary">Save rule</x-ui.button>
                                <x-ui.button type="button" wire:click="cancelEdit">Cancel</x-ui.button>
                            </div>
                        </form>
                    </li>
                @else
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-neutral-100 px-3 py-2 text-sm dark:border-neutral-800">
                        <span class="min-w-0">
                            <strong>{{ $t->label ?? 'Rule' }}</strong>
                            <span class="block truncate font-mono text-xs text-neutral-500">{{ $t->destination_url }}</span>
                            <span class="text-xs text-neutral-500">
                                {{ $t->country_codes ? implode(', ', $t->country_codes).' · ' : '' }}{{ $t->device ?? 'any device' }} · weight {{ $t->weight }} ({{ $totalWeight > 0 ? round($t->weight / $totalWeight * 100, 1) : 0 }}%)
                            </span>
                            @unless ($t->is_active)<span class="ml-1 rounded bg-neutral-200 px-1.5 py-0.5 text-[11px] font-semibold dark:bg-neutral-700">paused</span>@endunless
                        </span>
                        <span class="flex shrink-0 items-center gap-1.5 text-xs">
                            <button type="button" wire:click="startEdit('{{ $t->id }}')" class="cursor-pointer hover:underline">Edit</button>
                            <button type="button" wire:click="toggleTarget('{{ $t->id }}')" class="cursor-pointer hover:underline">{{ $t->is_active ? 'Pause' : 'Resume' }}</button>
                            <button type="button" wire:click="removeTarget('{{ $t->id }}')" class="cursor-pointer text-red-600 hover:underline">Remove</button>
                        </span>
                    </li>
                @endif
            @endforeach
        </ul>
    @else
        <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">No rules yet — every visitor goes to the main destination.</p>
    @endif

    <form wire:submit="addTarget" class="grid gap-3 border-t border-neutral-100 pt-4 sm:grid-cols-2 dark:border-neutral-800">
        <x-ui.input label="Label" name="label" type="text" wire:model="label" placeholder="US mobile" maxlength="60" />
        <x-ui.input label="Destination URL *" name="destination_url" type="url" wire:model="destination_url" placeholder="https://…" maxlength="2048" />
        <x-ui.input label="Countries" name="country_codes" type="text" wire:model="country_codes" placeholder="US, DE" maxlength="200" />
        <x-ui.select label="Device" name="device" wire:model="device">
            <option value="">Any device</option>
            <option value="desktop">Desktop</option>
            <option value="mobile">Mobile</option>
            <option value="tablet">Tablet</option>
        </x-ui.select>
        <x-ui.input label="Weight" name="weight" type="number" wire:model="weight" min="0" max="10000" step="1" hint="Share of tied traffic. 0 pauses the rule." />
        <div class="flex items-end">
            <x-ui.button type="submit" variant="primary">Add rule</x-ui.button>
        </div>
    </form>
</x-ui.card>
