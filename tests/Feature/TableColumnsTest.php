<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ActivityTable;
use App\Livewire\Dashboard\ApiKeyManager;
use App\Livewire\Dashboard\DomainManager;
use App\Livewire\Dashboard\LinkAnalytics;
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

/**
 * Every table on the dashboard exposes the Cloudflare-style column
 * customizer: toggle visibility, reorder, persist per user.
 */
class TableColumnsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_activity_table_renders_and_hides_columns(): void
    {
        Livewire::actingAs($this->user)
            ->test(ActivityTable::class)
            ->assertSee('Visible columns')
            ->call('toggleColumn', 'by', 'dashboard.activity')
            ->assertHasNoErrors();

        $this->assertSame(['by'], $this->user->fresh()->table_columns['dashboard.activity']['hidden']);
    }

    public function test_api_key_table_renders_and_hides_columns(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->assertSee('Visible columns')
            ->call('toggleColumn', 'prefix', 'dashboard.api-keys')
            ->assertHasNoErrors();

        $this->assertSame(['prefix'], $this->user->fresh()->table_columns['dashboard.api-keys']['hidden']);
    }

    public function test_domain_tables_are_customized_independently(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DomainManager::class)
            ->assertSee('Visible columns')
            ->call('toggleColumn', 'status', DomainManager::OWN_TABLE)
            ->call('toggleColumn', 'type', DomainManager::SYSTEM_TABLE);

        $prefs = $this->user->fresh()->table_columns;

        $this->assertSame(['status'], $prefs[DomainManager::OWN_TABLE]['hidden']);
        $this->assertSame(['type'], $prefs[DomainManager::SYSTEM_TABLE]['hidden']);
    }

    public function test_link_analytics_table_columns_are_customizable(): void
    {
        $link = Link::create([
            'slug' => 'analytic',
            'destination_url' => 'https://example.com/a',
            'domain_id' => Domain::where('hostname', 'href.nz')->first()->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $link])
            ->call('toggleColumn', 'ip_hash', 'dashboard.analytics.recent-clicks')
            ->assertHasNoErrors();

        $this->assertSame(
            ['ip_hash'],
            $this->user->fresh()->table_columns['dashboard.analytics.recent-clicks']['hidden']
        );
    }

    public function test_link_table_columns_are_customizable(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->call('toggleColumn', 'domain', 'dashboard.links')
            ->assertHasNoErrors();

        $this->assertSame(['domain'], $this->user->fresh()->table_columns['dashboard.links']['hidden']);
    }

    public function test_columns_can_be_reordered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ActivityTable::class)
            ->call('moveColumn', 'by', 'up', 'dashboard.activity')
            ->assertHasNoErrors();

        $order = $this->user->fresh()->table_columns['dashboard.activity']['order'];

        // 'by' was the last column, one step up swaps it with 'subject'.
        $this->assertSame(['when', 'action', 'by', 'subject'], $order);
    }

    public function test_reset_restores_defaults(): void
    {
        Livewire::actingAs($this->user)
            ->test(ActivityTable::class)
            ->call('toggleColumn', 'by', 'dashboard.activity')
            ->call('resetColumns', 'dashboard.activity')
            ->assertHasNoErrors();

        $prefs = $this->user->fresh()->table_columns['dashboard.activity'];

        $this->assertSame([], $prefs['hidden']);
        $this->assertSame(['when', 'action', 'subject', 'by'], $prefs['order']);
    }

    public function test_the_last_visible_column_cannot_be_hidden(): void
    {
        $component = Livewire::actingAs($this->user)->test(ActivityTable::class);

        foreach (['when', 'action', 'subject'] as $column) {
            $component->call('toggleColumn', $column, 'dashboard.activity');
        }

        $component->call('toggleColumn', 'by', 'dashboard.activity');

        $prefs = $this->user->fresh()->table_columns['dashboard.activity'];

        // Three hidden, one still visible — the fourth toggle is a no-op.
        $this->assertCount(3, $prefs['hidden']);
    }

    public function test_unknown_columns_are_ignored(): void
    {
        Livewire::actingAs($this->user)
            ->test(ActivityTable::class)
            ->call('toggleColumn', 'not_a_column', 'dashboard.activity')
            ->assertHasNoErrors();

        $this->assertNull($this->user->fresh()->table_columns['dashboard.activity'] ?? null);
    }
}
