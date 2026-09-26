<?php

namespace Tests\Feature;

use App\Livewire\Admin\ActivityLogTable;
use App\Livewire\Admin\LinkModeration;
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

class TableColumnsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $admin;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();

        Link::create([
            'slug' => 'coltest1',
            'destination_url' => 'https://example.com/coltest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_hidden_column_disappears_and_persists_in_db(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->assertSee('Destination URL', escape: false)
            ->call('toggleColumn', 'destination')
            ->assertSee('Destination URL', escape: false) // still listed in customizer
            ->assertDontSee('https://example.com/coltest', escape: false);

        $this->assertSame(['dashboard.links' => [
            'hidden' => ['destination'],
            'order' => ['slug', 'destination', 'domain', 'clicks', 'status', 'created'],
        ]], $this->user->fresh()->table_columns);

        // Survives a fresh component instance (reload).
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->assertDontSee('https://example.com/coltest', escape: false)
            ->assertSee('coltest1', escape: false);
    }

    public function test_column_order_is_applied_and_persisted(): void
    {
        $test = Livewire::actingAs($this->user)->test(LinkTable::class);

        // Header markers are unique to the table head (the customizer
        // panel has no wire:click sort markers).
        $html = $test->html();
        $this->assertLessThan(
            strpos($html, "sort('created_at')"),
            strpos($html, "sort('slug')")
        );

        $test->call('moveColumn', 'slug', 'down')
            ->call('moveColumn', 'slug', 'down')
            ->call('moveColumn', 'slug', 'down')
            ->call('moveColumn', 'slug', 'down')
            ->call('moveColumn', 'slug', 'down');

        $order = $this->user->fresh()->table_columns['dashboard.links']['order'];
        $this->assertSame('slug', $order[array_key_last($order)]);

        $html = $test->html();
        $this->assertLessThan(
            strpos($html, "sort('slug')"),
            strpos($html, "sort('created_at')")
        );
    }

    public function test_cannot_hide_all_columns(): void
    {
        $test = Livewire::actingAs($this->user)->test(LinkTable::class);

        foreach (['slug', 'destination', 'domain', 'clicks', 'status', 'created'] as $column) {
            $test->call('toggleColumn', $column);
        }

        // Last hide is refused: at least one column stays visible.
        $hidden = $this->user->fresh()->table_columns['dashboard.links']['hidden'];
        $this->assertCount(5, $hidden);
        $test->assertDontSee('coltest1', escape: false)
            ->assertSee('Created', escape: false);
    }

    public function test_unknown_column_keys_are_ignored(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->call('toggleColumn', 'nope')
            ->call('moveColumn', 'nope', 'up')
            ->assertHasNoErrors();

        $this->assertNull($this->user->fresh()->table_columns);
    }

    public function test_preferences_are_isolated_per_table(): void
    {
        Livewire::actingAs($this->admin)
            ->test(LinkTable::class)
            ->call('toggleColumn', 'domain');

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->assertSee('href.nz', escape: false);

        $prefs = $this->admin->fresh()->table_columns;
        $this->assertArrayHasKey('dashboard.links', $prefs);
        $this->assertArrayNotHasKey('admin.links', $prefs);
    }

    public function test_reset_columns_restores_defaults(): void
    {
        $test = Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->call('toggleColumn', 'domain')
            ->call('moveColumn', 'created', 'up');

        $this->assertNotEmpty($this->user->fresh()->table_columns['dashboard.links']['hidden']);

        $test->call('resetColumns')
            ->assertSee('href.nz', escape: false)
            ->assertSee('https://example.com/coltest', escape: false);

        $this->assertSame(
            ['hidden' => [], 'order' => ['slug', 'destination', 'domain', 'clicks', 'status', 'created']],
            $this->user->fresh()->table_columns['dashboard.links']
        );
    }

    public function test_admin_activity_table_supports_column_prefs(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ActivityLogTable::class)
            ->assertSee('Columns', escape: false)
            ->call('toggleColumn', 'owner')
            ->assertHasNoErrors();

        $this->assertSame(['owner'], $this->admin->fresh()->table_columns['admin.activity']['hidden']);
    }
}
