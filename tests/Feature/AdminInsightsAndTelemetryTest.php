<?php

namespace Tests\Feature;

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

class AdminInsightsAndTelemetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_non_admin_cannot_access_admin_insights(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://admin.ternis.link/insights')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('http://admin.ternis.link/stats/insights')
            ->assertForbidden();
    }

    public function test_admin_can_access_insights_and_view_heatmap_and_trends(): void
    {
        $admin = User::factory()->admin()->create();
        $domain = Domain::where('hostname', 'clicked.at')->first();

        $link = Link::create([
            'slug' => 'insights-test',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $domain->id,
            'user_id' => $admin->id,
            'is_active' => true,
            'click_count' => 50,
        ]);

        Click::create([
            'link_id' => $link->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 TestUA/1.0',
            'referer' => 'https://news.ycombinator.com',
            'country_code' => 'DE',
            'is_direct_url' => false,
            'created_at' => now(),
        ]);

        // Route redirect
        $this->actingAs($admin)
            ->get('http://admin.ternis.link/insights')
            ->assertRedirect('http://admin.ternis.link/stats/insights');

        // Page view
        $response = $this->actingAs($admin)
            ->get('http://admin.ternis.link/stats/insights');

        $response->assertOk()
            ->assertSee('Traffic & Behavioral Insights', false)
            ->assertSee('Activity Heatmap')
            ->assertSee('Daily Clicks Contribution Calendar')
            ->assertSee('Hourly Click Profile')
            ->assertSee('"00:00"', false)
            ->assertSee('"23:00"', false)
            ->assertSee('data-calendar-heatmap', false)
            ->assertSee('data-heatmap', false)
            ->assertSee('Top Referrers')
            ->assertSee('Top Countries')
            ->assertSee('insights-test');
    }

    public function test_admin_clicks_page_displays_full_user_agent(): void
    {
        $admin = User::factory()->admin()->create();
        $domain = Domain::where('hostname', 'clicked.at')->first();

        $link = Link::create([
            'slug' => 'ua-test-link',
            'destination_url' => 'https://example.com/ua',
            'domain_id' => $domain->id,
            'user_id' => $admin->id,
            'is_active' => true,
        ]);

        $fullUa = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 SpecializedBot/2.4 (Contact: bot@example.org)';

        Click::create([
            'link_id' => $link->id,
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'user_agent' => $fullUa,
            'referer' => 'https://github.com',
            'country_code' => 'US',
            'is_direct_url' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('http://admin.ternis.link/stats/clicks')
            ->assertOk()
            ->assertSee('Recent Click Stream')
            ->assertSee($fullUa);
    }

    public function test_admin_timeline_charts_render_chartjs_canvases_and_mode_switches(): void
    {
        $admin = User::factory()->admin()->create();
        $domain = Domain::where('hostname', 'clicked.at')->first();

        $link = Link::create([
            'slug' => 'graph-test',
            'destination_url' => 'https://example.com/graph',
            'domain_id' => $domain->id,
            'user_id' => $admin->id,
            'is_active' => true,
        ]);

        Click::create([
            'link_id' => $link->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'TestUA',
            'is_direct_url' => false,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get('http://admin.ternis.link/stats/overview');

        $response->assertOk()
            ->assertSee('data-chart-type="line"', escape: false)
            ->assertSee('data-chart-unit="click"', escape: false)
            ->assertSee('data-chart-switch="line"', escape: false)
            ->assertSee('data-chart-switch="bar"', escape: false)
            ->assertSee('Graph', escape: false)
            ->assertSee('Bars', escape: false);
    }

    public function test_admin_error_logs_contain_user_agent_column(): void
    {
        $admin = User::factory()->admin()->create();

        $specialUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AdminAuditErrorTester/1.0';

        ErrorEncounter::create([
            'host' => 'dash.ternis.link',
            'path' => '/some/error/path',
            'method' => 'GET',
            'http_code' => 500,
            'exception_class' => \RuntimeException::class,
            'error_message' => 'Simulated test error',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => $specialUa,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('http://admin.ternis.link/stats/errors')
            ->assertOk()
            ->assertSee('User Agent (Fully Visible)')
            ->assertSee($specialUa);
    }
}
