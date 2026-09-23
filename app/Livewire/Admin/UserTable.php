<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

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

    public function updateRole(int $userId, string $role): void
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

        $user->update(['role' => $role]);
    }

    public function updatePlan(int $userId, int $planId): void
    {
        $this->ensureAdmin();

        $plan = Plan::findOrFail($planId);
        $user = User::findOrFail($userId);
        $user->update(['plan_id' => $plan->id]);
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
        ]);
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
