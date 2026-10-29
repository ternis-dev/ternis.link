<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_href_re_host_resolves_as_business_type(): void
    {
        $domain = Domain::where('hostname', 'href.re')->first();
        $this->assertNotNull($domain);
        $this->assertSame('business', $domain->type->value);
    }

    public function test_href_re_landing_renders_business_presentation(): void
    {
        $response = $this->get('http://href.re/');

        $response->assertStatus(200);

        // Core identity & header
        $response->assertSee('href<span>.re</span>', escape: false);
        $response->assertSee('Official · Business only', escape: false);
        $response->assertSee('Official links', escape: false);
        $response->assertSee('recognizable', escape: false);

        // Does NOT have guest creation form
        $response->assertDontSee('Shorten a link', escape: false);

        // Key principles in minimalist grayscale theme
        $response->assertSee('Business only', escape: false);
        $response->assertSee('Trusted by default', escape: false);
        $response->assertSee('Measured', escape: false);

        // Fully simplified: does not contain heavy legacy bloat (FAQ, how-it-works, telemetry counters)
        $response->assertDontSee('Frequently asked questions', escape: false);
        $response->assertDontSee('How it works', escape: false);
        $response->assertDontSee('Live from the network', escape: false);
        $response->assertDontSee('FAQPage', escape: false);
    }

    public function test_href_re_local_preview_route_is_accessible(): void
    {
        $response = $this->get('/_preview/re');

        $response->assertStatus(200);
        $response->assertSee('href<span>.re</span>', escape: false);
        $response->assertSee('Official links', escape: false);
    }

    public function test_href_re_authenticated_landing_shows_dashboard_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://href.re/')
            ->assertStatus(200)
            ->assertSee('Go to Dashboard', escape: false)
            ->assertDontSee('dash.ternis.link/login', escape: false);
    }

    public function test_href_re_robots_and_sitemap(): void
    {
        $robots = $this->get('http://href.re/robots.txt')->assertStatus(200)->getContent();
        $this->assertStringContainsString('Allow: /$', $robots);
        $this->assertStringContainsString('Sitemap: http://href.re/sitemap.xml', $robots);

        $sitemap = $this->get('http://href.re/sitemap.xml')->assertStatus(200)->getContent();
        $this->assertStringContainsString('<loc>http://href.re/</loc>', $sitemap);
        $this->assertStringNotContainsString('ternis.link/pages/', $sitemap);

        $doc = simplexml_load_string($sitemap);
        $this->assertNotFalse($doc);
        $this->assertNotEmpty($doc->url);
    }

    public function test_href_re_unknown_slug_returns_business_not_found(): void
    {
        $response = $this->get('http://href.re/unknownbizlink');

        $response->assertStatus(404);
        $response->assertSee('Business Link Not Found', escape: false);
        $response->assertSee('unknownbizlink', escape: false);
        $response->assertSee('href.re', escape: false);
        $response->assertSee('Return to href.re', escape: false);
    }

    public function test_business_header_and_cards_wrap_on_mobile(): void
    {
        $response = $this->get('http://href.re/');

        $response->assertStatus(200);
        // Wrapping header row + responsive cards grid
        $response->assertSee('max-sm:flex-wrap', escape: false);
        $response->assertSee('grid-cols-1 gap-4 sm:grid-cols-3', escape: false);
    }
}
