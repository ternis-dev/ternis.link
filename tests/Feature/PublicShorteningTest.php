<?php

namespace Tests\Feature;

use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PublicShorteningTest extends TestCase
{
    use RefreshDatabase;

    private Domain $publicDomain;

    private Domain $ternisDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->ternisDomain = Domain::where('hostname', 'ternis.link')->firstOrFail();
    }

    public function test_guest_can_create_link_via_public_api_without_token(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/guest-link',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['slug', 'destination_url', 'short_url']);
        $this->assertStringStartsWith('https://href.nz/', $response->json('short_url'));
        $this->assertEquals(LinkService::GUEST_SLUG_LENGTH, strlen($response->json('slug')));

        $this->assertDatabaseHas('links', [
            'slug' => $response->json('slug'),
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
        ]);

        $link = Link::where('slug', $response->json('slug'))->firstOrFail();
        $this->assertNotNull($link->creator_ip_hash);
        $this->assertEquals(hash('sha256', '127.0.0.1'), $link->creator_ip_hash);
    }

    public function test_guest_custom_slug_is_rejected(): void
    {
        $response = $this->postJson('http://href.nz/v1/links/public', [
            'destination_url' => 'https://example.com/custom',
            'slug' => 'guest-slug-1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseMissing('links', ['slug' => 'guest-slug-1']);
    }

    public function test_guest_custom_slug_below_minimum_is_rejected(): void
    {
        $response = $this->postJson('http://href.nz/v1/links/public', [
            'destination_url' => 'https://example.com/short',
            'slug' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseMissing('links', ['slug' => 'abc']);
    }

    public function test_guest_custom_slug_via_service_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(LinkService::class)->create(
            destinationUrl: 'https://example.com/service-custom',
            domain: $this->publicDomain,
            user: null,
            customSlug: 'custom-slug-1',
            creatorIpHash: hash('sha256', '127.0.0.1'),
        );
    }

    public function test_guest_cannot_create_on_non_public_domain(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/nope',
            'domain_id' => $this->ternisDomain->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('domain_id');
        $this->assertDatabaseMissing('links', ['destination_url' => 'https://example.com/nope']);
    }

    public function test_public_api_is_throttled_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('http://links.t-api.de/v1/links/public', [
                'destination_url' => 'https://example.com/throttle-'.$i,
            ])
                ->assertStatus(201);
        }

        $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/throttle-over',
        ])
            ->assertStatus(429);
    }

    public function test_anonymous_daily_quota_is_enforced(): void
    {
        $service = app(LinkService::class);
        $ipHash = hash('sha256', '203.0.113.7');

        for ($i = 0; $i < LinkService::ANONYMOUS_DAILY_LIMIT; $i++) {
            $service->create(
                destinationUrl: 'https://example.com/quota-'.$i,
                domain: $this->publicDomain,
                creatorIpHash: $ipHash,
            );
        }

        $this->expectException(ValidationException::class);

        $service->create(
            destinationUrl: 'https://example.com/quota-over',
            domain: $this->publicDomain,
            creatorIpHash: $ipHash,
        );
    }

    public function test_guest_can_create_link_via_web_form(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/web-guest')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($value) => is_string($value) && str_starts_with($value, 'https://href.nz/'));

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/web-guest',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
        ]);

        $link = Link::where('destination_url', 'https://example.com/web-guest')->firstOrFail();
        $this->assertEquals(LinkService::GUEST_SLUG_LENGTH, strlen($link->slug));
    }

    public function test_landing_page_shows_guest_form_on_public_domain(): void
    {
        $response = $this->get('http://href.nz/');

        $response->assertStatus(200);
        $response->assertSee('Shorten a link', escape: false);
    }

    public function test_form_tracks_live_url_state(): void
    {
        Livewire::test(ShortenForm::class)
            ->assertSet('urlState', 'idle')
            ->set('destination_url', 'not a url')
            ->assertSet('urlState', 'invalid')
            ->set('destination_url', 'https://example.com/looks-good')
            ->assertSet('urlState', 'valid')
            ->set('destination_url', '')
            ->assertSet('urlState', 'idle');
    }

    public function test_form_shows_remaining_guest_quota(): void
    {
        $test = Livewire::test(ShortenForm::class);
        $this->assertSame(LinkService::ANONYMOUS_DAILY_LIMIT, $test->get('quotaLeft'));
        $test->assertSee(LinkService::ANONYMOUS_DAILY_LIMIT.' of '.LinkService::ANONYMOUS_DAILY_LIMIT.' free links left today');

        Link::create([
            'slug' => 'quota-'.Str::random(6),
            'destination_url' => 'https://example.com/quota',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);

        $test = Livewire::test(ShortenForm::class);
        $this->assertSame(LinkService::ANONYMOUS_DAILY_LIMIT - 1, $test->get('quotaLeft'));
    }

    public function test_successful_create_resets_url_state(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/state-reset')
            ->assertSet('urlState', 'valid')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('urlState', 'idle');
    }

    public function test_preview_shows_for_valid_input(): void
    {
        $url = 'https://example.com/preview-me';

        Livewire::test(ShortenForm::class)
            ->set('destination_url', $url)
            ->assertSee('will shorten', escape: false)
            ->assertSee('href.nz/○○○○○○○○', escape: false)
            ->assertSee(strlen($url).' / 2048');
    }

    public function test_quota_error_flags_oops_card_with_login_nudge(): void
    {
        for ($i = 0; $i < LinkService::ANONYMOUS_DAILY_LIMIT; $i++) {
            Link::create([
                'slug' => 'quota-'.Str::random(8),
                'destination_url' => 'https://example.com/quota-'.$i,
                'domain_id' => $this->publicDomain->id,
                'user_id' => null,
                'creator_ip_hash' => hash('sha256', '127.0.0.1'),
                'is_active' => true,
            ]);
        }

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/over-quota')
            ->call('create')
            ->assertSet('quotaExceeded', true)
            ->assertSee("oops — that didn't stick!", escape: false)
            ->assertSee('Members get a bigger daily pile');
    }

    public function test_invalid_submit_marks_field_with_error_state(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'nope')
            ->call('create')
            ->assertHasErrors('destination_url')
            ->assertSee('is-error', escape: false)
            ->assertSee('aria-invalid', escape: false);
    }

    public function test_business_and_public_landings_differ(): void
    {
        $public = $this->get('http://href.nz/');
        $public->assertStatus(200);
        $public->assertSee('href<span>.nz</span>', escape: false);

        $business = $this->get('http://href.re/');
        $business->assertStatus(200);
        $business->assertSee('href<span>.re</span>', escape: false);

        // Business landing offers no guest form.
        $business->assertDontSee('Shorten a link', escape: false);
    }

    public function test_created_guest_link_redirects(): void
    {
        $slug = 'guest-'.Str::random(8);
        Link::create([
            'slug' => $slug,
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);

        $response = $this->get('http://href.nz/'.$slug);

        $response->assertRedirect('https://example.com/target');
    }
}
