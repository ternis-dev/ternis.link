<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ApiKeyManager;
use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Dashboard\LinkTable;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'clicked.at')->first();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('http://dash.ternis.link');
        $response->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee($this->user->name);
    }

    public function test_authenticated_user_can_view_links_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links');

        $response->assertStatus(200);
        $response->assertSee('Your Links');
    }

    public function test_authenticated_user_can_view_create_link_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links/create');

        $response->assertStatus(200);
        $response->assertSee('Create Short Link');
    }

    public function test_authenticated_user_can_view_link_show_page(): void
    {
        $link = Link::create([
            'slug' => 'myshowslug',
            'destination_url' => 'https://ternis.dev',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}");

        $response->assertStatus(200);
        $response->assertSee('myshowslug');
    }

    public function test_authenticated_user_can_view_api_keys_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/api-keys');

        $response->assertStatus(200);
        $response->assertSee('API Keys');
    }

    public function test_livewire_link_form_creates_link(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://ternis.dev/test-livewire')
            ->set('domain_id', $this->domain->id)
            ->set('slug', 'livewire_slug')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('links', [
            'slug' => 'livewire_slug',
            'destination_url' => 'https://ternis.dev/test-livewire',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_livewire_api_key_manager_creates_and_revokes_key(): void
    {
        $test = Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'My Automated Key')
            ->call('createKey')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('api_keys', [
            'name' => 'My Automated Key',
            'user_id' => $this->user->id,
        ]);

        $key = $this->user->apiKeys()->first();
        $this->assertNotNull($key);
        $this->assertTrue($key->isValid());

        $test->call('revokeKey', $key->id);
        $this->assertFalse($key->fresh()->isValid());
    }

    public function test_link_table_empty_state_and_filter_reset(): void
    {
        Link::create([
            'slug' => 'sample-findable',
            'destination_url' => 'https://example.com/findable',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->assertSee('sample-findable')
            ->set('search', 'non-existent-query-12345')
            ->assertDontSee('sample-findable')
            ->assertSee('No short links match your current search or filter.')
            ->assertSee('Clear all filters');

        $test->call('resetFilters')
            ->assertSet('search', '')
            ->assertSee('sample-findable');
    }

    public function test_sortable_columns_are_buttons_with_aria_sort(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->assertSee('aria-sort="descending"', escape: false)
            ->assertSee('<button type="button" wire:click="sort(', escape: false)
            ->call('sort', 'slug')
            ->assertSee('aria-sort="descending"', escape: false);
    }

    public function test_overview_shows_getting_started_until_first_link(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link')
            ->assertOk()
            ->assertSee('Get started in three steps', escape: false)
            ->assertSee('title="Keyboard shortcut: C"', escape: false);

        Link::create([
            'slug' => 'first-ever',
            'destination_url' => 'https://example.com/first',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link')
            ->assertOk()
            ->assertDontSee('Get started in three steps', escape: false);
    }
}
