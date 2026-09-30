<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LinksExportTest extends TestCase
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

    public function test_guest_cannot_export_links_catalog(): void
    {
        $this->get('http://dash.ternis.link/links/export')
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_user_can_export_links_as_csv(): void
    {
        Link::create([
            'slug' => 'exportable-slug-1',
            'destination_url' => 'https://example.com/one',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'tags' => ['marketing', 'promo'],
            'description' => 'First campaign',
            'click_count' => 42,
        ]);

        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('slug,short_url,destination_url,domain,api_key,click_count,status,tags,description,created_at,expires_at', $content);
        $this->assertStringContainsString('exportable-slug-1', $content);
        $this->assertStringContainsString('https://example.com/one', $content);
        $this->assertStringContainsString('href.nz', $content);
        $this->assertStringContainsString('Dashboard', $content);
        $this->assertStringContainsString('42', $content);
        $this->assertStringContainsString('"marketing, promo"', $content);
        $this->assertStringContainsString('First campaign', $content);
    }

    public function test_export_excludes_other_users_links(): void
    {
        $otherUser = User::factory()->create();

        Link::create([
            'slug' => 'my-link-export',
            'destination_url' => 'https://example.com/my',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Link::create([
            'slug' => 'other-user-secret-link',
            'destination_url' => 'https://example.com/other-secret',
            'domain_id' => $this->domain->id,
            'user_id' => $otherUser->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links/export');

        $content = $response->streamedContent();
        $this->assertStringContainsString('my-link-export', $content);
        $this->assertStringNotContainsString('other-user-secret-link', $content);
    }

    public function test_export_excludes_removed_links(): void
    {
        Link::create([
            'slug' => 'normal-link',
            'destination_url' => 'https://example.com/normal',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'is_removed' => false,
        ]);

        Link::create([
            'slug' => 'removed-tombstone-link',
            'destination_url' => 'https://example.com/removed',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
            'is_removed' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links/export');

        $content = $response->streamedContent();
        $this->assertStringContainsString('normal-link', $content);
        $this->assertStringNotContainsString('removed-tombstone-link', $content);
    }

    public function test_export_filters_by_tag(): void
    {
        Link::create([
            'slug' => 'alpha-tag-link',
            'destination_url' => 'https://example.com/alpha',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'tags' => ['alpha'],
        ]);

        Link::create([
            'slug' => 'beta-tag-link',
            'destination_url' => 'https://example.com/beta',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'tags' => ['beta'],
        ]);

        $response = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links/export?tag=alpha');

        $content = $response->streamedContent();
        $this->assertStringContainsString('alpha-tag-link', $content);
        $this->assertStringNotContainsString('beta-tag-link', $content);
    }

    public function test_export_filters_by_api_key(): void
    {
        $raw = 'tl_'.Str::random(48);
        $key = ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'CI Deployment',
        ]);

        Link::create([
            'slug' => 'key-linked-item',
            'destination_url' => 'https://example.com/key',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'api_key_id' => $key->id,
            'is_active' => true,
        ]);

        Link::create([
            'slug' => 'manual-dashboard-link',
            'destination_url' => 'https://example.com/manual',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'api_key_id' => null,
            'is_active' => true,
        ]);

        // Filter by API key
        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/export?api_key_id={$key->id}");
        $content = $response->streamedContent();
        $this->assertStringContainsString('key-linked-item', $content);
        $this->assertStringContainsString('CI Deployment', $content);
        $this->assertStringNotContainsString('manual-dashboard-link', $content);

        // Filter by none (dashboard-created)
        $responseNone = $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links/export?api_key_id=none');
        $contentNone = $responseNone->streamedContent();
        $this->assertStringContainsString('manual-dashboard-link', $contentNone);
        $this->assertStringNotContainsString('key-linked-item', $contentNone);
    }

    public function test_links_page_and_api_key_page_display_export_action(): void
    {
        $raw = 'tl_'.Str::random(48);
        $key = ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Production Key',
        ]);

        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/links')
            ->assertOk()
            ->assertSee(route('dashboard.links.export-all'), escape: false)
            ->assertSee('Export CSV', escape: false);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/api-keys/{$key->id}")
            ->assertOk()
            ->assertSee(route('dashboard.links.export-all', ['api_key_id' => $key->id]), escape: false)
            ->assertSee('Export CSV', escape: false);
    }
}
