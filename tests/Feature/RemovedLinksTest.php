<?php

namespace Tests\Feature;

use App\Livewire\Admin\LinkModeration;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RemovedLinksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Domain $domain;

    private Link $link;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->link = Link::create([
            'slug' => 'toremove1',
            'destination_url' => 'https://example.com/toremove',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_remove_and_restore_via_moderation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->call('remove', $this->link->id)
            ->assertHasNoErrors();

        $this->assertTrue($this->link->fresh()->is_removed);

        Livewire::actingAs($this->admin)
            ->test(LinkModeration::class)
            ->set('status', 'removed')
            ->assertSee('toremove1', escape: false)
            ->assertSee('Removed', escape: false)
            ->call('restore', $this->link->id)
            ->assertHasNoErrors();

        $this->assertFalse($this->link->fresh()->is_removed);
    }

    public function test_removed_link_stops_resolving_publicly(): void
    {
        $this->link->update(['is_removed' => true]);

        $this->get('http://href.nz/toremove1')->assertNotFound();
    }

    public function test_removed_link_hidden_from_user_dashboard(): void
    {
        $this->link->update(['is_removed' => true]);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$this->link->id}")
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link')
            ->assertStatus(200)
            ->assertViewHas('stats', fn ($stats) => $stats['total_links'] === 0);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Dashboard\LinkTable::class)
            ->assertDontSee('toremove1', escape: false);
    }

    public function test_removed_link_hidden_from_api(): void
    {
        $this->link->update(['is_removed' => true]);

        // API list excludes removed links for owner and admin scope.
        $list = $this->getJson('http://links.t-api.de/v1/links', $this->headersFor($this->user));
        $list->assertStatus(200);
        $this->assertStringNotContainsString('toremove1', $list->getContent());

        $adminList = $this->getJson('http://links.t-api.de/v1/links', $this->headersFor($this->admin));
        $adminList->assertStatus(200);
        $this->assertStringNotContainsString('toremove1', $adminList->getContent());
    }

    private function headersFor(User $user): array
    {
        $raw = 'tl_'.\Illuminate\Support\Str::random(48);
        \App\Models\ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);

        return ['Authorization' => "Bearer {$raw}", 'Accept' => 'application/json'];
    }

    public function test_non_admin_cannot_remove_via_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkModeration::class)
            ->assertForbidden();

        $this->assertFalse($this->link->fresh()->is_removed);
    }
}
