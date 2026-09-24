<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input
            name="user-search"
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            class="max-w-xs"
        />
        <x-ui.select name="role-filter" wire:model.live="roleFilter" class="w-auto">
            <option value="all">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Plan</th>
                <th>Links</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="font-semibold">{{ $user->name }}</div>
                        <div class="text-xs text-neutral-500">{{ $user->email }}</div>
                    </td>
                    <td>
                        <x-ui.select :name="'role-'.$user->id" wire:change="updateRole('{{ $user->id }}', $event.target.value)">
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($user->role === $role)>{{ ucfirst($role->value) }}</option>
                            @endforeach
                        </x-ui.select>
                    </td>
                    <td>
                        <x-ui.select :name="'plan-'.$user->id" wire:change="updatePlan('{{ $user->id }}', $event.target.value)">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected($user->plan_id === $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </td>
                    <td class="font-bold">{{ number_format($user->links_count) }}</td>
                    <td class="text-xs text-neutral-500">{{ $user->created_at->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><x-ui.empty-state>No users found.</x-ui.empty-state></td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
