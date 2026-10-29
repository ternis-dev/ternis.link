<?php

namespace Tests\Feature;

use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_scan_ui_renders_on_allowed_domains(): void
    {
        // 1. href.nz (English)
        $this->get('http://href.nz/scan')
            ->assertOk()
            ->assertSee('Link Threat', escape: false)
            ->assertSee('Inspector', escape: false)
            ->assertSee('Inspect URL', escape: false);

        // 2. meinlink.at (German)
        $this->get('http://meinlink.at/scan')
            ->assertOk()
            ->assertSee('Link-Sicherheit', escape: false)
            ->assertSee('in Echtzeit prüfen', escape: false)
            ->assertSee('URL prüfen', escape: false);

        // 3. ternis.link (Platform)
        $this->get('http://ternis.link/scan')
            ->assertOk()
            ->assertSee('TypeSafe AI SystemOne', escape: false)
            ->assertSee('Inspect URL', escape: false);
    }

    public function test_scan_ui_404s_on_excluded_domains(): void
    {
        // href.re (business only)
        $this->get('http://href.re/scan')->assertNotFound();

        // href.yt (video shortener)
        $this->get('http://href.yt/scan')->assertNotFound();

        // clicked.at (newsletter tracking)
        $this->get('http://clicked.at/scan')->assertNotFound();

        // int.ternis.link (internal gateway)
        $this->get('http://int.ternis.link/scan')->assertNotFound();
    }

    public function test_submitting_url_inspects_and_displays_results(): void
    {
        $response = $this->get('http://href.nz/scan?url='.urlencode('https://example.com/login'));

        $response->assertOk()
            ->assertSee('example.com', escape: false)
            ->assertSee('Threat Probability Score', escape: false)
            ->assertSee('Scanned Destination', escape: false);

        // Invalid URL displays helpful validation alert
        $invalidResponse = $this->get('http://href.nz/scan?url=not-a-valid-url');
        $invalidResponse->assertOk()
            ->assertSee('Invalid or Blocked URL', escape: false);
    }

    public function test_scan_ui_supports_post_submission(): void
    {
        $response = $this->post('http://meinlink.at/scan', [
            'url' => 'https://example.com/test',
        ]);

        $response->assertOk()
            ->assertSee('example.com', escape: false)
            ->assertSee('Bedrohungs-Score', escape: false);
    }

    public function test_scan_ui_returns_json_when_requested(): void
    {
        $response = $this->getJson('http://href.nz/scan?url='.urlencode('https://example.com/test'));

        $response->assertOk()
            ->assertJsonPath('data.host', 'example.com')
            ->assertJsonPath('data.is_valid', true);
    }
}
