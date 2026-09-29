<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\LinkModeration;
use App\Livewire\Admin\UserTable;
use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkTombstone;
use App\Models\Plan;
use App\Models\User;
use App\Support\Activity;
use App\Support\NetworkStats;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();
    }

    public function test_guest_is_redirected_to_login_on_admin_host(): void
    {
        $this->get('http://admin.ternis.link/')->assertRedirect('http://admin.ternis.link/login');
    }

    public function test_non_admin_gets_forbidden_on_admin_host(): void
    {
        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/')
            ->assertForbidden()
            ->assertSee('403', escape: false)
            ->assertSee('have access', escape: false);
    }

    public function test_admin_can_view_overview_with_system_stats(): void
    {
        $link = Link::create([
            'slug' => 'admin-stats-1',
            'destination_url' => 'https://example.com/stats',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 3,
        ]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => false]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => true]);

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/')
            ->assertStatus(200)
            ->assertSee('Admin Overview')
            ->assertSee('Admin Console', escape: false)
            ->assertViewHas('stats', fn ($stats) => $stats['total_users'] === 2
                && $stats['total_links'] === 1
                && $stats['active_links'] === 1
                && $stats['removed_links'] === 0
                && $stats['active_api_keys'] === 0
                && $stats['errors_today'] === 0
                && $stats['total_clicks'] === 2
                && $stats['direct_url_clicks'] === 1);
    }

    public function test_admin_can_view_links_and_users_pages(): void
    {
        $link = Link::create([
            'slug' => 'admin-blur-1',
            'destination_url' => 'https://example.com/blur',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
        Activity::record(ActivityLog::ADMIN_LINK_DEACTIVATED, $this->admin, $link, ['slug' => $link->slug]);

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/links')
            ->assertStatus(200)
            ->assertSee('Link Moderation')
            ->assertSee('tl-sensitive', escape: false); // owner emails blurred until hover

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/users')
            ->assertStatus(200)
            ->assertSee('User Management')
            ->assertSee(config('services.ternis_auth.avatar_base').'/', escape: false) // avatar CDN profile pictures
            ->assertSee('.png?size=64', escape: false)
            ->assertSee('tl-sensitive', escape: false);

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/activity')
            ->assertStatus(200)
            ->assertSee('tl-sensitive', escape: false);

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/domains')
            ->assertStatus(200)
            ->assertSee('Domain Moderation');

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/activity')
            ->assertStatus(200)
            ->assertSee('Audit Log');
    }

    public function test_legacy_admin_prefix_redirects_to_root(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/admin')
            ->assertStatus(301)
            ->assertRedirect('http://admin.ternis.link');

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/admin/users?page=2')
            ->assertStatus(301)
            ->assertRedirect('http://admin.ternis.link/users?page=2');
    }

    public function test_admin_routes_404_on_wrong_hosts(): void
    {
        // Dashboard host redirects into the admin area instead of 404ing.
        $this->actingAs($this->admin)
            ->get('http://dash.ternis.link/admin')
            ->assertRedirect('http://admin.ternis.link');

        // Public host must not serve the admin area either.
        $this->actingAs($this->admin)
            ->get('http://href.nz/admin')
            ->assertNotFound();
    }

    public function test_admin_console_serves_at_root(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/')
            ->assertStatus(200)
            ->assertSee('Admin Overview', escape: false);
    }

    public function test_dashboard_routes_404_on_admin_host(): void
    {
        // Strict split: user-only dashboard routes never serve on admin host.
        // (/links, /domains, /activity exist on both hosts but render
        // different consoles; these paths exist only on dash.)
        foreach (['http://admin.ternis.link/api-keys', 'http://admin.ternis.link/settings', 'http://admin.ternis.link/new'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertNotFound();
        }
    }

    public function test_link_moderation_can_deactivate_and_reactivate(): void
    {
        $link = Link::create([
            'slug' => 'moderate-me',
            'destination_url' => 'https://example.com/moderate',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->call('deactivate', $link->id);

        $this->assertFalse($link->fresh()->is_active);

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->call('reactivate', $link->id);

        $this->assertTrue($link->fresh()->is_active);
    }

    public function test_link_moderation_blocks_non_admins(): void
    {
        $link = Link::create([
            'slug' => 'no-touch',
            'destination_url' => 'https://example.com/no-touch',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(LinkModeration::class)
            ->assertForbidden();

        $this->assertTrue($link->fresh()->is_active);
    }

    public function test_link_moderation_can_delete_removed_link_and_preserves_stats(): void
    {
        $link = Link::create([
            'slug' => 'delete-me',
            'destination_url' => 'https://example.com/delete-me',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
            'is_removed' => true,
            'click_count' => 5,
        ]);

        foreach ([0, 0, 0, 1, 1] as $daysAgo) {
            $click = Click::create(['link_id' => $link->id, 'is_direct_url' => false]);
            $click->created_at = now()->subDays($daysAgo);
            $click->save();
        }

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $before = NetworkStats::overview();
        $beforeCreations = NetworkStats::creationsByDay(30);
        $beforeClicks = NetworkStats::clicksByDay(30);
        $beforeDomain = NetworkStats::domains()->firstWhere('hostname', 'href.nz');

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->call('delete', $link->id)
            ->assertHasNoErrors();

        // Row + click details are gone …
        $this->assertDatabaseMissing('links', ['id' => $link->id]);
        $this->assertSame(0, Click::where('link_id', $link->id)->count());

        // … but the tombstone keeps the aggregates …
        $this->assertDatabaseHas('link_tombstones', [
            'domain_hostname' => 'href.nz',
            'click_count' => 5,
        ]);
        $tombstone = LinkTombstone::first();
        $this->assertSame($today, $tombstone->created_day->format('Y-m-d'));
        $this->assertSame(3, (int) ($tombstone->clicks_by_day[$today] ?? 0));
        $this->assertSame(2, (int) ($tombstone->clicks_by_day[$yesterday] ?? 0));

        // … so every stat survives the hard delete.
        $after = NetworkStats::overview();
        $this->assertSame($before['total_links'], $after['total_links']);
        $this->assertSame($before['total_clicks'], $after['total_clicks']);
        $this->assertSame($before['links_today'], $after['links_today']);
        $this->assertSame($before['clicks_today'], $after['clicks_today']);
        $this->assertSame($beforeCreations['values'], NetworkStats::creationsByDay(30)['values']);
        $this->assertSame($beforeClicks['values'], NetworkStats::clicksByDay(30)['values']);

        $afterDomain = NetworkStats::domains()->firstWhere('hostname', 'href.nz');
        $this->assertSame($beforeDomain->links_count, $afterDomain->links_count);
        $this->assertSame($beforeDomain->links_sum_click_count, $afterDomain->links_sum_click_count);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityLog::ADMIN_LINK_DELETED,
            'subject_label' => 'delete-me',
        ]);
    }

    public function test_link_moderation_refuses_to_delete_active_link(): void
    {
        $link = Link::create([
            'slug' => 'keep-me',
            'destination_url' => 'https://example.com/keep-me',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->call('delete', $link->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('links', ['id' => $link->id]);
        $this->assertSame(0, LinkTombstone::count());
    }

    public function test_link_moderation_delete_blocks_non_admins(): void
    {
        $link = Link::create([
            'slug' => 'no-delete',
            'destination_url' => 'https://example.com/no-delete',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
            'is_removed' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(LinkModeration::class)
            ->assertForbidden();

        $this->assertDatabaseHas('links', ['id' => $link->id]);
    }

    public function test_user_table_can_change_role_and_plan(): void
    {
        $pro = Plan::where('name', 'pro')->first();

        Livewire::actingAs($this->admin)
            ->test(UserTable::class)
            ->call('updateRole', $this->user->id, UserRole::Partner->value)
            ->call('updatePlan', $this->user->id, $pro->id)
            ->assertHasNoErrors();

        $this->assertEquals(UserRole::Partner, $this->user->fresh()->role);
        $this->assertEquals($pro->id, $this->user->fresh()->plan_id);
    }

    public function test_user_table_blocks_self_demotion(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserTable::class)
            ->call('updateRole', $this->admin->id, UserRole::User->value)
            ->assertStatus(422);

        $this->assertEquals(UserRole::Admin, $this->admin->fresh()->role);
    }

    public function test_user_table_blocks_non_admins(): void
    {
        Livewire::actingAs($this->user)
            ->test(UserTable::class)
            ->assertForbidden();
    }
}
