<?php

namespace Tests\Feature;

use App\Models\Domain;
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

        // Key sections in minimalist grayscale theme
        $response->assertSee('Core principles', escape: false);
        $response->assertSee('Business only', escape: false);
        $response->assertSee('Trusted by default', escape: false);
        $response->assertSee('Measured', escape: false);
        $response->assertSee('Custom slugs', escape: false);
        $response->assertSee('API access', escape: false);
        $response->assertSee('Lifecycle governance', escape: false);

        // How it works
        $response->assertSee('How it works', escape: false);
        $response->assertSee('Authenticated provisioning', escape: false);
        $response->assertSee('Audited redirect setup', escape: false);

        // Telemetry
        $response->assertSee('Live from the network', escape: false);
        $response->assertSee('short links created', escape: false);
        $response->assertSee('redirects counted', escape: false);

        // FAQ & Structured data
        $response->assertSee('Frequently asked questions', escape: false);
        $response->assertSee('Why does Ternis maintain href.re separately', escape: false);
        $response->assertSee('application/ld+json', escape: false);
        $response->assertSee('FAQPage', escape: false);
    }

    public function test_href_re_local_preview_route_is_accessible(): void
    {
        $response = $this->get('/_preview/re');

        $response->assertStatus(200);
        $response->assertSee('href<span>.re</span>', escape: false);
        $response->assertSee('Official links', escape: false);
    }
}
