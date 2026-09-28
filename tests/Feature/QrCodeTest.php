<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Support\LinkQrCode;
use App\Support\NetworkStats;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private Link $link;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->link = Link::create([
            'slug' => 'qrtest1',
            'destination_url' => 'https://example.com/qr',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_short_url_is_canonical_https(): void
    {
        $this->assertSame('https://href.nz/qrtest1', LinkQrCode::shortUrl($this->link));
    }

    public function test_detail_page_shows_qr_code(): void
    {
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$this->link->id}")
            ->assertStatus(200)
            ->assertSee('QR Code', escape: false)
            ->assertSee('data:image/svg+xml', escape: false)
            ->assertSee(route('dashboard.links.qr', $this->link->id), escape: false);
    }

    public function test_png_download_returns_image(): void
    {
        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$this->link->id}/qr");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_stranger_cannot_download_foreign_qr(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/links/{$this->link->id}/qr")
            ->assertStatus(404);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get("http://dash.ternis.link/links/{$this->link->id}/qr")
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_public_host_can_generate_qr_for_a_free_link(): void
    {
        $this->get('http://href.nz/v1/qr?url='.urlencode('https://href.nz/qrtest1').'&format=png')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_pretty_qr_defaults_to_png(): void
    {
        $this->get('http://href.nz/qr/https://example.com/some/path')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_pretty_qr_accepts_bare_hostnames_and_mime(): void
    {
        $this->get('http://href.nz/qr/example.com/svg')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->get('http://href.nz/qr/example.com/png')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_pretty_qr_treats_unknown_mime_as_part_of_the_url(): void
    {
        // /qr/{url} is greedy: an unsupported trailing segment just
        // gets encoded (PNG default) instead of 404ing.
        $this->get('http://href.nz/qr/example.com/jpg')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_suffixed_qr_serves_short_link_slugs(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->first();

        Link::create([
            'slug' => 'qrslug01',
            'destination_url' => 'https://example.com/qr-target',
            'domain_id' => $domain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->get('http://href.nz/qrslug01.png')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');

        $this->get('http://href.nz/qrslug01.svg')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->get('http://href.nz/qrslug01.jpg')->assertNotFound();

        $this->get('http://href.nz/nosuchslug.png')->assertNotFound();
    }

    public function test_suffixed_qr_serves_direct_urls(): void
    {
        $this->get('http://href.nz/example.com.png')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_pretty_qr_is_public_host_only(): void
    {
        $this->get('http://href.re/qr/example.com')->assertNotFound();
        $this->get('http://href.re/qrslug01.png')->assertNotFound();
    }

    public function test_slug_qr_serves_short_link_code(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->first();

        Link::create([
            'slug' => 'qrslug02',
            'destination_url' => 'https://example.com/qr-target',
            'domain_id' => $domain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->get('http://href.nz/qrslug02/qr')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');

        $this->get('http://href.nz/qrslug02/qr.svg')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->get('http://href.nz/qrslug02/qr.jpg')->assertNotFound();
        $this->get('http://href.nz/nosuchslug/qr')->assertNotFound();

        // Reserved API paths are never mistaken for slugs.
        $this->get('http://href.nz/v1/qr.svg')->assertNotFound();
    }

    public function test_qr_generations_are_tracked_with_link_reference(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->first();

        $link = Link::create([
            'slug' => 'qrtrack01',
            'destination_url' => 'https://example.com/qr-tracked',
            'domain_id' => $domain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->get('http://href.nz/v1/qr?url='.urlencode('https://example.com/loose'))->assertStatus(200);
        $this->get('http://href.nz/qrtrack01/qr')->assertStatus(200);

        $this->assertDatabaseHas('qr_generations', [
            'link_id' => null,
            'format' => 'svg',
        ]);
        $this->assertDatabaseHas('qr_generations', [
            'link_id' => $link->id,
            'format' => 'png',
        ]);

        $stats = NetworkStats::overview();
        $this->assertSame(2, $stats['qr_codes']);
        $this->assertSame(2, $stats['qr_codes_today']);
        $this->assertContains(2, NetworkStats::qrByDay(30)['values']);
    }

    public function test_repeated_qr_requests_stay_healthy_on_warm_cache(): void
    {
        // Regression: the version middleware used to cache an Eloquent
        // model, which unserializes as __PHP_Incomplete_Class (cache
        // serializable_classes=false), so every second request 500d.
        // The database store round-trips through real serialization,
        // unlike the array store — that's what makes this catch it.
        config()->set('cache.default', 'database');

        $url = 'http://links.t-api.de/v1/qr?url='.urlencode('https://example.com');

        $this->get($url)->assertStatus(200)->assertHeader('Content-Type', 'image/svg+xml');
        $this->get($url)->assertStatus(200)->assertHeader('Content-Type', 'image/svg+xml');
        $this->get($url.'&format=png')->assertStatus(200)->assertHeader('Content-Type', 'image/png');
    }
}
