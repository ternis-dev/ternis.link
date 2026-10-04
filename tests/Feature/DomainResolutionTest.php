<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Models\Domain;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    public function test_it_resolves_public_domain_landing_page(): void
    {
        $response = $this->get('http://href.nz/');
        $response->assertStatus(200);
        $response->assertSee('href');
    }

    public function test_it_resolves_qr_host_without_relying_on_domain_map(): void
    {
        $domainMap = config('domains.map');
        unset($domainMap['qr.href.nz']);
        config(['domains.map' => $domainMap]);

        $response = $this->get('http://qr.href.nz/');

        $response->assertOk();
        $response->assertSee('qr.href.nz');
    }

    /**
     * Production failure mode: a deployment whose cached config
     * map predates the QR host entry AND whose domains table was
     * never seeded with it. The middleware must still resolve the
     * host via the qr_host config (with its built-in default).
     */
    public function test_it_resolves_qr_host_without_domain_map_or_db_row(): void
    {
        $domainMap = config('domains.map');
        unset($domainMap['qr.href.nz']);
        config(['domains.map' => $domainMap]);

        Domain::where('hostname', 'qr.href.nz')->delete();

        $response = $this->get('http://qr.href.nz/');

        $response->assertOk();
        $response->assertSee('qr.href.nz');

        // The dedicated QR tool routes must survive the same
        // production conditions (stale map + missing DB row).
        $qrResponse = $this->get('http://qr.href.nz/url/https://example.com');
        $qrResponse->assertOk();
        $qrResponse->assertHeader('content-type', 'image/svg+xml');
    }

    public function test_it_resolves_business_domain_landing_page(): void
    {
        $response = $this->get('http://href.re/');
        $response->assertStatus(200);
        $response->assertSee('href');
    }

    public function test_it_redirects_to_login_on_dashboard_domain_when_unauthenticated(): void
    {
        $response = $this->get('http://dash.ternis.link/');
        $response->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_it_redirects_to_latest_version_on_api_domain(): void
    {
        $response = $this->get('http://links.t-api.de/');
        $response->assertStatus(302);
        $response->assertRedirect('/v1/');
    }

    public function test_it_redirects_api_ternis_link_to_links_t_api_de(): void
    {
        $response = $this->get('http://api.ternis.link/v1/links');
        $response->assertStatus(301);
        $response->assertRedirect('https://links.t-api.de/v1/links');
    }

    public function test_it_redirects_www_to_apex_for_canonical_domains(): void
    {
        foreach (['ternis.link', 'meinlink.at', 'href.nz', 'clicked.at', 'href.re'] as $apex) {
            $response = $this->get("http://www.{$apex}/pages/stats?src=www");

            $response->assertStatus(301);
            $response->assertRedirect("http://{$apex}/pages/stats?src=www");
        }
    }

    public function test_it_does_not_redirect_www_prefixed_subdomains(): void
    {
        $response = $this->get('http://www.dash.ternis.link/');
        $response->assertStatus(404);
    }

    public function test_it_resolves_registered_wildcard_subdomain(): void
    {
        Domain::create([
            'hostname' => 'partner.href.nz',
            'type' => DomainType::Public,
            'is_active' => true,
        ]);

        $response = $this->get('http://partner.href.nz/');
        $response->assertStatus(200);
    }

    public function test_it_aborts_404_for_unregistered_wildcard_subdomain(): void
    {
        $response = $this->get('http://nonexistent.href.nz/');
        $response->assertStatus(404);
    }

    public function test_it_aborts_404_for_completely_unknown_domain(): void
    {
        $response = $this->get('http://completely-unknown-domain.com/');
        $response->assertStatus(404);
    }
}
