<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkImport;
use App\Livewire\Dashboard\LinkTable;
use App\Models\ApiKey;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
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
        $this->get('http://my.ternis.link/')
            ->assertRedirect('http://my.ternis.link/login');

        $this->get('http://my.ternis.link/links')
            ->assertRedirect('http://my.ternis.link/login');
    }

    public function test_overview_shows_only_public_shortener_links(): void
    {
        $this->makeLink('href.nz', 'pub-overview-1');
        $this->makeLink('meinlink.at', 'pub-overview-2');
        $this->makeLink('href.yt', 'pub-overview-3');
        $this->makeLink('clicked.at', 'dash-only-1');
        $this->makeLink('ternis.link', 'dash-only-2');

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_links'] === 3)
            ->assertSee('nd-stat-value', escape: false)
            ->assertSee('Links by domain', escape: false);
    }

    public function test_legacy_opt_in_redirects_home_to_legacy(): void
    {
        $this->user->update(['public_dashboard_legacy' => true]);

        // New links/import pages stay put; only the home bounces.
        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertRedirect('http://my.ternis.link/_legacy');

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/_legacy')
            ->assertOk()
            ->assertSee('pd-hero', escape: false);
    }

    public function test_dashboard_switch_round_trip(): void
    {
        // Guests bounce to same-host login on both switch routes.
        $this->post('http://my.ternis.link/switch-to-legacy')
            ->assertRedirect('http://my.ternis.link/login');

        // Opt out by default.
        $this->assertFalse($this->user->fresh()->public_dashboard_legacy);

        $this->actingAs($this->user)
            ->post('http://my.ternis.link/switch-to-legacy')
            ->assertRedirect('http://my.ternis.link/_legacy');
        $this->assertTrue($this->user->fresh()->public_dashboard_legacy);

        $this->actingAs($this->user)
            ->post('http://my.ternis.link/_legacy/use-new')
            ->assertRedirect('http://my.ternis.link');
        $this->assertFalse($this->user->fresh()->public_dashboard_legacy);
    }

    public function test_links_table_partitions_public_and_personal(): void
    {
        $public = $this->makeLink('href.nz', 'pub-part-1');
        $personal = $this->makeLink('clicked.at', 'pers-part-1');

        // Public link: visible on my.ternis.link, 404 on dash.
        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$public->id}")
            ->assertOk();
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$public->id}")
            ->assertNotFound();

        // Dash-side link: visible on dash, 404 on my.ternis.link.
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$personal->id}")
            ->assertOk();
        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$personal->id}")
            ->assertNotFound();
    }

    public function test_public_create_and_links_pages_render(): void
    {
        $this->actingAs($this->user)
            ->get('http://my.ternis.link/links')
            ->assertOk()
            ->assertSee('Your Links', escape: false);

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/links/create')
            ->assertOk()
            ->assertSee('Create Short Link', escape: false);

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/new')
            ->assertOk();
    }

    public function test_login_page_uses_public_theme_and_local_sso(): void
    {
        $this->get('http://my.ternis.link/login')
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
            ->assertSee('of your links live on the public dashboard', escape: false);

        $other = User::factory()->create();

        $this->actingAs($other)
            ->get('http://dash.ternis.link/links')
            ->assertOk()
            ->assertDontSee('of your links live on the public dashboard', escape: false);
    }

    public function test_legacy_hosts_redirect_to_canonical(): void
    {
        $this->get('http://my.href.nz/links')
            ->assertRedirect('http://my.ternis.link/links');
        $this->get('http://my.href.yt/links')
            ->assertRedirect('http://my.ternis.link/links');
    }

    public function test_public_export_contains_only_public_links(): void
    {
        $this->makeLink('href.nz', 'pub-exp-1');
        $this->makeLink('clicked.at', 'dash-exp-1');

        $content = $this->actingAs($this->user)
            ->get('http://my.ternis.link/links/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('pub-exp-1', $content);
        $this->assertStringNotContainsString('dash-exp-1', $content);
    }

    public function test_public_import_page_renders(): void
    {
        $this->actingAs($this->user)
            ->get('http://my.ternis.link/links/import')
            ->assertOk()
            ->assertSee('Import Links', escape: false);
    }

    public function test_public_import_page_requires_login(): void
    {
        $this->get('http://my.ternis.link/links/import')
            ->assertRedirect('http://my.ternis.link/login');
    }

    public function test_public_import_enforces_hostname_partition(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LinkImport::class, ['scope' => 'public'])
            ->set('csv', "https://example.com/a,href.nz,pub-imp-a\nhttps://example.com/b,clicked.at,dash-imp-b")
            ->call('import');

        $results = $component->get('results');
        $this->assertTrue($results[0]['ok']);
        $this->assertFalse($results[1]['ok']);
        $this->assertStringContainsString('dash.ternis.link', $results[1]['message']);
        $this->assertDatabaseHas('links', ['slug' => 'pub-imp-a', 'user_id' => $this->user->id]);
        $this->assertDatabaseMissing('links', ['slug' => 'dash-imp-b']);
    }

    public function test_personal_import_rejects_public_hostnames(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LinkImport::class, ['scope' => 'personal'])
            ->set('csv', 'https://example.com/a,href.nz,pers-imp-a')
            ->call('dryRunImport');

        $results = $component->get('results');
        $this->assertFalse($results[0]['ok']);
        $this->assertStringContainsString('my.ternis.link', $results[0]['message']);
    }

    public function test_overview_shows_domain_breakdown_and_first_run_state(): void
    {
        // First run: empty-state CTA instead of an empty table.
        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertOk()
            ->assertSee('Paste any long URL', escape: false);

        $this->makeLink('href.nz', 'pub-bd-1');
        $this->makeLink('href.nz', 'pub-bd-2');
        $this->makeLink('meinlink.at', 'pub-bd-3');

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertOk()
            ->assertSee('Links by domain', escape: false)
            ->assertSee('href.nz', escape: false)
            ->assertSee('meinlink.at', escape: false)
            ->assertDontSee('Paste any long URL', escape: false);
    }

    public function test_link_table_filters_by_domain_on_public_theme(): void
    {
        $this->makeLink('href.nz', 'pub-df-1');
        $this->makeLink('meinlink.at', 'pub-df-2');

        // Unfiltered public table shows both.
        Livewire::actingAs($this->user)
            ->test(LinkTable::class, ['scope' => 'public', 'theme' => 'public'])
            ->assertSee('pub-df-1')
            ->assertSee('pub-df-2')
            ->assertSee('All domains')
            // Narrow to meinlink.at: href.nz row disappears.
            ->set('domainFilter', 'meinlink.at')
            ->assertSee('pub-df-2')
            ->assertDontSee('pub-df-1');

        // Unknown hostnames are ignored, never empty the table by injection.
        Livewire::actingAs($this->user)
            ->test(LinkTable::class, ['scope' => 'public', 'theme' => 'public'])
            ->set('domainFilter', 'evil.test')
            ->assertSee('pub-df-1')
            ->assertSee('pub-df-2');
    }

    public function test_table_and_detail_offer_copy_with_loading_states(): void
    {
        $link = $this->makeLink('href.nz', 'pub-copy-1');

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/links')
            ->assertOk()
            ->assertSee('data-copy="https://href.nz/pub-copy-1"', escape: false)
            ->assertSee('wire:loading', escape: false)
            ->assertSee('wire:poll.30s.visible', escape: false);

        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$link->id}")
            ->assertOk()
            ->assertSee('data-copy="https://href.nz/pub-copy-1"', escape: false);
    }

    public function test_overview_flags_links_expiring_within_seven_days(): void
    {
        $soon = $this->makeLink('href.nz', 'pub-exp-soon');
        $soon->update(['expires_at' => now()->addDays(3)]);

        $later = $this->makeLink('href.nz', 'pub-exp-later');
        $later->update(['expires_at' => now()->addDays(30)]);

        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertOk()
            ->assertSee('Expiring soon', escape: false)
            ->assertSee('pub-exp-soon')
            ->assertViewHas('expiringSoon', fn ($collection) => $collection->pluck('id')->all() === [$soon->id]);
    }

    public function test_overview_lists_top_performers_by_clicks(): void
    {
        $hot = $this->makeLink('href.nz', 'pub-top-hot');
        $hot->update(['click_count' => 42]);
        $warm = $this->makeLink('meinlink.at', 'pub-top-warm');
        $warm->update(['click_count' => 7]);
        $this->makeLink('href.yt', 'pub-top-zero');

        $response = $this->actingAs($this->user)->get('http://my.ternis.link/');

        $response->assertOk()
            ->assertSee('Top performers', escape: false)
            ->assertSee('pub-top-hot')
            ->assertSee('pub-top-warm')
            ->assertViewHas('topLinks', fn ($collection) => $collection->pluck('id')->all() === [$hot->id, $warm->id]);

        // Order on the page follows clicks desc.
        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'pub-top-hot') < strpos($content, 'pub-top-warm'));
    }

    public function test_overview_shows_latest_clicks_feed(): void
    {
        $public = $this->makeLink('href.nz', 'pub-feed-1');
        $personal = $this->makeLink('clicked.at', 'dash-feed-1');

        Click::create(['link_id' => $public->id, 'referrer' => 'https://example.com/post', 'is_direct_url' => false]);
        Click::create(['link_id' => $public->id, 'is_direct_url' => true]);
        Click::create(['link_id' => $personal->id, 'referrer' => 'https://example.com/other', 'is_direct_url' => false]);

        $response = $this->actingAs($this->user)->get('http://my.ternis.link/');

        $response->assertOk()
            ->assertSee('Latest clicks', escape: false)
            ->assertSee('pub-feed-1')
            ->assertSee('example.com', escape: false)
            ->assertViewHas('recentClicks', fn ($collection) => $collection->pluck('link_id')->all() === [$public->id]);
    }

    public function test_qr_zip_contains_only_public_links(): void
    {
        $this->makeLink('href.nz', 'pub-qr-zip');
        $this->makeLink('clicked.at', 'dash-qr-zip');

        $response = $this->actingAs($this->user)
            ->get('http://my.ternis.link/links/qr-zip');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        $names = $this->zipNames($response->streamedContent());
        $this->assertContains('qr-href.nz-pub-qr-zip.png', $names);
        $this->assertNotContains('qr-clicked.at-dash-qr-zip.png', $names);

        // PNG magic bytes inside the first entry.
        $tmp = tempnam(sys_get_temp_dir(), 'qr-test-');
        file_put_contents($tmp, $response->streamedContent());
        $zip = new \ZipArchive;
        $zip->open($tmp);
        $this->assertStringStartsWith("\x89PNG", $zip->getFromIndex(0));
        $zip->close();
        unlink($tmp);
    }

    public function test_qr_zip_requires_links_and_login(): void
    {
        $this->get('http://my.ternis.link/links/qr-zip')
            ->assertRedirect('http://my.ternis.link/login');

        $other = User::factory()->create();

        $this->actingAs($other)
            ->get('http://my.ternis.link/links/qr-zip')
            ->assertNotFound();
    }

    public function test_overview_embeds_first_run_tour(): void
    {
        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertOk()
            ->assertSee('id="tl-tour-steps"', escape: false)
            ->assertSee('Welcome to my.ternis.link', escape: false);
    }

    public function test_duplicate_clones_link_with_fresh_slug(): void
    {
        $original = $this->makeLink('href.nz', 'pub-dupe-1');
        $original->update([
            'description' => 'Campaign link',
            'tags' => ['launch', 'promo'],
            'expires_at' => now()->addDays(9),
        ]);

        // Guests bounce to same-host login.
        $this->post("http://my.ternis.link/links/{$original->id}/duplicate")
            ->assertRedirect('http://my.ternis.link/login');

        $response = $this->actingAs($this->user)
            ->post("http://my.ternis.link/links/{$original->id}/duplicate");

        $copy = Link::where('destination_url', $original->destination_url)
            ->where('id', '!=', $original->id)
            ->firstOrFail();

        $response->assertRedirect("http://my.ternis.link/links/{$copy->id}/edit");
        $response->assertSessionHas('info');
        $this->assertNotSame($original->slug, $copy->slug);
        $this->assertSame('Campaign link', $copy->description);
        $this->assertSame(['launch', 'promo'], $copy->tags);
        $this->assertNull($copy->expires_at);
        $this->assertSame(0, $copy->click_count);

        // Wrong side and strangers 404.
        $personal = $this->makeLink('clicked.at', 'dash-dupe-1');

        $this->actingAs($this->user)
            ->post("http://my.ternis.link/links/{$personal->id}/duplicate")
            ->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->post("http://my.ternis.link/links/{$original->id}/duplicate")
            ->assertNotFound();
    }

    /**
     * @return list<string>
     */
    private function zipNames(string $binary): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'qr-names-');
        file_put_contents($tmp, $binary);
        $zip = new \ZipArchive;
        $zip->open($tmp);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        unlink($tmp);

        return $names;
    }

    public function test_global_header_adapts_to_public_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get('http://my.ternis.link/')
            ->assertOk()
            ->assertSee('My links', escape: false)
            ->assertSee('Account', escape: false);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links')
            ->assertOk()
            ->assertSee('>Dashboard<', escape: false);
    }

    public function test_public_per_key_page_lists_only_that_key_public_links(): void
    {
        $key = ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => ApiKey::hashToken('tl_'.Str::random(48)),
            'key_prefix' => 'tl_pubkey',
            'api_version' => 1,
            'name' => 'Public Key',
        ]);

        $keyedPublic = Link::create([
            'slug' => 'pub-key-1', 'destination_url' => 'https://example.com/a',
            'domain_id' => Domain::where('hostname', 'href.nz')->firstOrFail()->id,
            'user_id' => $this->user->id, 'api_key_id' => $key->id, 'is_active' => true,
        ]);
        Link::create([
            'slug' => 'pub-nokey-1', 'destination_url' => 'https://example.com/b',
            'domain_id' => Domain::where('hostname', 'href.nz')->firstOrFail()->id,
            'user_id' => $this->user->id, 'is_active' => true,
        ]);
        Link::create([
            'slug' => 'dash-key-1', 'destination_url' => 'https://example.com/c',
            'domain_id' => Domain::where('hostname', 'clicked.at')->firstOrFail()->id,
            'user_id' => $this->user->id, 'api_key_id' => $key->id, 'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get("http://my.ternis.link/api-keys/{$key->id}")
            ->assertOk()
            ->assertSee('Public Key', escape: false)
            ->assertSee('pub-key-1')
            ->assertDontSee('pub-nokey-1')
            ->assertDontSee('dash-key-1');

        // Row deep-link keeps the per-key back context.
        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$keyedPublic->id}?from_api_key={$key->id}")
            ->assertOk()
            ->assertSee('Back to API key links', escape: false);

        // A foreign key id falls back to the plain links list.
        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$keyedPublic->id}?from_api_key=01K9999999999999999999999")
            ->assertOk()
            ->assertSee('Back to Links', escape: false);

        // Another user's key is invisible here.
        $other = User::factory()->create();
        $foreign = ApiKey::create([
            'user_id' => $other->id, 'key_hash' => ApiKey::hashToken('tl_'.Str::random(48)),
            'key_prefix' => 'tl_foreign', 'api_version' => 1, 'name' => 'Foreign',
        ]);

        $this->actingAs($this->user)
            ->get("http://my.ternis.link/api-keys/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_qr_download_scoped_to_public_links(): void
    {
        $public = $this->makeLink('href.nz', 'pub-qr-1');
        $personal = $this->makeLink('clicked.at', 'pers-qr-1');

        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$public->id}/qr")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($this->user)
            ->get("http://my.ternis.link/links/{$personal->id}/qr")
            ->assertNotFound();
    }
}
