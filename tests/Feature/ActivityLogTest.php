<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\UserTable;
use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Dashboard\LinkTable;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\SecurityAlert;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_link_creation_is_recorded(): void
    {
        $user = $this->userOnPlan('free');

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/article')
            ->set('domain_id', $this->systemDomain()->id)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'action' => ActivityLog::LINK_CREATED,
            'subject_owner_id' => $user->id,
        ]);
    }

    public function test_link_deactivation_is_recorded_with_owner(): void
    {
        $user = $this->userOnPlan('free');
        $link = $user->links()->create([
            'slug' => 'deact1',
            'destination_url' => 'https://example.com',
            'domain_id' => $this->systemDomain()->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(LinkTable::class)
            ->call('deactivate', $link->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'action' => ActivityLog::LINK_DEACTIVATED,
            'subject_type' => (new Link)->getMorphClass(),
            'subject_id' => $link->id,
            'subject_owner_id' => $user->id,
        ]);
    }

    public function test_admin_role_change_records_owner_and_notifies_user(): void
    {
        $admin = $this->userOnPlan('business', ['role' => UserRole::Admin]);
        $user = $this->userOnPlan('free');

        Notification::fake();

        Livewire::actingAs($admin)
            ->test(UserTable::class)
            ->call('updateRole', $user->id, UserRole::Partner->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => ActivityLog::ADMIN_USER_ROLE_CHANGED,
            'subject_owner_id' => $user->id,
        ]);

        Notification::assertSentTo($user, SecurityAlert::class);
    }

    public function test_login_and_logout_are_recorded(): void
    {
        $this->get('http://dash.ternis.link/auth/demo?role=user')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityLog::AUTH_LOGIN,
        ]);

        $this->post('http://dash.ternis.link/logout')
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityLog::AUTH_LOGOUT,
        ]);
    }

    public function test_user_activity_page_renders(): void
    {
        $user = $this->userOnPlan('free');

        $this->actingAs($user)
            ->get('http://dash.ternis.link/activity')
            ->assertOk()
            ->assertSee('Activity');
    }

    public function test_admin_audit_page_renders(): void
    {
        $admin = $this->userOnPlan('business', ['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('http://admin.ternis.link/activity')
            ->assertOk()
            ->assertSee('Audit Log');
    }

    private function userOnPlan(string $planName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'plan_id' => Plan::where('name', $planName)->firstOrFail()->id,
        ], $attributes));
    }

    private function systemDomain(): Domain
    {
        return Domain::whereNull('user_id')->where('is_active', true)->firstOrFail();
    }
}
