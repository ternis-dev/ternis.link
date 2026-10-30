<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Support\NetworkStats;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsWebApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();

        $link = Link::create([
            'slug' => 'apitestlink1',
            'destination_url' => 'https://example.com/api-test',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 8,
        ]);

        Click::create(['link_id' => $link->id, 'is_direct_url' => false]);
        NetworkStats::flush();
    }

    public function test_stats_data_endpoint_returns_json_with_all_sections_and_meta(): void
    {
        $response = $this->get('http://ternis.link/pages/stats/data');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('ETag')
            ->assertJsonStructure([
                'overview' => [
                    'total_links',
                    'active_links',
                    'removed_links',
                    'total_clicks',
                    'links_today',
                    'clicks_today',
                    'direct_url_clicks',
                    'direct_url_clicks_today',
                    'qr_codes',
                    'qr_codes_today',
                    'threat_evaluations',
                    'threats_quarantined',
                    'threats_bot_protected',
                ],
                'charts' => [
                    'creations' => ['labels', 'values', 'datasets'],
                    'clicks' => ['labels', 'values', 'datasets'],
                    'qr' => ['labels', 'values', 'datasets'],
                ],
                'activity' => [
                    'links' => ['labels', 'values'],
                    'clicks' => ['labels', 'values'],
                    'qr' => ['labels', 'values'],
                ],
                'domains',
                'heatmaps' => [
                    'calendar',
                    'hourly',
                    'summaries',
                ],
                'meta' => [
                    'user_type',
                    'poll_interval',
                    'cache_ttl',
                    'timestamp',
                ],
            ]);
    }

    public function test_stats_data_returns_304_not_modified_when_etag_matches(): void
    {
        $first = $this->get('http://ternis.link/pages/stats/data');
        $first->assertOk();

        $etag = $first->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $second = $this->withHeaders(['If-None-Match' => $etag])
            ->get('http://ternis.link/pages/stats/data');

        $second->assertStatus(304);
        $this->assertEmpty($second->getContent());
    }

    public function test_stats_data_sections(): void
    {
        $this->get('http://ternis.link/pages/stats/data/overview')
            ->assertOk()
            ->assertJsonStructure(['overview', 'charts', 'heatmaps', 'meta']);

        $this->get('http://ternis.link/pages/stats/data/activity')
            ->assertOk()
            ->assertJsonStructure(['activity', 'charts', 'meta']);

        $this->get('http://ternis.link/pages/stats/data/security')
            ->assertOk()
            ->assertJsonStructure(['overview', 'breakdown', 'meta']);

        $this->get('http://ternis.link/pages/stats/data/domains')
            ->assertOk()
            ->assertJsonStructure(['domains', 'meta']);
    }

    public function test_stats_pages_support_content_negotiation_for_json(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->get('http://ternis.link/pages/stats')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['overview', 'meta']);

        $this->withHeaders(['Accept' => 'application/json'])
            ->get('http://ternis.link/pages/stats/activity')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['activity', 'meta']);

        $this->withHeaders(['Accept' => 'application/json'])
            ->get('http://ternis.link/pages/stats/security')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['overview', 'meta']);

        $this->withHeaders(['Accept' => 'application/json'])
            ->get('http://ternis.link/pages/stats/domains')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['domains', 'meta']);
    }

    public function test_user_type_tailors_poll_interval(): void
    {
        // 1. Guest: 60s
        $guestRes = $this->get('http://ternis.link/pages/stats/data');
        $this->assertSame('guest', $guestRes->json('meta.user_type'));
        $this->assertSame(60, $guestRes->json('meta.poll_interval'));

        // 2. Regular Authenticated User: 45s
        $userRes = $this->actingAs($this->user)->get('http://ternis.link/pages/stats/data');
        $this->assertSame('user', $userRes->json('meta.user_type'));
        $this->assertSame(45, $userRes->json('meta.poll_interval'));

        // 3. Admin: 30s
        $admin = User::factory()->admin()->create();
        $adminRes = $this->actingAs($admin)->get('http://ternis.link/pages/stats/data');
        $this->assertSame('admin', $adminRes->json('meta.user_type'));
        $this->assertSame(30, $adminRes->json('meta.poll_interval'));
    }

    public function test_custom_domains_privacy_in_web_api(): void
    {
        $custom = Domain::create([
            'hostname' => 'mycustom.example.com',
            'user_id' => $this->user->id,
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        Link::create([
            'slug' => 'customapi1',
            'destination_url' => 'https://example.com/custom-api',
            'domain_id' => $custom->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        NetworkStats::flush();

        // Guest does not see custom domain
        $guestDomains = $this->get('http://ternis.link/pages/stats/data')->json('domains');
        $hostnames = array_column($guestDomains, 'hostname');
        $this->assertContains('href.nz', $hostnames);
        $this->assertNotContains('mycustom.example.com', $hostnames);

        // Owner sees custom domain
        $ownerDomains = $this->actingAs($this->user)->get('http://ternis.link/pages/stats/data')->json('domains');
        $ownerHostnames = array_column($ownerDomains, 'hostname');
        $this->assertContains('mycustom.example.com', $ownerHostnames);

        // Stranger does not see custom domain
        $stranger = User::factory()->create();
        $strangerDomains = $this->actingAs($stranger)->get('http://ternis.link/pages/stats/data')->json('domains');
        $strangerHostnames = array_column($strangerDomains, 'hostname');
        $this->assertNotContains('mycustom.example.com', $strangerHostnames);
    }

    public function test_stats_data_404_on_non_ternis_host(): void
    {
        $this->get('http://href.nz/pages/stats/data')->assertNotFound();
        $this->get('http://dash.ternis.link/pages/stats/data')->assertNotFound();
    }
}
