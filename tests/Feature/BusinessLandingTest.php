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

    public function test_href_re_landing_renders_enhanced_business_presentation(): void
    {
        $response = $this->get('http://href.re/');

        $response->assertStatus(200);

        // Core identity
        $response->assertSee('href<span>.re</span>', escape: false);
        $response->assertSee('Official links', escape: false);
        $response->assertSee('built for business', escape: false);
        $response->assertSee('Official · Business Gateway', escape: false);

        // Does NOT have guest creation form
        $response->assertDontSee('Shorten a link', escape: false);

        // Live telemetry
        $response->assertSee('Edge Network Status: Operational', escape: false);
        $response->assertSee('Short links provisioned', escape: false);
        $response->assertSee('Redirects safely routed', escape: false);
        $response->assertSee('Ad trackers or spyware pixels', escape: false);

        // Interactive Inspector
        $response->assertSee('Inspect &amp; verify an href.re link', escape: false);
        $response->assertSee('Enter href.re link or slug code', escape: false);
        $response->assertSee('Strict TLS 1.3 Encryption', escape: false);
        $response->assertSee('Zero Interstitial Cloaking', escape: false);

        // Enterprise Comparison Matrix
        $response->assertSee('Why href.re instead of generic shorteners?', escape: false);
        $response->assertSee('Strategic Differentiation', escape: false);
        $response->assertSee('Generic Shorteners (bit.ly, tinyurl)', escape: false);

        // Core Capabilities
        $response->assertSee('Cryptographically Audited', escape: false);
        $response->assertSee('Privacy-First Telemetry', escape: false);
        $response->assertSee('Sub-Millisecond Edge Resolution', escape: false);
        $response->assertSee('Semantic Corporate Slugs', escape: false);

        // Enterprise Use Cases
        $response->assertSee('Financial Invoices &amp; Billing', escape: false);
        $response->assertSee('Transactional SMS &amp; Messaging', escape: false);
        $response->assertSee('Legal Documents &amp; Contracts', escape: false);

        // Architecture Pipeline
        $response->assertSee('Zero-Trust Redirection Pipeline', escape: false);

        // FAQ & Structured Data
        $response->assertSee('Frequently asked questions', escape: false);
        $response->assertSee('Why does Ternis maintain href.re separately', escape: false);
        $response->assertSee('application/ld+json', escape: false);
        $response->assertSee('FAQPage', escape: false);
        $response->assertSee('Service', escape: false);
        $response->assertSee('href.re Business Link Gateway', escape: false);

        // SEO tags
        $response->assertSee('<meta name="robots" content="index, follow', escape: false);
        $response->assertSee('<link rel="canonical" href="https://href.re/">', escape: false);
    }

    public function test_href_re_local_preview_route_is_accessible(): void
    {
        $response = $this->get('/_preview/re');

        $response->assertStatus(200);
        $response->assertSee('href<span>.re</span>', escape: false);
        $response->assertSee('built for business', escape: false);
    }
}
