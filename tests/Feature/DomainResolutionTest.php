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
