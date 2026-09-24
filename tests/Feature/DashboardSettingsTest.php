<?php

namespace Tests\Feature;

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
        $this->get('http://dash.ternis.link/dashboard/settings')
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_settings_page_renders_with_defaults(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/dashboard/settings')
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
            ->assertSet('saved', true);

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
            ->get('http://dash.ternis.link/dashboard')
            ->assertStatus(200)
            ->assertSee('data-nav="side"', escape: false)
            ->assertDontSee('data-nav="top"', escape: false);
    }

    public function test_dashboard_uses_top_nav_when_preferred(): void
    {
        $this->user->update(['nav_layout' => 'top']);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/dashboard')
            ->assertStatus(200)
            ->assertSee('data-nav="top"', escape: false)
            ->assertDontSee('data-nav="side"', escape: false)
            ->assertSee('data-nav="top" class="sticky top-4 z-30', escape: false)
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
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
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
            ->get("http://dash.ternis.link/dashboard/links/{$link->id}");

        $response->assertStatus(200);
        $response->assertSee('data-chart="clicks"', escape: false);
        $response->assertSee('data-chart-labels', escape: false);
        $response->assertSee('Clicks over time');
    }

    public function test_analytics_browsers_chart_keeps_html_legend(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
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
            ->get("http://dash.ternis.link/dashboard/links/{$link->id}");

        $response->assertStatus(200);
        $response->assertSee('data-chart="browsers"', escape: false);
        $response->assertSee('Chrome');
    }
}
