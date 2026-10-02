<div>
    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live="search" placeholder="Search title or slug…" class="w-full max-w-xs rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
        <select wire:model.live="status" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="removed">Removed</option>
        </select>
    </div>

    <x-ui.table>
        <thead>
            <tr class="text-left text-xs tracking-widest text-neutral-500 uppercase">
                <th class="px-3 py-2">Page</th>
                <th class="px-3 py-2">Domain</th>
                <th class="px-3 py-2">Owner</th>
                <th class="px-3 py-2 text-right">Views</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($pages as $page)
            <tr class="border-t border-neutral-100 dark:border-neutral-800">
                <td class="px-3 py-2">
                    <span class="font-medium">{{ $page->title }}</span>
                    <span class="block font-mono text-xs text-neutral-500">{{ $page->parent_id ? '/' . $page->slug : '(root)' }}</span>
                </td>
                <td class="px-3 py-2 font-mono text-xs">{{ $page->domain?->hostname ?? '—' }}</td>
                <td class="px-3 py-2 text-xs">{{ $page->user?->email ?? '—' }}</td>
                <td class="px-3 py-2 text-right font-mono">{{ number_format($page->view_count) }}</td>
                <td class="px-3 py-2 text-xs">
                    @if ($page->is_removed)
                        <span class="rounded bg-red-100 px-2 py-0.5 font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">removed</span>
                    @elseif (! $page->is_active)
                        <span class="rounded bg-neutral-200 px-2 py-0.5 font-semibold dark:bg-neutral-700">inactive</span>
                    @else
                        <span class="rounded bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">active</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-right text-xs">
                    @if ($page->is_removed)
                        <button type="button" wire:click="restore('{{ $page->id }}')" class="cursor-pointer hover:underline">Restore</button>
                    @else
                        @if ($page->is_active)
                            <button type="button" wire:click="deactivate('{{ $page->id }}')" class="cursor-pointer hover:underline">Deactivate</button>
                        @else
                            <button type="button" wire:click="reactivate('{{ $page->id }}')" class="cursor-pointer hover:underline">Reactivate</button>
                        @endif
                        <button type="button" wire:click="remove('{{ $page->id }}')" class="ml-2 cursor-pointer text-red-600 hover:underline">Remove</button>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </x-ui.table>

    <div class="mt-4">{{ $pages->links() }}</div>
</div>
