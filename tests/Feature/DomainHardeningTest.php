<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DomainHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $rawApiKey;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Hardening Key',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_authenticated_api_404s_on_non_api_host(): void
    {
        // NOTE: hosts must go in the URL — withHeaders(['Host' => …]) is
        // ignored for relative URIs (Laravel prepends APP_URL instead).
        $this->getJson('http://href.nz/v1/links', $this->authHeaders())
            ->assertStatus(404);

        $this->postJson('http://href.nz/v1/links', [
            'destination_url' => 'https://example.com/wrong-host',
            'domain_id' => $this->publicDomain->id,
        ], $this->authHeaders())
            ->assertStatus(404);

        $this->getJson('http://dash.ternis.link/v1/domains', $this->authHeaders())
            ->assertStatus(404);
    }

    public function test_authenticated_api_still_works_on_api_host(): void
    {
        $this->getJson('http://links.t-api.de/v1/links', $this->authHeaders())
            ->assertStatus(200);
    }

    public function test_public_api_allows_api_and_public_hosts(): void
    {
        $this->postJson('http://links.t-api.de/v1/links/public', ['destination_url' => 'https://example.com/hardening-api'])
            ->assertStatus(201);

        $this->postJson('http://href.nz/v1/links/public', ['destination_url' => 'https://example.com/hardening-public'])
            ->assertStatus(201);
    }

    public function test_public_api_404s_on_dashboard_host(): void
    {
        $this->postJson('http://dash.ternis.link/v1/links/public', ['destination_url' => 'https://example.com/hardening-dash'])
            ->assertStatus(404);
    }

    public function test_dashboard_404s_on_public_host(): void
    {
        $this->actingAs($this->user)->get('http://href.nz/dashboard')->assertStatus(404);
        $this->actingAs($this->user)->get('http://href.nz/dashboard/links')->assertStatus(404);
    }

    public function test_login_and_auth_404_on_public_host(): void
    {
        $this->get('http://href.nz/login')->assertStatus(404);
        $this->get('http://href.nz/auth/redirect')->assertStatus(404);
        $this->get('http://dash.ternis.link/login')->assertStatus(200);
    }

    public function test_redirects_404_on_api_and_dashboard_hosts(): void
    {
        $link = Link::create([
            'slug' => 'harden123',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->publicDomain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->get('http://links.t-api.de/'.$link->slug)->assertStatus(404);
        $this->get('http://dash.ternis.link/'.$link->slug)->assertStatus(404);
        $this->get('http://links.t-api.de/url/https://example.com/x')->assertStatus(404);
        $this->get('http://href.nz/'.$link->slug)->assertRedirect('https://example.com/target');
    }

    public function test_landing_still_branches_by_host(): void
    {
        $this->get('http://href.nz/')->assertStatus(200);
        $this->get('http://links.t-api.de/')->assertStatus(302)->assertRedirect('/v1/');
        $this->get('http://dash.ternis.link/')->assertRedirect(route('login'));
        $this->get('http://10.0.0.5/healthz')->assertStatus(200);
    }

    public function test_openapi_doc_covers_host_pinned_routes(): void
    {
        $path = base_path('docs/api-v1-openapi.yaml');

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('openapi: 3.1.0', $contents);
        $this->assertStringContainsString('links.t-api.de', $contents);
        $this->assertStringContainsString('/links/public', $contents);
        $this->assertStringContainsString('/links/{link}', $contents);
        $this->assertStringContainsString('/domains', $contents);
        $this->assertStringContainsString('bearerAuth', $contents);
    }
}
