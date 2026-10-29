<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-1 flex-wrap items-center gap-2">
            <x-ui.input
                name="search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name, IP, User-Agent..."
                class="max-w-xs"
            />
            <x-ui.select name="status-filter" wire:model.live="statusFilter" class="w-auto">
                <option value="all">All statuses</option>
                <option value="active">Active only</option>
                <option value="inactive">Inactive only</option>
            </x-ui.select>
            <x-ui.select name="type-filter" wire:model.live="typeFilter" class="w-auto">
                <option value="all">All rule types</option>
                <option value="pair">Allowed Pairs (IP + UA)</option>
                <option value="ip">IP only</option>
                <option value="ua">User-Agent only</option>
            </x-ui.select>
            @include('livewire.partials.column-customizer')
        </div>
        <div>
            <x-ui.button wire:click="openCreateModal" variant="primary">
                + Add Allowed Pair
            </x-ui.button>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'name')
                        <th>Name / Description</th>
                    @elseif ($column === 'ip_address')
                        <th>IP Address / CIDR</th>
                    @elseif ($column === 'user_agent')
                        <th>User-Agent</th>
                    @elseif ($column === 'rule_type')
                        <th>Rule Type</th>
                    @elseif ($column === 'status')
                        <th>Status</th>
                    @elseif ($column === 'created_by')
                        <th>Added By</th>
                    @elseif ($column === 'created_at')
                        <th>Added</th>
                    @endif
                @endforeach
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    @foreach ($visibleColumns as $column)
                        @if ($column === 'name')
                            <td class="max-w-[200px]">
                                <div class="font-medium text-neutral-900 dark:text-neutral-100 truncate" title="{{ $entry->name ?: '—' }}">
                                    {{ $entry->name ?: '—' }}
                                </div>
                                @if ($entry->notes)
                                    <div class="text-xs text-neutral-500 truncate" title="{{ $entry->notes }}">{{ $entry->notes }}</div>
                                @endif
                            </td>
                        @elseif ($column === 'ip_address')
                            <td class="font-mono text-xs">
                                @if ($entry->ip_address)
                                    <code class="rounded bg-neutral-100 px-1.5 py-0.5 dark:bg-neutral-800">{{ $entry->ip_address }}</code>
                                @else
                                    <span class="text-neutral-400 italic">Any IP</span>
                                @endif
                            </td>
                        @elseif ($column === 'user_agent')
                            <td class="font-mono text-xs max-w-sm truncate" title="{{ $entry->user_agent }}">
                                @if ($entry->user_agent)
                                    <span class="select-all">{{ $entry->user_agent }}</span>
                                @else
                                    <span class="text-neutral-400 italic">Any User-Agent</span>
                                @endif
                            </td>
                        @elseif ($column === 'rule_type')
                            <td>
                                <x-ui.badge tone="solid">{{ $entry->rule_type }}</x-ui.badge>
                            </td>
                        @elseif ($column === 'status')
                            <td>
                                @if ($entry->is_active)
                                    <x-ui.badge tone="solid">Active</x-ui.badge>
                                @else
                                    <x-ui.badge>Inactive</x-ui.badge>
                                @endif
                            </td>
                        @elseif ($column === 'created_by')
                            <td class="tl-sensitive text-xs text-neutral-500">
                                {{ $entry->creator?->email ?? 'System' }}
                            </td>
                        @elseif ($column === 'created_at')
                            <td class="whitespace-nowrap text-xs text-neutral-500" title="{{ $entry->created_at }}">
                                {{ $entry->created_at?->diffForHumans() }}
                            </td>
                        @endif
                    @endforeach
                    <td class="whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <x-ui.button wire:click="toggleActive('{{ $entry->id }}')" size="sm" variant="ghost">
                                {{ $entry->is_active ? 'Disable' : 'Enable' }}
                            </x-ui.button>
                            <x-ui.button wire:click="openEditModal('{{ $entry->id }}')" size="sm" variant="secondary">
                                Edit
                            </x-ui.button>
                            <x-ui.button wire:click="delete('{{ $entry->id }}')" wire:confirm="Delete this allowed pair?" size="sm" variant="danger">
                                Delete
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}">
                        <x-ui.empty-state>
                            No allowed pairs configured. Allowed clients bypass rate limits and guest quotas.
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $entries->links() }}
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/50 backdrop-blur-xs">
            <div class="relative w-full max-w-xl rounded-xl border border-neutral-200 bg-white p-6 shadow-xl dark:border-neutral-800 dark:bg-neutral-900" @keydown.escape.window="$wire.closeModal()">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <h2 class="font-display text-lg font-semibold tracking-tight">
                        {{ $editingId ? 'Edit Allowed Pair' : 'Add Allowed Pair' }}
                    </h2>
                    <button
                        type="button"
                        wire:click="closeModal"
                        aria-label="Close dialog"
                        class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-transparent text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label for="allow-name" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">
                            Name / Label (optional)
                        </label>
                        <x-ui.input
                            id="allow-name"
                            name="name"
                            type="text"
                            wire:model="name"
                            placeholder="e.g. Twins on Ice Fallback Service"
                        />
                        @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="allow-ip" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">
                            IP Address / CIDR (optional)
                        </label>
                        <x-ui.input
                            id="allow-ip"
                            name="ip_address"
                            type="text"
                            wire:model="ip_address"
                            placeholder="e.g. 192.168.1.1, 10.0.0.0/8 (or leave blank if any IP)"
                        />
                        <p class="text-[11px] text-neutral-500 mt-1">Supports single IPv4/IPv6, comma-separated IPs, or CIDR blocks.</p>
                        @error('ip_address') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="allow-ua" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">
                            User-Agent (optional)
                        </label>
                        <x-ui.input
                            id="allow-ua"
                            name="user_agent"
                            type="text"
                            wire:model="user_agent"
                            placeholder="e.g. twins-on-ice-fallback/1.0 or twins-on-ice* (or leave blank if any UA)"
                        />
                        <p class="text-[11px] text-neutral-500 mt-1">Supports exact matches, wildcard patterns (e.g. *bot*), or substrings.</p>
                        @error('user_agent') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="allow-notes" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">
                            Notes / Context (optional)
                        </label>
                        <x-ui.input
                            id="allow-notes"
                            name="notes"
                            type="text"
                            wire:model="notes"
                            placeholder="e.g. Fallback automation for link redirection during migration"
                        />
                        @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="allow-active"
                            type="checkbox"
                            wire:model="is_active"
                            class="rounded border-neutral-300 text-amber-500 focus:ring-amber-400 dark:border-neutral-700 dark:bg-neutral-800"
                        />
                        <label for="allow-active" class="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                            Active (rule takes effect immediately)
                        </label>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
                        <x-ui.button type="button" wire:click="closeModal" variant="ghost">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Save Rule</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
