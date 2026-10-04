<?php

namespace Tests\Feature;

use App\Models\Domain;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YtLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_href_yt_host_resolves_as_public_type(): void
    {
        $domain = Domain::where('hostname', 'href.yt')->first();
        $this->assertNotNull($domain);
        $this->assertSame('public', $domain->type->value);
    }

    public function test_href_yt_landing_renders_the_link_desk_experience(): void
    {
        $response = $this->get('http://href.yt/');

        $response->assertStatus(200);

        // Branding & visual identity
        $response->assertSee('href<span class="font-normal opacity-45">.yt</span>', escape: false);
        $response->assertSee('live / link desk', escape: false);
        $response->assertSee('Make the', escape: false);
        $response->assertSee('Less link.', escape: false);
        $response->assertSee('No ad wall', escape: false);
        $response->assertSee('href.nz', escape: false);
        $response->assertSee('href.yt', escape: false);
        $response->assertSee('timestamp ready', escape: false);
        $response->assertSee('No strange detours', escape: false);

        // Assets
        $response->assertSee('yt-', escape: false);

        // FAQ & Structured data
        $response->assertSee('Notes from the desk', escape: false);
        $response->assertSee('application/ld+json', escape: false);
        $response->assertSee('FAQPage', escape: false);

        // Disclaimer
        $response->assertSee('not affiliated with YouTube, Google LLC, Alphabet Inc.', escape: false);
    }

    public function test_href_yt_custom_new_page_renders(): void
    {
        $response = $this->get('http://href.yt/new');

        $response->assertStatus(200);
        $response->assertSee('Accelerate a Video Link', escape: false);
        $response->assertSee('Studio Mode', escape: false);
        $response->assertSee('href<span class="text-red-500">.yt</span>', escape: false);
    }

    public function test_href_yt_custom_login_page_renders(): void
    {
        $response = $this->get('http://href.yt/login');

        $response->assertStatus(200);
        $response->assertSee('Creator Studio Login', escape: false);
        $response->assertSee('Sign in with Ternis Auth', escape: false);
        $response->assertSee('Features for verified accounts', escape: false);
        $response->assertSee('href<span class="text-red-500">.yt</span>', escape: false);
    }

    public function test_href_yt_unknown_slug_returns_custom_video_404(): void
    {
        $response = $this->get('http://href.yt/nonexistent-video');

        $response->assertStatus(404);
        $response->assertSee('Video link not found', escape: false);
        $response->assertSee('Signal Lost', escape: false);
        $response->assertSee('href.yt', escape: false);
        $response->assertSee('Back to href.yt', escape: false);
        $response->assertSee('Accelerate New Link', escape: false);
    }

    public function test_href_yt_unknown_path_returns_custom_error(): void
    {
        $response = $this->get('http://href.yt/pages/random-route-that-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('Video or link not found', escape: false);
        $response->assertSee('Signal Lost', escape: false);
        $response->assertSee('href.yt', escape: false);
        $response->assertSee('Back to href.yt', escape: false);
    }

    public function test_href_yt_local_previews(): void
    {
        $this->get('/_preview/yt')->assertStatus(200)->assertSee('Make the', escape: false);
        $this->get('/_preview/yt-new')->assertStatus(200)->assertSee('Accelerate a Video Link', escape: false);
        $this->get('/_preview/yt-login')->assertStatus(200)->assertSee('Creator Studio Login', escape: false);
        $this->get('/_preview/yt-error')->assertStatus(404)->assertSee('Video link not found', escape: false);
    }
}
