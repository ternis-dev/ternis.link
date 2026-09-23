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
        $response = $this->withHeaders(['Host' => 'links.t-api.de'])
            ->postJson('/v1/links/public', [
                'destination_url' => 'https://example.com/guest-link',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['slug', 'destination_url', 'short_url']);
        $this->assertStringStartsWith('https://href.nz/', $response->json('short_url'));
        $this->assertGreaterThanOrEqual(8, strlen($response->json('slug')));

        $this->assertDatabaseHas('links', [
            'slug' => $response->json('slug'),
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
        ]);

        $link = Link::where('slug', $response->json('slug'))->firstOrFail();
        $this->assertNotNull($link->creator_ip_hash);
        $this->assertEquals(hash('sha256', '127.0.0.1'), $link->creator_ip_hash);
    }

    public function test_guest_can_choose_custom_slug_at_guest_minimum(): void
    {
        $response = $this->withHeaders(['Host' => 'href.nz'])
            ->postJson('/v1/links/public', [
                'destination_url' => 'https://example.com/custom',
                'slug' => 'guest-slug-1',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['slug' => 'guest-slug-1']);
    }

    public function test_guest_custom_slug_below_minimum_is_rejected(): void
    {
        $response = $this->withHeaders(['Host' => 'href.nz'])
            ->postJson('/v1/links/public', [
                'destination_url' => 'https://example.com/short',
                'slug' => 'abc',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseMissing('links', ['slug' => 'abc']);
    }

    public function test_guest_cannot_create_on_non_public_domain(): void
    {
        $response = $this->withHeaders(['Host' => 'links.t-api.de'])
            ->postJson('/v1/links/public', [
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
            $this->withHeaders(['Host' => 'links.t-api.de'])
                ->postJson('/v1/links/public', [
                    'destination_url' => 'https://example.com/throttle-'.$i,
                ])
                ->assertStatus(201);
        }

        $this->withHeaders(['Host' => 'links.t-api.de'])
            ->postJson('/v1/links/public', [
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
    }

    public function test_web_form_rejects_short_slug_for_guests(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/web-short')
            ->set('slug', 'abc')
            ->call('create')
            ->assertHasErrors('slug');

        $this->assertDatabaseMissing('links', ['destination_url' => 'https://example.com/web-short']);
    }

    public function test_landing_page_shows_guest_form_on_public_domain(): void
    {
        $response = $this->withHeaders(['Host' => 'href.nz'])->get('/');

        $response->assertStatus(200);
        $response->assertSee('Shorten a link', escape: false);
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

        $response = $this->withHeaders(['Host' => 'href.nz'])->get('/'.$slug);

        $response->assertRedirect('https://example.com/target');
    }
}
