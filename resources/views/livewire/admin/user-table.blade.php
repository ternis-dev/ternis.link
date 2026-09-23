<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            style="max-width: 320px; padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
        <select
            wire:model.live="roleFilter"
            style="padding: 0.625rem 0.875rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
        >
            <option value="all">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-container">
        <table>
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
                            <div style="font-weight: 600;">{{ $user->name }}</div>
                            <div style="color: var(--text-muted); font-size: 0.85rem;">{{ $user->email }}</div>
                        </td>
                        <td>
                            <select
                                wire:change="updateRole({{ $user->id }}, $event.target.value)"
                                style="padding: 0.375rem 0.5rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
                            >
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($user->role === $role)>{{ ucfirst($role->value) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select
                                wire:change="updatePlan({{ $user->id }}, $event.target.value)"
                                style="padding: 0.375rem 0.5rem; background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-primary);"
                            >
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected($user->plan_id === $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><strong style="color: var(--primary);">{{ number_format($user->links_count) }}</strong></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $user->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $users->links() }}
    </div>
</div>
