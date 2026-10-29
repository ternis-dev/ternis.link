<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\UserTable;
use App\Livewire\Dashboard\ActivityTable;
use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Dashboard\LinkTable;
use App\Models\ActivityLog;
use App\Models\ApiSessionToken;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\SecurityAlert;
use App\Support\Activity;
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

    public function test_dashboard_activity_log_shows_actions_performed_by_api_tokens(): void
    {
        $user = $this->userOnPlan('business');
        $key = $user->apiKeys()->create([
            'name' => 'Automated Deployer',
            'key_hash' => hash('sha256', 'raw-token-123'),
            'key_prefix' => 'tl_dep1',
            'api_version' => 1,
        ]);

        $link = $user->links()->create([
            'slug' => 'api-link-1',
            'destination_url' => 'https://example.com/api',
            'domain_id' => $this->systemDomain()->id,
            'api_key_id' => $key->id,
            'is_active' => true,
        ]);

        Activity::record(ActivityLog::LINK_CREATED, $user, $link, [
            'slug' => $link->slug,
            'via' => 'api',
            'auth_via' => 'api-key',
            'api_key_id' => $key->id,
            'api_key_name' => $key->name,
            'api_key_prefix' => $key->key_prefix,
        ]);

        Livewire::actingAs($user)
            ->test(ActivityTable::class)
            ->assertSee('Automated Deployer')
            ->assertSee('tl_dep1')
            ->set('sourceFilter', 'api')
            ->assertSee('Automated Deployer')
            ->set('sourceFilter', 'web')
            ->assertDontSee('api-link-1')
            ->set('sourceFilter', 'all')
            ->set('search', 'Deployer')
            ->assertSee('api-link-1')
            ->set('search', 'Nonexistent')
            ->assertDontSee('api-link-1');
    }

    public function test_activity_visible_to_includes_api_token_actions_even_without_actor_id(): void
    {
        $user = $this->userOnPlan('business');
        $key = $user->apiKeys()->create([
            'name' => 'CI Bot',
            'key_hash' => hash('sha256', 'raw-token-456'),
            'key_prefix' => 'tl_ci12',
            'api_version' => 1,
        ]);

        $entry = ActivityLog::create([
            'actor_id' => null,
            'action' => ActivityLog::LINK_CREATED,
            'subject_type' => (new Link)->getMorphClass(),
            'subject_id' => '01KTESTLINK123456789012345',
            'subject_label' => 'ci-slug',
            'metadata' => [
                'via' => 'api',
                'api_key_id' => $key->id,
                'api_key_name' => $key->name,
                'api_key_prefix' => $key->key_prefix,
            ],
        ]);

        $visibleIds = ActivityLog::visibleTo($user)->pluck('id');
        $this->assertTrue($visibleIds->contains($entry->id));
    }

    public function test_session_token_request_is_attributed_and_visible_only_to_its_owner(): void
    {
        $user = $this->userOnPlan('business');
        $other = $this->userOnPlan('business');
        $rawToken = 'ts_'.str_repeat('a', 48);
        $token = ApiSessionToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'token_prefix' => substr($rawToken, 0, 8),
            'expires_at' => now()->addHour(),
        ]);

        $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/session',
            'domain_id' => $this->systemDomain()->id,
            'slug' => 'session-audit',
        ], ['Authorization' => 'Bearer '.$rawToken])->assertCreated();

        $entry = ActivityLog::where('action', ActivityLog::LINK_CREATED)->firstOrFail();
        $this->assertSame($user->id, $entry->actor_id);
        $this->assertSame($token->id, $entry->metadata['session_token_id']);
        $this->assertSame($token->token_prefix, $entry->metadata['session_token_prefix']);
        $this->assertStringNotContainsString($rawToken, json_encode($entry->toArray()));

        Livewire::actingAs($user)
            ->test(ActivityTable::class)
            ->set('sourceFilter', 'api')
            ->assertSee('session-audit')
            ->assertSee('Session Token ('.$token->token_prefix)
            ->set('sourceFilter', 'web')
            ->assertDontSee('session-audit');

        Livewire::actingAs($other)
            ->test(ActivityTable::class)
            ->assertDontSee('session-audit');
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
