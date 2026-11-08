<?php

namespace Tests\Feature;

use App\Enums\ApiVersionStatus;
use App\Models\ApiKey;
use App\Models\ApiVersion;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiV3Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Domain $hrefDomain;
    private Domain $meinlinkDomain;
    private ApiKey $apiKey;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        ApiVersion::updateOrCreate(
            ['version' => 3],
            ['status' => ApiVersionStatus::Active]
        );

        $this->user = User::factory()->create();
        $this->hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->meinlinkDomain = Domain::where('hostname', 'meinlink.at')->firstOrFail();

        $raw = 'tl_'.Str::random(48);
        $this->apiKey = ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => ApiKey::hashToken($raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 3,
            'name' => 'API v3 Suite Key',
            'preferred_domain_id' => $this->hrefDomain->id,
        ]);

        $this->headers = [
            'Authorization' => "Bearer {$raw}",
            'Accept' => 'application/json',
        ];
    }

    public function test_v3_create_link_with_intuitive_aliases(): void
    {
        $response = $this->postJson('http://links.t-api.de/v3/links', [
            'url' => 'https://example.com/v3-intuitive',
            'domain' => 'meinlink.at',
            'slug' => 'v3testslug',
            'expires_in' => '7d',
            'tags' => 'developer, api-v3',
            'utm' => [
                'source' => 'twitter',
                'medium' => 'social',
                'campaign' => 'api_v3_launch',
            ],
            'description' => 'Created via intuitive API v3',
        ], $this->headers);

        $response->assertCreated();
        $response->assertJsonPath('data.slug', 'v3testslug');
        $response->assertJsonPath('data.short_url', 'https://meinlink.at/v3testslug');
        $response->assertJsonPath('data.destination_url', 'https://example.com/v3-intuitive');
        $response->assertJsonPath('data.domain.hostname', 'meinlink.at');
        $response->assertJsonPath('data.tags', ['developer', 'api-v3']);
        $response->assertJsonPath('data.utm.source', 'twitter');
        $response->assertJsonPath('data.utm.medium', 'social');
        $response->assertJsonPath('data.utm.campaign', 'api_v3_launch');
        $this->assertNotNull($response->json('data.expires_at'));
        $this->assertStringContainsString('/v3/links/', $response->json('data.qr_code_url'));
        $this->assertStringContainsString('/v3/links/', $response->json('data.analytics_url'));

        $link = Link::where('slug', 'v3testslug')->firstOrFail();
        $this->assertSame($this->meinlinkDomain->id, $link->domain_id);
    }

    public function test_v3_list_links_with_filters_and_pagination(): void
    {
        // Create 2 links
        $link1 = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->hrefDomain->id,
            'slug' => 'apple-search',
            'destination_url' => 'https://apple.com',
            'tags' => ['tech', 'gadgets'],
            'is_active' => true,
        ]);

        $link2 = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->meinlinkDomain->id,
            'slug' => 'banana-search',
            'destination_url' => 'https://banana.org',
            'tags' => ['food'],
            'is_active' => true,
        ]);

        // Search q=apple
        $searchRes = $this->getJson('http://links.t-api.de/v3/links?q=apple', $this->headers);
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('data.links'));
        $this->assertSame('apple-search', $searchRes->json('data.links.0.slug'));

        // Tag filter tag=food
        $tagRes = $this->getJson('http://links.t-api.de/v3/links?tag=food', $this->headers);
        $tagRes->assertOk();
        $this->assertCount(1, $tagRes->json('data.links'));
        $this->assertSame('banana-search', $tagRes->json('data.links.0.slug'));

        // Domain filter
        $domainRes = $this->getJson('http://links.t-api.de/v3/links?domain=meinlink.at', $this->headers);
        $domainRes->assertOk();
        $this->assertCount(1, $domainRes->json('data.links'));
        $this->assertSame('meinlink.at', $domainRes->json('data.links.0.domain.hostname'));
    }

    public function test_v3_bulk_link_creation(): void
    {
        $response = $this->postJson('http://links.t-api.de/v3/links/bulk', [
            'links' => [
                [
                    'url' => 'https://example.com/item1',
                    'slug' => 'bulk-1',
                    'domain' => 'href.nz',
                ],
                [
                    'url' => 'https://example.com/item2',
                    'slug' => 'bulk-2',
                    'tags' => 'batch, multi',
                ],
                [
                    'url' => 'invalid-url', // should fail validation
                ],
            ],
        ], $this->headers);

        $response->assertStatus(207); // Multi-Status
        $this->assertSame(3, $response->json('data.summary.total'));
        $this->assertSame(2, $response->json('data.summary.succeeded'));
        $this->assertSame(1, $response->json('data.summary.failed'));

        $this->assertSame('created', $response->json('data.items.0.status'));
        $this->assertSame('bulk-1', $response->json('data.items.0.data.slug'));

        $this->assertSame('created', $response->json('data.items.1.status'));
        $this->assertSame('bulk-2', $response->json('data.items.1.data.slug'));

        $this->assertSame('failed', $response->json('data.items.2.status'));
    }

    public function test_v3_analytics_endpoint(): void
    {
        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->hrefDomain->id,
            'slug' => 'stats-link',
            'destination_url' => 'https://example.com/stats',
            'is_active' => true,
        ]);

        Click::create([
            'link_id' => $link->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'referrer' => 'https://google.com',
            'country_code' => 'AT',
            'is_direct_url' => false,
            'created_at' => now(),
        ]);

        Click::create([
            'link_id' => $link->id,
            'ip_hash' => hash('sha256', '127.0.0.2'),
            'referrer' => 'https://twitter.com',
            'country_code' => 'DE',
            'is_direct_url' => false,
            'created_at' => now(),
        ]);

        $res = $this->getJson("http://links.t-api.de/v3/links/{$link->id}/analytics", $this->headers);
        $res->assertOk();
        $this->assertSame(2, $res->json('data.summary.total_clicks'));
        $this->assertSame(2, $res->json('data.summary.unique_visitors'));
        $this->assertNotEmpty($res->json('data.top_referrers'));
        $this->assertNotEmpty($res->json('data.top_countries'));
    }

    public function test_v3_qr_code_generation(): void
    {
        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->hrefDomain->id,
            'slug' => 'qr-link',
            'destination_url' => 'https://example.com/qr',
            'is_active' => true,
        ]);

        // Default SVG format
        $svgRes = $this->get("http://links.t-api.de/v3/links/{$link->id}/qr", $this->headers);
        $svgRes->assertOk();
        $this->assertStringContainsString('image/svg+xml', $svgRes->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $svgRes->getContent());

        // PNG format
        $pngRes = $this->get("http://links.t-api.de/v3/links/{$link->id}/qr?format=png&size=200", $this->headers);
        $pngRes->assertOk();
        $this->assertStringContainsString('image/png', $pngRes->headers->get('Content-Type'));
    }

    public function test_v3_update_and_destroy_link(): void
    {
        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->hrefDomain->id,
            'slug' => 'up-del-link',
            'destination_url' => 'https://example.com/initial',
            'is_active' => true,
        ]);

        // Update
        $updateRes = $this->putJson("http://links.t-api.de/v3/links/{$link->id}", [
            'url' => 'https://example.com/updated-url',
            'description' => 'Updated link description',
        ], $this->headers);

        $updateRes->assertOk();
        $this->assertSame('https://example.com/updated-url', $updateRes->json('data.destination_url'));
        $this->assertSame('Updated link description', $updateRes->json('data.description'));

        // Destroy / Deactivate
        $delRes = $this->deleteJson("http://links.t-api.de/v3/links/{$link->id}", [], $this->headers);
        $delRes->assertOk();

        $this->assertFalse($link->fresh()->is_active);
    }
}
