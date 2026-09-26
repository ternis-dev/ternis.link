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

    public function test_admin_cannot_view_another_users_link_on_dashboard_host(): void
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

        // Strict split: cross-user access lives on the admin host
        // (Link Moderation), not on dash.ternis.link.
        $this->actingAs($admin)
            ->get("http://dash.ternis.link/links/{$link->id}")
            ->assertStatus(404);
        $this->actingAs($admin)
            ->get("http://dash.ternis.link/links/{$link->id}/export")
            ->assertStatus(404);

        // Owner still sees their own link.
        $this->actingAs($owner)
            ->get("http://dash.ternis.link/links/{$link->id}")
            ->assertStatus(200)
            ->assertSee('other-users-link');

        // Non-admin still blocked
        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/links/{$link->id}")
            ->assertStatus(404);
        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/links/{$link->id}/export")
            ->assertStatus(404);
    }

    public function test_dashboard_link_table_lists_only_own_links(): void
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
            ->assertDontSee('someone-else-1');

        Livewire::actingAs($owner)
            ->test(LinkTable::class)
            ->assertSee('someone-else-1')
            ->assertDontSee('admin-own-1');
    }

    public function test_dashboard_stats_are_per_user_even_for_admins(): void
    {
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $domain = Domain::where('hostname', 'href.nz')->first();

        Link::create(['slug' => 'stat-a', 'destination_url' => 'https://example.com/a', 'domain_id' => $domain->id, 'user_id' => $owner->id, 'is_active' => true]);
        Link::create(['slug' => 'stat-b', 'destination_url' => 'https://example.com/b', 'domain_id' => $domain->id, 'user_id' => $owner->id, 'is_active' => true]);

        // Admins see only their own (zero) links on dash; system-wide
        // stats live on the admin host.
        $this->actingAs($admin)
            ->get('http://dash.ternis.link')
            ->assertStatus(200)
            ->assertDontSee('Admin view: stats across ALL links', escape: false)
            ->assertViewHas('stats', fn ($s) => $s['total_links'] === 0);
    }
}
