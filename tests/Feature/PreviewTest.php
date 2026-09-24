<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    use RefreshDatabase;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function test_slug_preview_shows_destination_without_tracking(): void
    {
        $link = Link::create([
            'slug' => 'preview12',
            'destination_url' => 'https://example.com/preview-target',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->get('http://href.nz/preview/preview12')
            ->assertStatus(200)
            ->assertSee('Link preview', escape: false)
            ->assertSee('https://example.com/preview-target', escape: false)
            ->assertSee('Visit link', escape: false)
            ->assertSee('No known risk patterns', escape: false);

        // Sandbox: no click counted, no rows created.
        $this->assertSame(0, $link->fresh()->click_count);
        $this->assertSame(1, Link::count());
    }

    public function test_unknown_slug_preview_uses_not_found_page(): void
    {
        $this->get('http://href.nz/preview/nosuchlink')
            ->assertStatus(404)
            ->assertSee('for the domain', escape: false)
            ->assertSee('href.nz', escape: false);
    }

    public function test_direct_url_preview_creates_no_tracking_row(): void
    {
        $this->get('http://href.nz/preview/https://example.com/some/long/page')
            ->assertStatus(200)
            ->assertSee('Link preview', escape: false)
            ->assertSee('https://example.com/some/long/page', escape: false)
            ->assertSee('example.com', escape: false);

        $this->assertSame(0, Link::count());
    }

    public function test_junk_destination_shows_warning(): void
    {
        $this->get('http://href.nz/preview/https://phpinfo.php')
            ->assertStatus(200)
            ->assertSee('automated-scan', escape: false)
            ->assertSee('Only continue if you trust it.', escape: false);

        $this->assertSame(0, Link::count());
    }

    public function test_garbage_input_404s(): void
    {
        $this->get('http://href.nz/preview/!!!')->assertStatus(404);
    }

    public function test_preview_rejected_off_hrefnz(): void
    {
        // Guests are login-redirected on the ternis host before the
        // handler runs; an authenticated user reaches it and gets a 404.
        $user = \App\Models\User::factory()->family()->create();
        $link = Link::create([
            'slug' => 'preview99',
            'destination_url' => 'https://example.com/x',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get("http://ternis.link/preview/{$link->slug}")
            ->assertStatus(404);
    }
}
