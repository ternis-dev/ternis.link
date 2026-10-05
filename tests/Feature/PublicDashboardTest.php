<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
    }

    private function makeLink(string $hostname, string $slug): Link
    {
        return Link::create([
            'slug' => $slug,
            'destination_url' => 'https://example.com/'.$slug,
            'domain_id' => Domain::where('hostname', $hostname)->firstOrFail()->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_same_host_login(): void
    {
        $this->get('http://my.href.nz/')
            ->assertRedirect('http://my.href.nz/login');

        $this->get('http://my.href.nz/links')
            ->assertRedirect('http://my.href.nz/login');
    }

    public function test_overview_shows_only_public_shortener_links(): void
    {
        $this->makeLink('href.nz', 'pub-overview-1');
        $this->makeLink('meinlink.at', 'pub-overview-2');
        $this->makeLink('href.yt', 'pub-overview-3');
        $this->makeLink('clicked.at', 'dash-only-1');
        $this->makeLink('ternis.link', 'dash-only-2');

        $this->actingAs($this->user)
            ->get('http://my.href.nz/')
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_links'] === 3)
            ->assertSee('pd-hero', escape: false)
            ->assertSee('pd-stat', escape: false);
    }

    public function test_links_table_partitions_public_and_personal(): void
    {
        $public = $this->makeLink('href.nz', 'pub-part-1');
        $personal = $this->makeLink('clicked.at', 'pers-part-1');

        // Public link: visible on my.href.nz, 404 on dash.
        $this->actingAs($this->user)
            ->get("http://my.href.nz/links/{$public->id}")
            ->assertOk();
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$public->id}")
            ->assertNotFound();

        // Dash-side link: visible on dash, 404 on my.href.nz.
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$personal->id}")
            ->assertOk();
        $this->actingAs($this->user)
            ->get("http://my.href.nz/links/{$personal->id}")
            ->assertNotFound();
    }

    public function test_public_create_and_links_pages_render(): void
    {
        $this->actingAs($this->user)
            ->get('http://my.href.nz/links')
            ->assertOk()
            ->assertSee('Your Links', escape: false);

        $this->actingAs($this->user)
            ->get('http://my.href.nz/links/create')
            ->assertOk()
            ->assertSee('Create Short Link', escape: false);

        $this->actingAs($this->user)
            ->get('http://my.href.nz/new')
            ->assertOk();
    }

    public function test_login_page_uses_public_theme_and_local_sso(): void
    {
        $this->get('http://my.href.nz/login')
            ->assertOk()
            ->assertSee('Log in to your links', escape: false)
            ->assertSee('/auth/redirect', escape: false);
    }

    public function test_dash_links_page_points_at_public_dashboard(): void
    {
        $this->makeLink('href.nz', 'pub-banner-1');

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links')
            ->assertOk()
            ->assertSee('my.href.nz', escape: false);

        $other = User::factory()->create();

        $this->actingAs($other)
            ->get('http://dash.ternis.link/links')
            ->assertOk()
            ->assertDontSee('my.href.nz', escape: false);
    }

    public function test_alias_host_redirects_to_canonical(): void
    {
        $this->get('http://my.href.yt/links')
            ->assertRedirect('http://my.href.nz/links');
    }

    public function test_public_export_contains_only_public_links(): void
    {
        $this->makeLink('href.nz', 'pub-exp-1');
        $this->makeLink('clicked.at', 'dash-exp-1');

        $content = $this->actingAs($this->user)
            ->get('http://my.href.nz/links/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('pub-exp-1', $content);
        $this->assertStringNotContainsString('dash-exp-1', $content);
    }

    public function test_qr_download_scoped_to_public_links(): void
    {
        $public = $this->makeLink('href.nz', 'pub-qr-1');
        $personal = $this->makeLink('clicked.at', 'pers-qr-1');

        $this->actingAs($this->user)
            ->get("http://my.href.nz/links/{$public->id}/qr")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($this->user)
            ->get("http://my.href.nz/links/{$personal->id}/qr")
            ->assertNotFound();
    }
}
