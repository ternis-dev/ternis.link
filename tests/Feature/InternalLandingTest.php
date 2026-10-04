<?php

namespace Tests\Feature;

use App\Models\Domain;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_int_ternis_link_host_resolves_and_has_domain_model(): void
    {
        $domain = Domain::where('hostname', 'int.ternis.link')->first();
        $this->assertNotNull($domain);
        $this->assertSame('ternis', $domain->type->value);
    }

    public function test_int_ternis_link_landing_page_renders_dedicated_content(): void
    {
        $response = $this->get('http://int.ternis.link/');

        $response->assertStatus(200);

        // Header & branding
        $response->assertSee('int<span class="text-neutral-400 dark:text-neutral-500">.ternis.link</span>', escape: false);
        $response->assertSee('Internal Gateway', escape: false);

        // Core message: internal application-links & imprint
        $response->assertSee('Internal application links', escape: false);
        $response->assertSee('centralized', escape: false);
        $response->assertSee('internal routing gateway', escape: false);
        $response->assertSee('Imprint Gateway', escape: false);
        $response->assertSee('/imprint', escape: false);
        $response->assertSee('/impressum', escape: false);

        // Feature cards
        $response->assertSee('Internal application links', escape: false);
        $response->assertSee('Imprint &amp; legal gateway', escape: false);
        $response->assertSee('System routing &amp; dispatch', escape: false);
        $response->assertSee('Privacy-first design', escape: false);

        // Connected ecosystem
        $response->assertSee('Connected Ecosystem', escape: false);
        $response->assertSee('href.re', escape: false);
        $response->assertSee('href.nz', escape: false);
        $response->assertSee('ternis.link', escape: false);

        // FAQ section & structured data
        $response->assertSee('Frequently asked questions', escape: false);
        $response->assertSee('What is int.ternis.link used for?', escape: false);
        $response->assertSee('application/ld+json', escape: false);
        $response->assertSee('FAQPage', escape: false);
    }

    public function test_preview_route_renders_internal_landing(): void
    {
        $response = $this->get('/_preview/int');

        $response->assertStatus(200);
        $response->assertSee('Internal application links', escape: false);
        $response->assertSee('Imprint Gateway', escape: false);
    }

    public function test_int_ternis_link_imprint_redirects(): void
    {
        $response = $this->get('http://int.ternis.link/imprint?domain=clicked.at&lang=de');
        $response->assertRedirect('https://ternis.dev/de/legal/imprint?domain=clicked.at');

        $responseEn = $this->get('http://int.ternis.link/imprint');
        $responseEn->assertRedirect('https://ternis.dev/en/legal/imprint?domain=ternis.link');
    }

    public function test_int_ternis_link_unknown_slug_returns_custom_404(): void
    {
        $response = $this->get('http://int.ternis.link/nonexistent-link');

        $response->assertStatus(404);
        $response->assertSee('Internal Link Not Found', escape: false);
        $response->assertSee('int.ternis.link', escape: false);
        $response->assertSee('nonexistent-link', escape: false);
        $response->assertSee('Return to int.ternis.link', escape: false);
        $response->assertSee('Imprint Gateway', escape: false);
    }

    public function test_int_ternis_link_unknown_path_returns_custom_404(): void
    {
        $response = $this->get('http://int.ternis.link/pages/unknown-page-that-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('Internal link not found.', escape: false);
        $response->assertSee('int.ternis.link', escape: false);
        $response->assertSee('Internal Gateway', escape: false);
        $response->assertSee('Imprint Gateway', escape: false);
    }
}
