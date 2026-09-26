<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Concerns\SolvesAltcha;

class JunkUrlTest extends TestCase
{
    use RefreshDatabase;
    use SolvesAltcha;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function test_guest_form_rejects_scanner_probe_with_junk_notice(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://phpinfo.php')
            ->set('altcha_payload', $this->solvedAltchaPayload())
            ->call('create')
            ->assertHasErrors('destination_url')
            ->assertSet('errorKind', 'junk')
            ->assertSee('That doesn’t look like a real link', escape: false)
            ->assertSee('Scanner-style probes', escape: false);

        $this->assertDatabaseMissing('links', ['destination_url' => 'https://phpinfo.php']);
    }

    public function test_junk_rejection_does_not_burn_guest_quota(): void
    {
        $before = Livewire::test(ShortenForm::class)->get('quotaLeft');

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://info.php.bak')
            ->set('altcha_payload', $this->solvedAltchaPayload())
            ->call('create')
            ->assertSet('errorKind', 'junk');

        $this->assertSame($before, Livewire::test(ShortenForm::class)->get('quotaLeft'));
    }

    public function test_public_api_rejects_junk_with_422(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://.env.backup1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('destination_url');
        $this->assertDatabaseMissing('links', ['destination_url' => 'https://.env.backup1']);
    }

    public function test_dashboard_form_maps_junk_onto_destination_field(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://server-status.php')
            ->call('create')
            ->assertHasErrors('destination_url')
            ->assertSee('Scanner-style probes', escape: false);

        $this->assertDatabaseMissing('links', ['destination_url' => 'https://server-status.php']);
    }

    public function test_direct_url_redirect_skips_tracking_for_junk_but_still_redirects(): void
    {
        $response = $this->get('http://href.nz/url/https://phpinfo.php.save');

        $response->assertRedirect('https://phpinfo.php.save');
        $this->assertDatabaseMissing('links', ['destination_url' => 'https://phpinfo.php.save']);
    }

    public function test_direct_url_redirect_still_tracks_real_urls(): void
    {
        $response = $this->get('http://href.nz/url/https://example.com/real-page');

        $response->assertRedirect('https://example.com/real-page');
        $this->assertDatabaseHas('links', ['destination_url' => 'https://example.com/real-page']);
    }

    public function test_purge_command_dry_run_lists_without_changing(): void
    {
        $junk = Link::create([
            'slug' => 'junk0001',
            'destination_url' => 'https://debug.php',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);
        $real = Link::create([
            'slug' => 'real0001',
            'destination_url' => 'https://example.com/real',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);

        $this->artisan('links:purge-junk')
            ->expectsOutputToContain('junk0001')
            ->doesntExpectOutputToContain('real0001')
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();

        $this->assertTrue($junk->fresh()->is_active);
        $this->assertTrue($real->fresh()->is_active);
    }

    public function test_purge_command_apply_deactivates_only_junk(): void
    {
        $junk = Link::create([
            'slug' => 'junk0002',
            'destination_url' => 'https://test.php',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);
        $real = Link::create([
            'slug' => 'real0002',
            'destination_url' => 'https://example.com/also-real',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);

        $this->artisan('links:purge-junk', ['--apply' => true])
            ->expectsOutputToContain('Deactivated 1 junk link')
            ->assertSuccessful();

        $this->assertFalse($junk->fresh()->is_active);
        $this->assertTrue($real->fresh()->is_active);
    }

    public function test_dashboard_tables_use_custom_pagination(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            Link::create([
                'slug' => 'page-test-'.$i,
                'destination_url' => 'https://example.com/page-'.$i,
                'domain_id' => $this->publicDomain->id,
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        $this->actingAs($user)
            ->get('http://dash.ternis.link/links')
            ->assertStatus(200)
            ->assertSee('ui-pagination', escape: false)
            ->assertSee('aria-label="Pagination"', escape: false)
            ->assertSee('Showing', escape: false);
    }
}
