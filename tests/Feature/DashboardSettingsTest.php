<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Dashboard\LinkImport;
use App\Livewire\Dashboard\LinkTable;
use App\Livewire\Dashboard\SettingsForm;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create([
            'plan_id' => Plan::where('name', 'family')->firstOrFail()->id,
        ]);
    }

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('http://dash.ternis.link/settings')
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_settings_page_renders_with_defaults(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/settings')
            ->assertStatus(200)
            ->assertSee('Settings')
            ->assertSee('Navigation Layout', escape: false)
            ->assertSee('Color Theme', escape: false);

        Livewire::actingAs($this->user)
            ->test(SettingsForm::class)
            ->assertSet('nav_layout', 'side')
            ->assertSet('theme', 'system');
    }

    public function test_saving_settings_persists_preferences(): void
    {
        Livewire::actingAs($this->user)
            ->test(SettingsForm::class)
            ->set('nav_layout', 'top')
            ->set('theme', 'dark')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true)
            ->assertDispatched('notify', message: 'Settings saved.', type: 'success');

        $this->assertSame('top', $this->user->fresh()->nav_layout);
        $this->assertSame('dark', $this->user->fresh()->theme);
    }

    public function test_invalid_preferences_are_rejected(): void
    {
        Livewire::actingAs($this->user)
            ->test(SettingsForm::class)
            ->set('nav_layout', 'bottom')
            ->set('theme', 'neon')
            ->call('save')
            ->assertHasErrors(['nav_layout', 'theme']);

        $this->assertSame('side', $this->user->fresh()->nav_layout);
        $this->assertSame('system', $this->user->fresh()->theme);
    }

    public function test_dashboard_uses_side_nav_by_default(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link')
            ->assertStatus(200)
            ->assertSee('data-nav="side"', escape: false)
            ->assertDontSee('data-nav="top"', escape: false);
    }

    public function test_dashboard_uses_top_nav_when_preferred(): void
    {
        $this->user->update(['nav_layout' => 'top']);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link')
            ->assertStatus(200)
            ->assertSee('data-nav="top"', escape: false)
            ->assertDontSee('data-nav="side"', escape: false)
            ->assertSee('data-nav="top" class="no-scrollbar sticky top-4 z-30', escape: false)
            ->assertSee('Settings');
    }

    public function test_new_users_default_to_side_nav_and_system_theme(): void
    {
        $fresh = User::factory()->create()->refresh();

        $this->assertSame('side', $fresh->nav_layout);
        $this->assertSame('system', $fresh->theme);
        $this->assertFalse($fresh->usesTopNav());
    }

    public function test_analytics_renders_chart_js_canvases_with_data(): void
    {
        $domain = Domain::where('hostname', 'clicked.at')->firstOrFail();
        $link = Link::create([
            'slug' => 'chartjs123',
            'destination_url' => 'https://example.com',
            'domain_id' => $domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Click::create([
            'link_id' => $link->id,
            'user_agent' => 'Mozilla/5.0',
            'ip_hash' => hash('sha256', 'chartjs'),
            'is_direct_url' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}");

        $response->assertStatus(200);
        $response->assertSee('data-chart="clicks"', escape: false);
        $response->assertSee('data-chart-labels', escape: false);
        $response->assertSee('Clicks over time');
    }

    public function test_analytics_browsers_chart_keeps_html_legend(): void
    {
        $domain = Domain::where('hostname', 'clicked.at')->firstOrFail();
        $link = Link::create([
            'slug' => 'chartlgnd1',
            'destination_url' => 'https://example.com',
            'domain_id' => $domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Click::create([
            'link_id' => $link->id,
            'user_agent' => 'Mozilla/5.0 (Macintosh) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
            'ip_hash' => hash('sha256', 'legend'),
            'is_direct_url' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}");

        $response->assertStatus(200);
        $response->assertSee('data-chart="browsers"', escape: false);
        $response->assertSee('Chrome');
    }

    public function test_settings_page_renders_domain_preferences(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/settings')
            ->assertStatus(200)
            ->assertSee('Domain Preferences', escape: false)
            ->assertSee('Default Domain', escape: false)
            ->assertSee('Domain Order', escape: false);
    }

    public function test_user_can_set_default_domain_and_reorder_domains(): void
    {
        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $ytDomain = Domain::where('hostname', 'href.yt')->firstOrFail();

        $component = Livewire::actingAs($this->user)
            ->test(SettingsForm::class);

        $initialOrder = $component->get('domain_order');
        $this->assertContains($hrefDomain->id, $initialOrder);

        // Move a domain and set default
        $component->call('moveDomain', $ytDomain->id, 'up')
            ->set('default_domain_id', $hrefDomain->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true);

        $fresh = $this->user->fresh();
        $this->assertSame($hrefDomain->id, $fresh->default_domain_id);
        $this->assertSame($component->get('domain_order'), $fresh->domain_order);
    }

    public function test_user_can_reset_domain_order(): void
    {
        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $clickedDomain = Domain::where('hostname', 'clicked.at')->firstOrFail();

        $this->user->update([
            'domain_order' => [$hrefDomain->id, $clickedDomain->id],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SettingsForm::class)
            ->call('resetDomainOrder')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $this->user->fresh();
        $expectedOrder = $this->user->availableDomains()->sortBy('hostname', SORT_NATURAL | SORT_FLAG_CASE)->pluck('id')->values()->all();
        $this->assertSame($expectedOrder, $fresh->domain_order);
    }

    public function test_link_form_preselects_user_default_domain(): void
    {
        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->user->update(['default_domain_id' => $hrefDomain->id]);

        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->assertSet('domain_id', $hrefDomain->id);
    }

    public function test_link_form_orders_domains_according_to_user_preference(): void
    {
        $ytDomain = Domain::where('hostname', 'href.yt')->firstOrFail();
        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $this->user->update([
            'domain_order' => [$ytDomain->id, $hrefDomain->id],
        ]);

        $domains = $this->user->availableDomains();
        $this->assertSame($ytDomain->id, $domains->first()->id);
        $this->assertSame($hrefDomain->id, $domains->values()->get(1)->id);

        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->assertSeeHtml($ytDomain->hostname)
            ->assertSeeHtml($hrefDomain->hostname);
    }

    public function test_public_dashboard_domains_are_available_on_dash(): void
    {
        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();

        // 1. Link form creates link on href.nz from dash
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/pub-on-dash')
            ->set('domain_id', $hrefDomain->id)
            ->set('slug', 'pubondash1')
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('slug', 'pubondash1')->firstOrFail();
        $this->assertSame($hrefDomain->id, $link->domain_id);

        // 2. Link is accessible on dash.ternis.link
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}")
            ->assertOk()
            ->assertSee('pubondash1');

        // 3. Link appears in dash links table
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->assertSee('pubondash1');
    }

    public function test_link_import_uses_user_default_domain_when_unspecified(): void
    {
        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->user->update(['default_domain_id' => $hrefDomain->id]);

        Livewire::actingAs($this->user)
            ->test(LinkImport::class)
            ->set('csv', "https://example.com/def-test,,,,\n")
            ->call('import')
            ->assertHasNoErrors();

        $imported = Link::where('destination_url', 'https://example.com/def-test')->firstOrFail();
        $this->assertSame($hrefDomain->id, $imported->domain_id);
    }
}
