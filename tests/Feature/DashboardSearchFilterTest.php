<?php

namespace Tests\Feature;

use App\Livewire\Admin\LinkModeration;
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

class DashboardSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->user = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();
    }

    public function test_user_can_filter_and_sort_by_most_clicks_on_dashboard(): void
    {
        $linkLow = Link::create([
            'slug' => 'low-clicks',
            'destination_url' => 'https://example.com/low',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'click_count' => 5,
            'is_active' => true,
        ]);

        $linkHigh = Link::create([
            'slug' => 'high-clicks',
            'destination_url' => 'https://example.com/high',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'click_count' => 250,
            'is_active' => true,
        ]);

        $linkZero = Link::create([
            'slug' => 'zero-clicks',
            'destination_url' => 'https://example.com/zero',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'click_count' => 0,
            'is_active' => true,
        ]);

        // Test sorting by most clicks
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('sortOption', 'most_clicks')
            ->assertSet('sortBy', 'click_count')
            ->assertSet('sortDir', 'desc')
            ->assertSeeInOrder(['high-clicks', 'low-clicks', 'zero-clicks']);

        // Test clicks filter: 100_plus
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('clicksFilter', '100_plus')
            ->assertSee('high-clicks')
            ->assertDontSee('low-clicks')
            ->assertDontSee('zero-clicks');

        // Test clicks filter: zero
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('clicksFilter', 'zero')
            ->assertSee('zero-clicks')
            ->assertDontSee('high-clicks')
            ->assertDontSee('low-clicks');
    }

    public function test_user_can_filter_by_status_and_domain(): void
    {
        $activeLink = Link::create([
            'slug' => 'active-slug',
            'destination_url' => 'https://example.com/active',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $disabledLink = Link::create([
            'slug' => 'disabled-slug',
            'destination_url' => 'https://example.com/disabled',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('statusFilter', 'disabled')
            ->assertSee('disabled-slug')
            ->assertDontSee('active-slug');
    }

    public function test_admin_can_search_all_platform_links_on_dashboard(): void
    {
        $otherUser = User::factory()->create(['email' => 'customer@example.org']);
        $otherLink = Link::create([
            'slug' => 'popular-customer-link',
            'destination_url' => 'https://example.com/customer',
            'domain_id' => $this->domain->id,
            'user_id' => $otherUser->id,
            'click_count' => 999,
            'is_active' => true,
        ]);

        // Default admin scope is 'mine'
        Livewire::actingAs($this->admin)
            ->test(LinkTable::class)
            ->assertDontSee('popular-customer-link');

        // Admin selects 'all' platform links and searches / sorts by most clicks
        Livewire::actingAs($this->admin)
            ->test(LinkTable::class)
            ->set('adminScope', 'all')
            ->set('sortOption', 'most_clicks')
            ->assertSee('popular-customer-link')
            ->assertSee('customer@example.org');
    }

    public function test_admin_link_moderation_has_enhanced_filters_and_clicks_sorting(): void
    {
        $link1 = Link::create([
            'slug' => 'mod-top-link',
            'destination_url' => 'https://example.com/mod1',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'click_count' => 500,
            'is_active' => true,
        ]);

        $link2 = Link::create([
            'slug' => 'mod-dormant-link',
            'destination_url' => 'https://example.com/mod2',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'click_count' => 0,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->set('sortOption', 'most_clicks')
            ->assertSet('sortBy', 'click_count')
            ->assertSet('sortDir', 'desc')
            ->assertSeeInOrder(['mod-top-link', 'mod-dormant-link'])
            ->set('clicksFilter', '100_plus')
            ->assertSee('mod-top-link')
            ->assertDontSee('mod-dormant-link');
    }
}
