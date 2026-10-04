<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatsTest extends TestCase
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
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function test_guest_cannot_access_admin_stats(): void
    {
        $this->get('http://admin.ternis.link/stats')
            ->assertRedirect('http://admin.ternis.link/login');
    }

    public function test_non_admin_gets_forbidden_on_admin_stats(): void
    {
        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/stats')
            ->assertForbidden();
    }

    public function test_admin_can_access_stats_overview(): void
    {
        $link = Link::create([
            'slug' => 'statstest1',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 15,
        ]);

        Click::create([
            'link_id' => $link->id,
            'ip_hash' => 'ip_statsoverview',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'country_code' => 'US',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats');

        $response->assertOk();
        $response->assertSee('Stats &amp; System Analytics', escape: false);
        $response->assertSee('Link/Clicks Ratio', escape: false);
        $response->assertSee('Daily Link Clicks Traffic', escape: false);
    }

    public function test_admin_can_access_stats_errors_with_parameter_filtering(): void
    {
        ErrorEncounter::create([
            'http_code' => 404,
            'error_message' => 'Slug not found',
            'exception_class' => 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
            'method' => 'GET',
            'host' => 'href.nz',
            'path' => '/missing-page',
            'ip_hash' => 'ip_err1',
        ]);

        ErrorEncounter::create([
            'http_code' => 500,
            'error_message' => 'Internal server error occurred',
            'exception_class' => 'Exception',
            'method' => 'POST',
            'host' => 'dash.ternis.link',
            'path' => '/api/test',
            'ip_hash' => 'ip_err2',
        ]);

        // Access with code=404 parameter
        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/errors?code=404&days=7');

        $response->assertOk();
        $response->assertSee('Error Encounters Telemetry', escape: false);
        $response->assertSee('Slug not found');
        $response->assertDontSee('Internal server error occurred');
    }

    public function test_admin_can_access_stats_activities_with_parameters(): void
    {
        ActivityLog::create([
            'actor_id' => $this->admin->id,
            'action' => 'link.created',
            'ip_hash' => 'ip_act1',
        ]);

        ActivityLog::create([
            'actor_id' => $this->user->id,
            'action' => 'domain.verified',
            'ip_hash' => 'ip_act2',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/activities?action=link.created&days=14');

        $response->assertOk();
        $response->assertSee('Audit Activities Telemetry', escape: false);
        $response->assertSee('link.created');
    }

    public function test_admin_can_access_stats_links_with_parameters(): void
    {
        Link::create([
            'slug' => 'links-stat-slug',
            'destination_url' => 'https://example.com/one',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/links?tracking=1&days=30');

        $response->assertOk();
        $response->assertSee('Short Links Telemetry', escape: false);
        $response->assertSee('links-stat-slug');
    }

    public function test_admin_can_access_stats_clicks_with_parameters(): void
    {
        $link = Link::create([
            'slug' => 'clicks-stats-link',
            'destination_url' => 'https://example.com/two',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Click::create([
            'link_id' => $link->id,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148',
            'country_code' => 'DE',
            'ip_hash' => 'ip_click_mob',
            'query_params' => ['utm_source' => 'test'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/clicks?device=mobile&days=30');

        $response->assertOk();
        $response->assertSee('Link Clicks Traffic Telemetry', escape: false);
        $response->assertSee('mobile');
    }

    public function test_admin_can_access_stats_ratio(): void
    {
        $link1 = Link::create([
            'slug' => 'ratio-link-1',
            'destination_url' => 'https://example.com/a',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 100,
        ]);

        $link2 = Link::create([
            'slug' => 'ratio-link-2',
            'destination_url' => 'https://example.com/b',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/ratio');

        $response->assertOk();
        $response->assertSee('Link / Clicks Ratio &amp; Engagement Telemetry', escape: false);
        $response->assertSee('Average Clicks / Link', escape: false);
        $response->assertSee('Clicks-per-Link Volume Distribution Tiers', escape: false);
    }

    public function test_admin_stats_supports_json_format(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/errors?format=json&days=7');

        $response->assertOk();
        $response->assertJsonStructure([
            'section',
            'data' => [
                'days',
                'total_filtered',
                'timeline',
                'by_code',
            ],
        ]);
    }

    public function test_admin_stats_rejects_unknown_section(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/stats/non_existent_section')
            ->assertNotFound();
    }
}
