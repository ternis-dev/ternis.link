<?php

namespace Tests\Feature;

use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_href_yt_landing_renders_creator_experience(): void
    {
        $response = $this->get('http://href.yt/');

        $response->assertStatus(200);

        // Branding & visual identity
        $response->assertSee('href<span>.yt</span>', escape: false);
        $response->assertSee('Short links for', escape: false);
        $response->assertSee('video people.', escape: false);
        $response->assertSee('Built for creators', escape: false);
        $response->assertSee('Instant, no sign-up', escape: false);

        // Simulator & Presets
        $response->assertSee('Presets:', escape: false);
        $response->assertSee('Video Description', escape: false);
        $response->assertSee('Creator Questions &amp; Answers', escape: false);

        // Disclaimer
        $response->assertSee('not affiliated with, endorsed by, authorized by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc.', escape: false);
    }

    public function test_href_yt_v1_endpoints_are_forbidden_and_return_404(): void
    {
        // Must NOT allow calling /v1/qr on href.yt
        $this->get('http://href.yt/v1/qr?url=https%3A%2F%2Fhref.yt%2FctO4IEV1&format=png')
            ->assertStatus(404);

        // Must NOT allow calling /v1/links/public on href.yt
        $this->postJson('http://href.yt/v1/links/public', [
            'destination_url' => 'https://example.com/yt-link',
        ])->assertStatus(404);
    }

    public function test_href_yt_inline_qr_generation_on_shorten(): void
    {
        $test = Livewire::test(ShortenForm::class)
            ->set('selectedDomain', 'href.yt')
            ->set('destination_url', 'https://youtube.com/watch?v=test1234')
            ->call('create');

        $test->assertHasNoErrors()
            ->assertSet('showQr', true)
            ->assertSet('shortUrl', fn ($val) => is_string($val) && str_starts_with($val, 'https://href.yt/'))
            ->assertSee('Inline Vector QR', escape: false)
            ->assertSee('data:image/svg+xml;utf8,', escape: false)
            ->assertSee('download="qr-', escape: false)
            ->assertDontSee('/v1/qr', escape: false);
    }

    public function test_href_yt_custom_new_page_renders(): void
    {
        $response = $this->get('http://href.yt/new');

        $response->assertStatus(200);
        $response->assertSee('Make something', escape: false);
        $response->assertSee('href<span>.yt</span>', escape: false);
        $response->assertSee('not affiliated with', escape: false);
    }

    public function test_href_yt_custom_login_page_renders(): void
    {
        $response = $this->get('http://href.yt/login');

        $response->assertStatus(200);
        $response->assertSee('Members', escape: false);
        $response->assertSee('log in', escape: false);
        $response->assertSee('Log in with Ternis Auth', escape: false);
        $response->assertSee('not affiliated with', escape: false);
    }

    public function test_href_yt_unknown_slug_returns_custom_404(): void
    {
        $response = $this->get('http://href.yt/nonexistent-video');

        $response->assertStatus(404);
        $response->assertSee('Video link not found.', escape: false);
        $response->assertSee('href.yt', escape: false);
        $response->assertSee('Back to href.yt', escape: false);
        $response->assertSee('not affiliated with', escape: false);
    }

    public function test_href_yt_local_previews(): void
    {
        $this->get('/_preview/yt')->assertStatus(200)->assertSee('Short links for', escape: false);
        $this->get('/_preview/yt-new')->assertStatus(200)->assertSee('Make something', escape: false);
        $this->get('/_preview/yt-login')->assertStatus(200)->assertSee('Log in with Ternis Auth', escape: false);
        $this->get('/_preview/yt-error')->assertStatus(404)->assertSee('Video link not found.', escape: false);
    }
}
