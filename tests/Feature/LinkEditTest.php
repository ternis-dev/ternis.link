<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkEditForm;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LinkEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private Link $link;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->link = Link::create([
            'slug' => 'edit-me-1',
            'destination_url' => 'https://example.com/original',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    private function headersFor(User $user): array
    {
        $raw = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);

        return ['Authorization' => "Bearer {$raw}"];
    }

    public function test_edit_page_renders_for_owner(): void
    {
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/dashboard/links/{$this->link->id}/edit")
            ->assertStatus(200)
            ->assertSee('Edit Short Link', escape: false)
            ->assertSee('id="destination_url"', escape: false)
            ->assertSee('href.nz/edit-me-1', escape: false);
    }

    public function test_edit_page_404s_for_strangers(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/dashboard/links/{$this->link->id}/edit")
            ->assertStatus(404);
    }

    public function test_owner_can_update_destination(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->set('destination_url', 'https://example.com/updated')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true)
            ->assertSee('Link updated.', escape: false);

        $this->assertSame('https://example.com/updated', $this->link->fresh()->destination_url);
    }

    public function test_invalid_destination_is_rejected(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->set('destination_url', 'not-a-url')
            ->call('save')
            ->assertHasErrors('destination_url');

        $this->assertSame('https://example.com/original', $this->link->fresh()->destination_url);
    }

    public function test_junk_destination_is_rejected(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->set('destination_url', 'https://phpinfo.php')
            ->call('save')
            ->assertHasErrors('destination_url')
            ->assertSee('Scanner-style probes', escape: false);

        $this->assertSame('https://example.com/original', $this->link->fresh()->destination_url);
    }

    public function test_unchanged_past_expiry_saves_but_new_past_expiry_fails(): void
    {
        $this->link->update(['expires_at' => now()->subDay()]);

        // Untouched expiry (even past) is fine.
        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->call('save')
            ->assertHasNoErrors();

        // A newly entered past date is rejected.
        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->set('expires_at', now()->subHour()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors('expires_at');
    }

    public function test_owner_can_toggle_active(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->link->fresh()->is_active);
    }

    public function test_admin_can_edit_foreign_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get("http://dash.ternis.link/dashboard/links/{$this->link->id}/edit")
            ->assertStatus(200);

        Livewire::actingAs($admin)
            ->test(LinkEditForm::class, ['link' => $this->link])
            ->set('destination_url', 'https://example.com/admin-edit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://example.com/admin-edit', $this->link->fresh()->destination_url);
    }

    public function test_api_rejects_junk_destination_on_update(): void
    {
        $response = $this->putJson(
            "http://links.t-api.de/v1/links/{$this->link->id}",
            ['destination_url' => 'https://info.php.bak'],
            $this->headersFor($this->user)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('destination_url');
        $this->assertSame('https://example.com/original', $this->link->fresh()->destination_url);
    }

    public function test_links_table_links_to_edit_page(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/dashboard/links')
            ->assertStatus(200)
            ->assertSee("/dashboard/links/{$this->link->id}/edit", escape: false);
    }
}
