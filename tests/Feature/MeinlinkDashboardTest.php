<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
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

    public function test_quarantined_links_cannot_be_edited_or_reactivated(): void
    {
        $link = $this->link($this->user, extra: ['is_active' => false, 'tags' => ['quarantine']]);
        $this->actingAs($this->user)->post('http://dash.meinlink.at/links/'.$link->id.'/toggle')->assertSessionHasErrors('link');
        $this->put('http://dash.meinlink.at/links/'.$link->id, ['destination_url' => 'https://example.com/bypass'])->assertSessionHasErrors('link');
        $this->assertFalse($link->fresh()->is_active);
        $this->assertSame('https://example.com/test', $link->fresh()->destination_url);
    }
}
