<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\User;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $roleFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updateRole(string $userId, string $role): void
    {
        $this->ensureAdmin();

        if (! in_array($role, array_column(UserRole::cases(), 'value'), true)) {
            abort(422, 'Invalid role.');
        }

        $user = User::findOrFail($userId);

        // Never lock yourself out: admins cannot demote their own account.
        if ($user->id === auth()->id() && $role !== UserRole::Admin->value) {
            abort(422, 'You cannot demote your own admin account.');
        }

        $oldRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $user->update(['role' => $role]);

        Activity::record(ActivityLog::ADMIN_USER_ROLE_CHANGED, auth()->user(), $user, [
            'old_role' => $oldRole,
            'new_role' => $role,
        ], $user);

        Notifier::security(
            $user,
            'Your account role changed',
            ["Your ternis.link role changed from {$oldRole} to {$role}."],
            DomainUrls::dashboard('/'),
            'Open dashboard',
        );

        Notifier::admins(
            "Role changed: {$user->email}",
            [auth()->user()->email." changed {$user->email} from {$oldRole} to {$role}."],
            DomainUrls::admin('/users'),
            'Manage users',
            auth()->user(),
        );
    }

    public function updatePlan(string $userId, string $planId): void
    {
        $this->ensureAdmin();

        $plan = Plan::findOrFail($planId);
        $user = User::findOrFail($userId);

        $oldPlan = $user->plan?->name ?? 'none';
        $user->update(['plan_id' => $plan->id]);

        Activity::record(ActivityLog::ADMIN_USER_PLAN_CHANGED, auth()->user(), $user, [
            'old_plan' => $oldPlan,
            'new_plan' => $plan->name,
        ], $user);

        Notifier::security(
            $user,
            'Your plan changed',
            ["Your ternis.link plan changed from {$oldPlan} to {$plan->name}."],
            DomainUrls::dashboard('/'),
            'Open dashboard',
        );

        Notifier::admins(
            "Plan changed: {$user->email}",
            [auth()->user()->email." changed {$user->email} from {$oldPlan} to {$plan->name}."],
            DomainUrls::admin('/users'),
            'Manage users',
            auth()->user(),
        );
    }

    public function render()
    {
        $this->ensureAdmin();

        $users = User::with(['plan'])
            ->withCount('links')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter !== 'all', fn ($q) => $q->where('role', $this->roleFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.user-table', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'plans' => Plan::orderBy('name')->get(),
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visible = $this->visibleColumns(),
            'columnCount' => count($visible),
        ]);
    }

    protected function tableKey(): string
    {
        return 'admin.users';
    }

    protected function availableColumns(): array
    {
        return [
            'user' => 'User',
            'role' => 'Role',
            'plan' => 'Plan',
            'links' => 'Links',
            'joined' => 'Joined',
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
