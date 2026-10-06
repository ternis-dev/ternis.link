<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkImport;
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

class LinkBulkUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    private function makeLink(string $slug, bool $active = true, ?User $owner = null): Link
    {
        return Link::create([
            'slug' => $slug,
            'destination_url' => 'https://example.com/'.$slug,
            'domain_id' => $this->domain->id,
            'user_id' => ($owner ?? $this->user)->id,
            'is_active' => $active,
        ]);
    }

    public function test_table_bulk_deactivate_and_reactivate(): void
    {
        $a = $this->makeLink('bulk-a01');
        $b = $this->makeLink('bulk-b01');

        $component = Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('selected', [$a->id, $b->id])
            ->call('bulkSetActive', false)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $a->fresh()->is_active);
        $this->assertFalse((bool) $b->fresh()->is_active);
        $component->assertSee('deactivated', escape: false);

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('selected', [$a->id])
            ->call('bulkSetActive', true)
            ->assertHasNoErrors();

        $this->assertTrue((bool) $a->fresh()->is_active);
        $this->assertFalse((bool) $b->fresh()->is_active);
    }

    public function test_table_bulk_ignores_foreign_ids(): void
    {
        $mine = $this->makeLink('bulk-m01');
        $stranger = User::factory()->create();
        $theirs = $this->makeLink('bulk-t01', owner: $stranger);

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('selected', [$mine->id, $theirs->id, '01JXXXXXXXXXXXXXXXXXXXXXXXXX'])
            ->call('bulkSetActive', false)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $mine->fresh()->is_active);
        $this->assertTrue((bool) $theirs->fresh()->is_active);
    }

    public function test_table_actions_dispatch_notify_toasts(): void
    {
        $link = $this->makeLink('bulk-n01');

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->call('deactivate', $link->id)
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Deactivated bulk-n01.', type: 'info');

        $other = $this->makeLink('bulk-n02');

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('selected', [$other->id])
            ->call('bulkSetActive', false)
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: '1 link(s) deactivated.', type: 'success');
    }

    public function test_import_dispatches_notify_toast(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkImport::class)
            ->set('csv', "https://example.com/a,href.nz,toast-a\nnot-a-url")
            ->call('import')
            ->assertDispatched('notify', message: 'Imported 1 link(s), 1 row(s) need attention.', type: 'error');
    }

    public function test_table_bulk_empty_selection_notices(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->call('bulkSetActive', false)
            ->assertSee('No eligible links', escape: false);
    }

    public function test_import_page_requires_login(): void
    {
        $this->get('http://dash.ternis.link/links/import')
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_import_happy_path_with_tags(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkImport::class)
            ->set('csv', "https://example.com/a,href.nz,import-a,,First,launch;marketing\nhttps://example.com/b")
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('links', ['slug' => 'import-a', 'user_id' => $this->user->id]);
        $link = Link::where('slug', 'import-a')->firstOrFail();
        $this->assertSame(['launch', 'marketing'], $link->tags);
        $this->assertSame(2, $this->user->links()->count());
    }

    public function test_import_dry_run_creates_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkImport::class)
            ->set('csv', 'https://example.com/a,href.nz,dry-run-a')
            ->call('dryRunImport')
            ->assertHasNoErrors()
            ->assertSee('Valid.', escape: false);

        $this->assertSame(0, $this->user->links()->count());
    }

    public function test_import_reports_per_row_errors(): void
    {
        $this->makeLink('taken-slug');

        $component = Livewire::actingAs($this->user)
            ->test(LinkImport::class)
            ->set('csv', "not-a-url\nhttps://example.com/x,href.nz,taken-slug\nhttps://example.com/y,nosuchdomain.test,z")
            ->call('import');

        $results = $component->get('results');
        $this->assertCount(3, $results);
        $this->assertTrue(collect($results)->every(fn ($r) => $r['ok'] === false));
        $this->assertSame(1, $this->user->links()->count());
    }

    public function test_import_enforces_row_cap(): void
    {
        $lines = [];
        for ($i = 0; $i < 205; $i++) {
            $lines[] = "https://example.com/{$i}";
        }

        $component = Livewire::actingAs($this->user)
            ->test(LinkImport::class)
            ->set('csv', implode("\n", $lines))
            ->call('import');

        $results = $component->get('results');
        $this->assertTrue(collect($results)->contains(fn ($r) => str_contains($r['message'], 'Row cap')));
        $this->assertLessThanOrEqual(200, $this->user->links()->count());
    }
}
