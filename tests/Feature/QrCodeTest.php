<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Support\LinkQrCode;
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
}
