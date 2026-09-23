<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\LinkModeration;
use App\Livewire\Admin\UserTable;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
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
        $this->get('http://admin.ternis.link/admin')->assertRedirect(route('login'));
    }

    public function test_non_admin_gets_forbidden_on_admin_host(): void
    {
        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/admin')
            ->assertForbidden();
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
            ->get('http://admin.ternis.link/admin')
            ->assertStatus(200)
            ->assertSee('Admin Overview')
            ->assertViewHas('stats', fn ($stats) => $stats['total_users'] === 2
                && $stats['total_links'] === 1
                && $stats['total_clicks'] === 2
                && $stats['direct_url_clicks'] === 1);
    }

    public function test_admin_can_view_links_and_users_pages(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/admin/links')
            ->assertStatus(200)
            ->assertSee('Link Moderation');

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/admin/users')
            ->assertStatus(200)
            ->assertSee('User Management');
    }

    public function test_admin_routes_404_on_wrong_hosts(): void
    {
        // Dashboard host must not serve the admin area.
        $this->actingAs($this->admin)
            ->get('http://dash.ternis.link/admin')
            ->assertNotFound();

        // Public host must not serve the admin area either.
        $this->actingAs($this->admin)
            ->get('http://href.nz/admin')
            ->assertNotFound();
    }

    public function test_admin_root_redirects_to_admin_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/')
            ->assertRedirect(route('admin.dashboard'));
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
