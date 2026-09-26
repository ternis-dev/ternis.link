<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="user-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            class="max-w-xs"
        />
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
            <x-ui.select name="role-filter" wire:model.live="roleFilter" class="w-auto">
                <option value="all">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'user')
                        <th>User</th>
                    @elseif ($column === 'role')
                        <th>Role</th>
                    @elseif ($column === 'plan')
                        <th>Plan</th>
                    @elseif ($column === 'links')
                        <th>Links</th>
                    @elseif ($column === 'joined')
                        <th>Joined</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    @foreach ($visibleColumns as $column)
                        @if ($column === 'user')
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $user->avatarUrl(64) }}" alt="{{ $user->name }}" width="32" height="32" loading="lazy" class="h-8 w-8 rounded-full border border-neutral-200 object-cover dark:border-neutral-700">
                                    <div class="min-w-0">
                                        <div class="font-semibold">{{ $user->name }}</div>
                                        <div class="tl-sensitive truncate text-xs text-neutral-500" title="{{ $user->email }}">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                        @elseif ($column === 'role')
                            <td>
                                <x-ui.select :name="'role-'.$user->id" wire:change="updateRole('{{ $user->id }}', $event.target.value)">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->value }}" @selected($user->role === $role)>{{ ucfirst($role->value) }}</option>
                                    @endforeach
                                </x-ui.select>
                            </td>
                        @elseif ($column === 'plan')
                            <td>
                                <x-ui.select :name="'plan-'.$user->id" wire:change="updatePlan('{{ $user->id }}', $event.target.value)">
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected($user->plan_id === $plan->id)>{{ $plan->name }}</option>
                                    @endforeach
                                </x-ui.select>
                            </td>
                        @elseif ($column === 'links')
                            <td class="font-bold">{{ number_format($user->links_count) }}</td>
                        @elseif ($column === 'joined')
                            <td class="text-xs text-neutral-500">{{ $user->created_at->format('M d, Y') }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}"><x-ui.empty-state>No users found.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
