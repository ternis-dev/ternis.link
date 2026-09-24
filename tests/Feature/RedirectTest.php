<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    public function test_direct_url_redirect(): void
    {
        $response = $this->get('http://href.nz/url/https://example.com/some/path');

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/some/path');

        $click = Click::first();
        $this->assertNotNull($click);
        $this->assertTrue((bool) $click->is_direct_url);
    }

    public function test_go_url_redirect(): void
    {
        $response = $this->get('http://href.nz/go/https://laravel.com');

        $response->assertStatus(302);
        $response->assertRedirect('https://laravel.com');
    }

    public function test_bare_path_url_detected_and_redirected(): void
    {
        $response = $this->get('http://href.nz/google.com');

        $response->assertStatus(302);
        $response->assertRedirect('https://google.com');
    }

    public function test_slug_redirects_to_destination_and_tracks_click(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->first();
        $link = Link::create([
            'slug' => 'mylink123',
            'destination_url' => 'https://ternis.dev',
            'domain_id' => $domain->id,
            'is_active' => true,
            'click_count' => 0,
        ]);

        $response = $this->get('http://href.nz/mylink123');

        $response->assertStatus(302);
        $response->assertRedirect('https://ternis.dev');

        $this->assertEquals(1, $link->fresh()->click_count);

        $click = Click::where('link_id', $link->id)->first();
        $this->assertNotNull($click);
        $this->assertFalse((bool) $click->is_direct_url);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $response = $this->get('http://href.nz/notfoundslug');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('notfoundslug', escape: false);
        $response->assertSee('for the domain', escape: false);
        $response->assertSee('href.nz', escape: false);
    }
}
