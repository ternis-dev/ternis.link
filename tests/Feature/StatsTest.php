<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Admin\LinkModeration;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\QrGeneration;
use App\Models\User;
use App\Support\NetworkStats;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class StatsTest extends TestCase
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
            'slug' => 'statlink1',
            'destination_url' => 'https://example.com/stats',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 5,
        ]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => false, 'referrer' => 'https://secret.example/', 'user_agent' => 'SecretAgent/1.0']);
    }

    public function test_overview_is_public_on_ternis_host(): void
    {
        $clickedDomain = Domain::where('hostname', 'clicked.at')->firstOrFail();
        $clickedLink = Link::create([
            'slug' => 'clickedstat1',
            'destination_url' => 'https://example.com/clicked',
            'domain_id' => $clickedDomain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 1,
        ]);
        Click::create(['link_id' => $clickedLink->id, 'is_direct_url' => false]);

        $custom = Domain::create([
            'hostname' => 'private.example.org',
            'user_id' => $this->user->id,
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
        ]);
        Link::create([
            'slug' => 'privlink1',
            'destination_url' => 'https://example.com/priv',
            'domain_id' => $custom->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        QrGeneration::create(['format' => 'svg']);
        QrGeneration::create(['link_id' => $clickedLink->id, 'format' => 'png']);
        NetworkStats::flush();

        $this->get('http://ternis.link/pages/stats')
            ->assertStatus(200)
            ->assertSee('Network Stats', escape: false)
            ->assertSee('Links Created (All Time)', escape: false)
            ->assertSee('Links Created Per Day', escape: false)
            ->assertSee('Network Activity Heatmap', escape: false)
            ->assertSee('data-calendar-heatmap', escape: false)
            ->assertSee('data-heatmap', escape: false)
            ->assertSee('Bar Graph', escape: false)
            ->assertSee('Graph', escape: false)
            ->assertSee('data-chart="multi"', escape: false)
            ->assertSee('data-chart-switch="bar"', escape: false)
            ->assertSee('data-chart-switch="line"', escape: false)
            ->assertSee('data-chart-unit="QR code"', escape: false)
            ->assertSee('data-chart-verb="generated"', escape: false)
            ->assertSee('On-demand vector SVG and PNG QR codes rendered across short-link QR endpoints', escape: false)
            ->assertDontSee('private.example.org', escape: false)
            ->assertSee('Custom domains', escape: false)
            ->assertViewHas('creationDatasets', function ($datasets) {
                $byLabel = collect($datasets)->keyBy('label');

                return $byLabel->has('href.nz')
                    && $byLabel->has('clicked.at')
                    && $byLabel->has('Custom domains')
                    && $byLabel['href.nz']['colorLight'] !== $byLabel['clicked.at']['colorLight']
                    && $byLabel['clicked.at']['colorLight'] !== $byLabel['Custom domains']['colorLight'];
            })
            ->assertViewHas('heatmaps', fn ($heatmaps) => isset($heatmaps['calendar']['clicks'], $heatmaps['calendar']['links'], $heatmaps['calendar']['qr'])
                && isset($heatmaps['hourly']['clicks'], $heatmaps['hourly']['links'], $heatmaps['hourly']['qr'])
                && count($heatmaps['hourly']['clicks']) === 7
                && count($heatmaps['hourly']['clicks'][0]) === 24);
    }

    public function test_stats_markdown_includes_network_activity_heatmap(): void
    {
        NetworkStats::flush();

        $this->get('http://ternis.link/pages/stats.md')
            ->assertStatus(200)
            ->assertSee('## Network Activity Heatmap — Clicks (Weekday × UTC Hour, last 90 days)', escape: false)
            ->assertSee('| Mon |', escape: false)
            ->assertDontSee($this->user->email, escape: false)
            ->assertDontSee('secret.example', escape: false);
    }

    public function test_heatmap_and_per_domain_charts_preserve_tombstoned_links_and_clicks(): void
    {
        $admin = User::factory()->admin()->create();
        $link = Link::where('slug', 'statlink1')->firstOrFail();
        $link->update(['is_active' => false, 'is_removed' => true, 'click_count' => 1]);

        NetworkStats::flush();
        $before = NetworkStats::heatmaps();
        $beforeCreations = NetworkStats::creationsByDomain(30);
        $beforeClicks = NetworkStats::clicksByDomain(30);
        $today = now()->format('Y-m-d');

        Livewire::actingAs($admin)
            ->test(LinkModeration::class)
            ->call('delete', $link->id)
            ->assertHasNoErrors();

        $after = NetworkStats::heatmaps();
        $afterCreations = NetworkStats::creationsByDomain(30);
        $afterClicks = NetworkStats::clicksByDomain(30);

        $this->assertSame($before['calendar']['links'][$today] ?? 0, $after['calendar']['links'][$today] ?? 0);
        $this->assertSame($before['calendar']['clicks'][$today] ?? 0, $after['calendar']['clicks'][$today] ?? 0);
        $this->assertSame($beforeCreations['datasets'], $afterCreations['datasets']);
        $this->assertSame($beforeClicks['datasets'], $afterClicks['datasets']);
        $this->assertGreaterThanOrEqual(1, $after['calendar']['links'][$today] ?? 0);
        $this->assertGreaterThanOrEqual(1, $after['calendar']['clicks'][$today] ?? 0);
    }

    public function test_subpages_are_public_on_ternis_host(): void
    {
        $this->get('http://ternis.link/pages/stats/domains')
            ->assertStatus(200)
            ->assertSee('Links Per Domain', escape: false)
            ->assertSee('Volume Per Domain', escape: false)
            ->assertSee('Bar Graph', escape: false)
            ->assertSee('Graph', escape: false)
            ->assertSee('data-chart-colors-light=', escape: false)
            ->assertSee('never shown', escape: false);
    }

    public function test_top_links_page_is_gone(): void
    {
        // Per-link leaderboards distort the analytics members rely on.
        $this->get('http://ternis.link/pages/stats/links')->assertNotFound();
        $this->get('http://ternis.link/pages/stats/links.md')->assertNotFound();

        // Old bookmarks land on the overview instead of a 404.
        $this->get('http://ternis.link/stats/links')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/stats');
    }

    public function test_domains_survive_a_poisoned_stats_cache(): void
    {
        Cache::put('stats:domains:v2', 'not-attribute-rows', 600);

        $domains = NetworkStats::domains();

        $this->assertContainsOnlyInstancesOf(Domain::class, $domains);
        $this->assertTrue($domains->contains('hostname', 'href.nz'));
    }

    public function test_domains_page_shows_platform_and_own_domains_only(): void
    {
        $custom = Domain::create([
            'hostname' => 'go.example.com',
            'user_id' => $this->user->id,
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        Link::create([
            'slug' => 'custom1',
            'destination_url' => 'https://example.com/custom',
            'domain_id' => $custom->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'click_count' => 3,
        ]);

        $stranger = User::factory()->create();

        // Guests see platform domains, never another user's hostname.
        $this->get('http://ternis.link/pages/stats/domains')
            ->assertStatus(200)
            ->assertSee('href.nz', escape: false)
            ->assertDontSee('go.example.com')
            ->assertSee('never shown', escape: false)
            ->assertViewHas('domains', fn ($domains) => $domains->isNotEmpty()
                && $domains->every(fn ($domain) => $domain->user_id === null));

        // Owners see their own custom domain next to the platform ones.
        $this->actingAs($this->user)
            ->get('http://ternis.link/pages/stats/domains')
            ->assertStatus(200)
            ->assertSee('href.nz', escape: false)
            ->assertSee('go.example.com', escape: false)
            ->assertViewHas('domains', fn ($domains) => $domains->contains('hostname', 'go.example.com'));

        // Strangers still don't.
        $this->actingAs($stranger)
            ->get('http://ternis.link/pages/stats/domains')
            ->assertStatus(200)
            ->assertSee('href.nz', escape: false)
            ->assertDontSee('go.example.com');

        $this->get('http://ternis.link/pages/stats/domains.md')
            ->assertStatus(200)
            ->assertSee('| href.nz |', escape: false)
            ->assertDontSee('go.example.com')
            ->assertSee('never shown', escape: false);
    }

    public function test_stats_leak_no_personal_data(): void
    {
        foreach (['http://ternis.link/pages/stats', 'http://ternis.link/pages/stats/domains'] as $url) {
            $response = $this->get($url)->assertStatus(200);
            $response->assertDontSee($this->user->email, escape: false);
            $response->assertDontSee('secret.example', escape: false);
            $response->assertDontSee('SecretAgent', escape: false);
            $response->assertDontSee('https://example.com/stats', escape: false);
        }
    }

    public function test_removed_links_stay_counted_in_stats(): void
    {
        Link::where('slug', 'statlink1')->update(['is_removed' => true]);

        $this->get('http://ternis.link/pages/stats')
            ->assertStatus(200)
            ->assertSee('Removed Links', escape: false)
            ->assertViewHas('stats', fn ($stats) => $stats['total_links'] === 1
                && $stats['removed_links'] === 1
                && $stats['total_clicks'] === 1);
    }

    public function test_stats_404_on_other_hosts(): void
    {
        $this->get('http://href.nz/pages/stats')->assertNotFound();
        $this->get('http://dash.ternis.link/pages/stats')->assertNotFound();
        $this->get('http://admin.ternis.link/pages/stats')->assertNotFound();
    }

    public function test_legacy_stats_prefix_redirects_to_pages(): void
    {
        $this->get('http://ternis.link/stats')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/stats');

        $this->get('http://ternis.link/stats/domains')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/stats/domains');
    }
}
