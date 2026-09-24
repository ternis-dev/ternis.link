<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\GeoIpService;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsCleanupTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->first();
    }

    private function trackableLink(?User $user = null): Link
    {
        return Link::create([
            'slug' => 'geo-link-1',
            'destination_url' => 'https://example.com/geo',
            'domain_id' => $this->domain->id,
            'user_id' => $user?->id,
            'is_active' => true,
        ]);
    }

    public function test_click_stores_geo_from_resolver(): void
    {
        $link = $this->trackableLink();

        app()->singleton(GeoIpService::class, fn () => new GeoIpService(
            fn () => ['country_code' => 'de', 'city' => 'Berlin']
        ));

        $this->get('http://href.nz/geo-link-1')->assertStatus(302);

        $click = Click::where('link_id', $link->id)->first();
        $this->assertNotNull($click);
        $this->assertEquals('DE', $click->country_code);
        $this->assertEquals('Berlin', $click->city);
    }

    public function test_click_stores_country_from_cloudflare_header(): void
    {
        $link = $this->trackableLink();

        $this->get('http://href.nz/geo-link-1', ['CF-IPCountry' => 'nz'])
            ->assertStatus(302);

        $click = Click::where('link_id', $link->id)->first();
        $this->assertEquals('NZ', $click->country_code);
        $this->assertNull($click->city);
    }

    public function test_click_stores_null_geo_when_unknown(): void
    {
        $link = $this->trackableLink();

        $this->get('http://href.nz/geo-link-1')->assertStatus(302);

        $click = Click::where('link_id', $link->id)->first();
        $this->assertNull($click->country_code);
        $this->assertNull($click->city);
    }

    public function test_cleanup_command_deactivates_only_expired_active_links(): void
    {
        $expired = Link::create([
            'slug' => 'expired-1', 'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id, 'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);
        $future = Link::create([
            'slug' => 'future-1', 'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id, 'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);
        $alreadyInactive = Link::create([
            'slug' => 'inactive-1', 'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id, 'is_active' => false,
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('links:deactivate-expired')
            ->assertSuccessful()
            ->expectsOutput('Deactivated 1 expired link(s).');

        $this->assertFalse($expired->fresh()->is_active);
        $this->assertTrue($future->fresh()->is_active);
        $this->assertFalse($alreadyInactive->fresh()->is_active);
    }

    public function test_cleanup_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events());

        $event = $events->first(fn ($e) => str_contains((string) ($e->command ?? ''), 'links:deactivate-expired'));

        $this->assertNotNull($event);
        $this->assertEquals('0 0 * * *', $event->getExpression());
    }

    public function test_dashboard_hides_direct_url_clicks_from_non_admins(): void
    {
        $user = User::factory()->create();
        $link = $this->trackableLink($user);

        Click::create(['link_id' => $link->id, 'is_direct_url' => false]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => false]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => true]);

        $this->actingAs($user)
            ->get('http://dash.ternis.link')
            ->assertStatus(200)
            ->assertViewHas('stats', fn ($stats) => $stats['total_clicks'] === 2 && $stats['clicks_today'] === 2);
    }

    public function test_dashboard_shows_direct_url_clicks_to_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $link = $this->trackableLink($admin);

        Click::create(['link_id' => $link->id, 'is_direct_url' => false]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => true]);

        $this->actingAs($admin)
            ->get('http://dash.ternis.link')
            ->assertStatus(200)
            ->assertViewHas('stats', fn ($stats) => $stats['total_clicks'] === 2 && $stats['clicks_today'] === 2);
    }
}
