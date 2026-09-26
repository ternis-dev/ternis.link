<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();

        $link = Link::create([
            'slug' => 'statlink1',
            'destination_url' => 'https://example.com/stats',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 5,
        ]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => false, 'referrer' => 'https://secret.example/', 'user_agent' => 'SecretAgent/1.0']);
    }

    public function test_overview_is_public_on_ternis_host(): void
    {
        $this->get('http://ternis.link/pages/stats')
            ->assertStatus(200)
            ->assertSee('Network Stats', escape: false)
            ->assertSee('Links Created (All Time)', escape: false)
            ->assertSee('Links Created Per Day', escape: false);
    }

    public function test_subpages_are_public_on_ternis_host(): void
    {
        $this->get('http://ternis.link/pages/stats/domains')
            ->assertStatus(200)
            ->assertSee('Links Per Domain', escape: false)
            ->assertSee('href.nz', escape: false);

        $this->get('http://ternis.link/pages/stats/links')
            ->assertStatus(200)
            ->assertSee('Top Links', escape: false)
            ->assertSee('statlink1', escape: false);
    }

    public function test_stats_leak_no_personal_data(): void
    {
        foreach (['http://ternis.link/pages/stats', 'http://ternis.link/pages/stats/domains', 'http://ternis.link/pages/stats/links'] as $url) {
            $response = $this->get($url)->assertStatus(200);
            $response->assertDontSee($this->user->email, escape: false);
            $response->assertDontSee('secret.example', escape: false);
            $response->assertDontSee('SecretAgent', escape: false);
            $response->assertDontSee('https://example.com/stats', escape: false);
        }
    }

    public function test_removed_links_stay_counted_in_stats(): void
    {
        Link::where('slug', 'statlink1')->update(['is_removed' => true]);

        $this->get('http://ternis.link/pages/stats')
            ->assertStatus(200)
            ->assertSee('Removed Links', escape: false)
            ->assertViewHas('stats', fn ($stats) => $stats['total_links'] === 1
                && $stats['removed_links'] === 1
                && $stats['total_clicks'] === 1);

        $this->get('http://ternis.link/pages/stats/links')
            ->assertStatus(200)
            ->assertSee('statlink1', escape: false)
            ->assertSee('Removed', escape: false);
    }

    public function test_stats_404_on_other_hosts(): void
    {
        $this->get('http://href.nz/pages/stats')->assertNotFound();
        $this->get('http://dash.ternis.link/pages/stats')->assertNotFound();
        $this->get('http://admin.ternis.link/pages/stats')->assertNotFound();
    }

    public function test_legacy_stats_prefix_redirects_to_pages(): void
    {
        $this->get('http://ternis.link/stats')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/stats');

        $this->get('http://ternis.link/stats/domains')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/stats/domains');
    }
}
