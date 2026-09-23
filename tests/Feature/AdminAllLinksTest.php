<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkTable;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAllLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_another_users_link_stats_and_export(): void
    {
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $domain = Domain::where('hostname', 'href.nz')->first();

        $link = Link::create([
            'slug' => 'other-users-link',
            'destination_url' => 'https://example.com/other',
            'domain_id' => $domain->id,
            'user_id' => $owner->id,
            'is_active' => true,
        ]);

        // Detail page
        $this->actingAs($admin)
            ->get("http://dash.ternis.link/dashboard/links/{$link->id}")
            ->assertStatus(200)
            ->assertSee('other-users-link');

        // Export
        $this->actingAs($admin)
            ->get("http://dash.ternis.link/dashboard/links/{$link->id}/export")
            ->assertStatus(200);

        // Non-admin still blocked
        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/dashboard/links/{$link->id}")
            ->assertStatus(404);
        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/dashboard/links/{$link->id}/export")
            ->assertStatus(404);
    }

    public function test_admin_link_table_lists_all_links_with_owner(): void
    {
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $domain = Domain::where('hostname', 'href.nz')->first();

        Link::create(['slug' => 'admin-own-1', 'destination_url' => 'https://example.com/a', 'domain_id' => $domain->id, 'user_id' => $admin->id, 'is_active' => true]);
        Link::create(['slug' => 'someone-else-1', 'destination_url' => 'https://example.com/b', 'domain_id' => $domain->id, 'user_id' => $owner->id, 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(LinkTable::class)
            ->assertSee('admin-own-1')
            ->assertSee('someone-else-1')
            ->assertSee($owner->email);

        Livewire::actingAs($owner)
            ->test(LinkTable::class)
            ->assertSee('someone-else-1')
            ->assertDontSee('admin-own-1');
    }

    public function test_admin_dashboard_stats_cover_all_links(): void
    {
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $domain = Domain::where('hostname', 'href.nz')->first();

        Link::create(['slug' => 'stat-a', 'destination_url' => 'https://example.com/a', 'domain_id' => $domain->id, 'user_id' => $owner->id, 'is_active' => true]);
        Link::create(['slug' => 'stat-b', 'destination_url' => 'https://example.com/b', 'domain_id' => $domain->id, 'user_id' => $owner->id, 'is_active' => true]);

        $this->actingAs($admin)
            ->get('http://dash.ternis.link/dashboard')
            ->assertStatus(200)
            ->assertSee('Admin view: stats across ALL links', escape: false)
            ->assertViewHas('stats', fn ($s) => $s['total_links'] === 2);
    }
}
