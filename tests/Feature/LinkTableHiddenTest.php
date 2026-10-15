<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkTable;
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

class LinkTableHiddenTest extends TestCase
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

    private function makeKey(string $name, bool $visible): ApiKey
    {
        $raw = 'tl_'.Str::random(48);

        return ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => ApiKey::hashToken($raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => $name,
            'show_on_dashboard' => $visible,
        ]);
    }

    private function makeLink(string $slug, ?ApiKey $key = null): Link
    {
        return Link::create([
            'slug' => $slug,
            'destination_url' => 'https://example.com/'.$slug,
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'api_key_id' => $key?->id,
            'is_active' => true,
        ]);
    }

    public function test_hidden_key_links_excluded_from_search_by_default(): void
    {
        $hiddenKey = $this->makeKey('Hidden Key', false);
        $this->makeLink('hidden-abc', $hiddenKey);
        $this->makeLink('visible-abc');

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('search', 'abc')
            ->assertSee('visible-abc')
            ->assertDontSee('hidden-abc');
    }

    public function test_include_hidden_checkbox_includes_them_in_search(): void
    {
        $hiddenKey = $this->makeKey('Hidden Key', false);
        $this->makeLink('hidden-abc', $hiddenKey);
        $this->makeLink('visible-abc');

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->set('search', 'abc')
            ->set('includeHidden', true)
            ->assertSee('visible-abc')
            ->assertSee('hidden-abc');
    }

    public function test_checkbox_shown_on_list_but_not_on_per_key_page(): void
    {
        $hiddenKey = $this->makeKey('Hidden Key', false);
        $this->makeLink('hidden-abc', $hiddenKey);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links')
            ->assertStatus(200)
            ->assertSee('Include hidden', escape: false);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/api-keys/{$hiddenKey->id}")
            ->assertStatus(200)
            ->assertSee('hidden-abc')
            ->assertDontSee('Include hidden', escape: false);
    }
}
