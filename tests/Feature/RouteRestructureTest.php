<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RouteRestructureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_legacy_dashboard_prefix_redirects_permanently(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/dashboard/links')
            ->assertStatus(301)
            ->assertRedirect('http://dash.ternis.link/links');

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/dashboard')
            ->assertStatus(301)
            ->assertRedirect('http://dash.ternis.link');
    }

    public function test_ternis_link_dashboard_redirects_to_dashboard_host(): void
    {
        $this->get('http://ternis.link/dashboard')
            ->assertStatus(302)
            ->assertRedirect('http://dash.ternis.link');

        $this->get('http://ternis.link/dashboard/links?tab=active')
            ->assertStatus(302)
            ->assertRedirect('http://dash.ternis.link/links?tab=active');

        $this->actingAs($this->user)
            ->get('http://ternis.link/dashboard')
            ->assertStatus(302)
            ->assertRedirect('http://dash.ternis.link');
    }

    public function test_admin_path_redirects_to_admin_host(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/admin/users?page=2')
            ->assertStatus(302)
            ->assertRedirect('https://admin.ternis.link/users?page=2');

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/admin')
            ->assertStatus(302)
            ->assertRedirect('https://admin.ternis.link');
    }

    public function test_new_alias_serves_create_page(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/new')
            ->assertStatus(200)
            ->assertSee('Create Short Link', escape: false);
    }

    public function test_dashboard_home_lives_at_domain_root(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/')
            ->assertStatus(200)
            ->assertSee('Dashboard', escape: false);
    }

    public function test_links_index_uses_quick_create_modal(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links')
            ->assertStatus(200)
            ->assertSee('open-link-creator', escape: false)
            ->assertSee('New Short Link', escape: false);
    }

    public function test_link_form_modal_mode_closes_and_notifies(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class, ['modal' => true])
            ->assertSee('close-link-creator', escape: false)
            ->assertDontSee('Cancel')
            ->set('destination_url', 'https://example.com/modal-create')
            ->call('create')
            ->assertHasNoErrors()
            ->assertDispatched('link-created');

        $this->assertDatabaseHas('links', ['destination_url' => 'https://example.com/modal-create']);
    }

    public function test_link_form_page_mode_keeps_cancel_and_stays_quiet(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->assertSee('Cancel')
            ->set('destination_url', 'https://example.com/page-create')
            ->call('create')
            ->assertHasNoErrors()
            ->assertNotDispatched('link-created');
    }

    public function test_dashboard_layout_has_create_link_modal_across_all_pages(): void
    {
        foreach (['http://dash.ternis.link/', 'http://dash.ternis.link/links', 'http://dash.ternis.link/api-keys', 'http://dash.ternis.link/domains', 'http://dash.ternis.link/settings'] as $url) {
            $this->actingAs($this->user)
                ->get($url)
                ->assertStatus(200)
                ->assertSee('open-link-creator', escape: false)
                ->assertSee('New Short Link', escape: false);
        }
    }

    public function test_link_form_modal_mode_can_create_another_and_resets(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LinkForm::class, ['modal' => true])
            ->set('destination_url', 'https://example.com/multi-modal')
            ->call('create')
            ->assertSee('Link created successfully!')
            ->assertSee('Copy')
            ->assertDispatched('link-created');

        $component->call('createAnother')
            ->assertDontSee('Link created successfully!');

        $component->set('destination_url', 'https://example.com/second-modal')
            ->call('create')
            ->assertSee('Link created successfully!')
            ->dispatch('open-link-creator')
            ->assertDontSee('Link created successfully!');
    }
}
