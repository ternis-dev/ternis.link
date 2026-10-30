<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeinlinkDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->user = User::factory()->create();
    }

    private function link(User $user, string $host = 'meinlink.at', array $extra = []): Link
    {
        return Link::create(array_merge([
            'user_id' => $user->id,
            'domain_id' => Domain::where('hostname', $host)->firstOrFail()->id,
            'slug' => 'test-'.strtolower(Str::random(8)),
            'destination_url' => 'https://example.com/test',
            'is_active' => true,
        ], $extra));
    }

    public function test_login_and_handoff_use_the_separate_german_surface(): void
    {
        $this->get('http://dash.meinlink.at/')->assertRedirect('http://dash.meinlink.at/login');
        $this->get('http://dash.meinlink.at/login')->assertOk()->assertSee('lang="de"', false)->assertSee('Deine Links.');
        $this->get('http://dash.meinlink.at/auth/redirect')->assertRedirect('/auth/sso/start?to=%2F');
        $this->get('http://dash.meinlink.at/auth/sso/start')->assertRedirect('http://dash.ternis.link/auth/sso/issue?host=dash.meinlink.at&to=%2F');
        $this->actingAs($this->user)->get('http://dash.meinlink.at/login')->assertRedirect('http://dash.meinlink.at');
    }

    public function test_identity_handoff_logs_in_on_the_german_dashboard(): void
    {
        $location = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/auth/sso/issue?host=dash.meinlink.at&to=/links')
            ->assertRedirectContains('http://dash.meinlink.at/auth/sso/consume?token=')
            ->headers->get('Location');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get($location)->assertRedirect('http://dash.meinlink.at/links');
        $this->assertAuthenticatedAs($this->user);
        $this->get('http://dash.meinlink.at/links')->assertOk()->assertViewIs('meinlink-dashboard.index');
    }

    public function test_dashboard_only_lists_owned_meinlink_links(): void
    {
        $own = $this->link($this->user);
        $foreign = $this->link(User::factory()->create());
        $otherDomain = $this->link($this->user, 'href.nz');
        $this->actingAs($this->user)->get('http://dash.meinlink.at/')->assertOk()
            ->assertViewIs('meinlink-dashboard.index')->assertSee($own->slug)
            ->assertDontSee($foreign->slug)->assertDontSee($otherDomain->slug);
        $this->get('http://dash.meinlink.at/new')->assertOk()->assertSee('Mach es kurz.');
        $this->get('http://dash.meinlink.at/help')->assertOk()->assertSee('Kurz erklärt.');
    }

    public function test_user_can_create_edit_pause_and_download_qr(): void
    {
        $this->actingAs($this->user)->post('http://dash.meinlink.at/links', [
            'destination_url' => 'https://example.com/created', 'description' => 'Mein Projekt',
        ])->assertRedirect();
        $link = $this->user->links()->firstOrFail();
        $this->assertSame('meinlink.at', $link->domain->hostname);
        $this->get('http://dash.meinlink.at/links/'.$link->id)->assertOk()->assertSee('Mein Projekt');
        $this->get('http://dash.meinlink.at/links/'.$link->id.'/edit')->assertOk();
        $this->put('http://dash.meinlink.at/links/'.$link->id, [
            'destination_url' => 'https://example.com/updated', 'description' => 'Neu',
        ])->assertRedirect();
        $this->assertSame('https://example.com/updated', $link->fresh()->destination_url);
        $this->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertRedirect();
        $this->assertFalse($link->fresh()->is_active);
        $this->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertRedirect();
        $this->assertTrue($link->fresh()->is_active);
        $this->get('http://dash.meinlink.at/links/'.$link->id.'/qr')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_every_link_endpoint_rejects_foreign_and_other_domain_links(): void
    {
        $this->actingAs($this->user);
        foreach ([$this->link(User::factory()->create()), $this->link($this->user, 'href.nz')] as $link) {
            foreach (['', '/edit', '/qr'] as $suffix) {
                $this->get('http://dash.meinlink.at/links/'.$link->id.$suffix)->assertNotFound();
            }
            $this->put('http://dash.meinlink.at/links/'.$link->id, ['destination_url' => 'https://example.com/other'])->assertNotFound();
            $this->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertNotFound();
        }
    }

    public function test_statistics_and_csv_only_include_owned_meinlink_clicks_in_range(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->setTime(12, 0));
        $own = $this->link($this->user);
        $other = $this->link(User::factory()->create());
        $otherDomain = $this->link($this->user, 'href.nz');
        $removed = $this->link($this->user, extra: ['is_removed' => true]);
        foreach ([$own, $other, $otherDomain, $removed] as $link) {
            Click::create(['link_id' => $link->id, 'created_at' => now()]);
        }
        Click::create(['link_id' => $own->id, 'created_at' => now()->subDays(6)->startOfDay()]);
        Click::create(['link_id' => $own->id, 'created_at' => now()->subDays(7)->endOfDay()]);
        Click::create(['link_id' => $own->id, 'created_at' => now()->addDay()]);
        Click::create(['link_id' => $own->id, 'is_direct_url' => true, 'created_at' => now()]);
        $this->actingAs($this->user)->get('http://dash.meinlink.at/statistics?days=7')
            ->assertOk()->assertSee('Jeder Klick zählt.')
            ->assertViewHas('total', 2)->assertViewHas('activeDays', 2)
            ->assertViewHas('daily', fn ($daily) => $daily->count() === 7 && $daily->where('count', 0)->count() === 5)
            ->assertViewHas('topLinks', fn ($links) => $links->count() === 1 && $links->first()->id === $own->id && $links->first()->period_clicks === 2)
            ->assertDontSee($other->slug)->assertDontSee($otherDomain->slug)->assertDontSee($removed->slug);
        $csv = $this->get('http://dash.meinlink.at/statistics/export?days=7')->assertOk()->streamedContent();
        $this->assertStringContainsString('Datum;Klicks', $csv);
        $this->assertStringContainsString('2026-10-24;1', $csv);
        $this->assertStringContainsString('2026-10-30;1', $csv);
        $this->assertStringNotContainsString('2026-10-23', $csv);
        $this->assertStringNotContainsString('example.com', $csv);
    }

    public function test_statistics_have_empty_states_and_validate_ranges_and_search(): void
    {
        $this->get('http://dash.meinlink.at/statistics')->assertRedirect();
        $this->get('http://dash.meinlink.at/statistics/export')->assertRedirect();
        $this->actingAs($this->user)->get('http://dash.meinlink.at/statistics')->assertOk()
            ->assertSee('noch keine Klicks erfasst')->assertViewHas('total', 0);
        $this->get('http://dash.meinlink.at/statistics?days=365')->assertSessionHasErrors('days');
        $this->get('http://dash.meinlink.at/?q[]=invalid')->assertSessionHasErrors('q');
        $this->get('http://dash.meinlink.at/?sort=unknown')->assertSessionHasErrors('sort');
    }

    public function test_link_list_can_be_sorted_without_exposing_other_users(): void
    {
        $old = $this->link($this->user, extra: ['created_at' => now()->subDays(3), 'click_count' => 20]);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();
        $new = $this->link($this->user, extra: ['click_count' => 3]);
        $foreign = $this->link(User::factory()->create(), extra: ['click_count' => 100]);
        $this->actingAs($this->user)->get('http://dash.meinlink.at/?sort=clicks')->assertOk()
            ->assertSeeInOrder([$old->slug, $new->slug])->assertDontSee($foreign->slug);
        $this->get('http://dash.meinlink.at/?sort=oldest')->assertOk()->assertSeeInOrder([$old->slug, $new->slug]);
        $this->get('http://dash.meinlink.at/?sort=newest')->assertOk()->assertSeeInOrder([$new->slug, $old->slug]);
    }

    public function test_expired_paused_link_must_have_its_expiry_changed_before_activation(): void
    {
        $link = $this->link($this->user, extra: ['is_active' => false, 'expires_at' => now()->subDay()]);
        $this->actingAs($this->user)->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertSessionHasErrors('link');
        $this->assertFalse($link->fresh()->is_active);
        $this->put('http://dash.meinlink.at/links/'.$link->id, [
            'destination_url' => $link->destination_url, 'expires_at' => '',
        ])->assertRedirect();
        $this->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertRedirect();
        $this->assertTrue($link->fresh()->is_active);
        $this->assertNull($link->fresh()->expires_at);
    }

    public function test_burst_quarantine_has_consistent_status_and_hidden_mutation_controls(): void
    {
        $link = $this->link($this->user, extra: ['is_active' => false]);
        ThreatEvaluation::create([
            'link_id' => $link->id, 'creator_ip_hash' => '', 'destination_url' => $link->destination_url,
            'destination_host' => 'example.com', 'evaluation_type' => 'spam_burst', 'verdict' => 'spam',
            'threat_score' => 95, 'confidence' => .99, 'action_taken' => 'quarantined',
        ]);
        $this->actingAs($this->user)->get('http://dash.meinlink.at/links/'.$link->id)->assertOk()
            ->assertSee('Dieser Link ist gesperrt.')->assertDontSee('Link aktivieren')->assertDontSee('>Bearbeiten</a>', false);
        $this->get('http://dash.meinlink.at/links/'.$link->id.'/edit')->assertRedirect()->assertSessionHasErrors('link');
        $this->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertSessionHasErrors('link');
    }

    public function test_duplicate_slug_error_is_attached_to_the_slug_field(): void
    {
        $link = $this->link($this->user);
        $this->actingAs($this->user)->from('http://dash.meinlink.at/new')->post('http://dash.meinlink.at/links', [
            'destination_url' => 'https://example.com/new', 'slug' => $link->slug,
        ])->assertRedirect('http://dash.meinlink.at/new')->assertSessionHasErrors('slug');
        // Preserve the browser's session cookie across the redirect. JSON
        // error bags cannot be reused from a different in-memory session ID.
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('http://dash.meinlink.at/new')->assertOk()->assertSee('id="slug-error"', false);
    }

    public function test_quarantined_links_cannot_be_edited_or_reactivated(): void
    {
        $link = $this->link($this->user, extra: ['is_active' => false, 'tags' => ['quarantine']]);
        $this->actingAs($this->user)->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertSessionHasErrors('link');
        $this->put('http://dash.meinlink.at/links/'.$link->id, ['destination_url' => 'https://example.com/bypass'])->assertSessionHasErrors('link');
        $this->assertFalse($link->fresh()->is_active);
        $this->assertSame('https://example.com/test', $link->fresh()->destination_url);
    }
}
