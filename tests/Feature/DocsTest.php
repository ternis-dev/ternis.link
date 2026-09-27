<?php

namespace Tests\Feature;

use App\Http\Controllers\DocsController;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->user = User::factory()->create();
    }

    public function test_docs_index_lists_pages(): void
    {
        $this->get('http://docs.ternis.link/')
            ->assertOk()
            ->assertSee('How this network fits together', escape: false)
            ->assertSee('/architecture', escape: false)
            ->assertSee('/api-v1-openapi.yaml', escape: false);
    }

    public function test_docs_show_renders_markdown_as_html(): void
    {
        $this->get('http://docs.ternis.link/architecture')
            ->assertOk()
            ->assertSee('Architecture', escape: false)
            ->assertSee('text/markdown', escape: false); // alternate link to the .md twin
    }

    public function test_docs_show_has_markdown_twin(): void
    {
        $response = $this->get('http://docs.ternis.link/architecture.md');

        $response->assertOk();
        $this->assertStringStartsWith('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('# Architecture', $response->getContent());
    }

    public function test_docs_unknown_slug_404s(): void
    {
        $this->get('http://docs.ternis.link/no-such-page')->assertNotFound();
    }

    public function test_docs_openapi_yaml_is_served_raw(): void
    {
        $response = $this->get('http://docs.ternis.link/api-v1-openapi.yaml');

        $response->assertOk();
        $this->assertStringContainsString('openapi:', $response->getContent());
    }

    public function test_docs_routes_404_on_other_hosts(): void
    {
        $this->get('http://href.nz/architecture')->assertNotFound();
        $this->get('http://ternis.link/architecture')->assertNotFound();
        $this->get('http://dash.ternis.link/')->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_docs_host_does_not_serve_short_links(): void
    {
        $this->get('http://docs.ternis.link/abc123')->assertNotFound();
    }

    public function test_docs_links_slug_renders_the_links_doc(): void
    {
        // Regression: dashboard /links (registered first) used to
        // swallow this and bounce guests to a /login that 404s.
        $this->get('http://docs.ternis.link/links')
            ->assertOk()
            ->assertSee('Links', escape: false);
    }

    public function test_every_docs_slug_resolves_on_the_docs_host(): void
    {
        // Collision guard: if a future docs slug ever matches an
        // earlier exact route again, this fails instead of shipping a
        // login-bounce or a 404.
        foreach (array_keys(DocsController::PAGES) as $slug) {
            $this->get("http://docs.ternis.link/{$slug}")->assertOk();
        }
    }

    public function test_docs_slugs_do_not_leak_onto_the_dashboard_host(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/architecture')
            ->assertNotFound();
    }

    public function test_localhost_links_serves_the_dashboard(): void
    {
        // Pinned dashboard routes never match localhost, so the docs
        // route delegates back (same pattern as /new).
        $this->get('http://localhost/links')
            ->assertRedirect('http://localhost/login');

        $this->actingAs($this->user)
            ->get('http://localhost/links')
            ->assertOk()
            ->assertSee('New Short Link', escape: false);
    }
}
