<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RedirectCacheTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private LinkService $links;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->links = app(LinkService::class);
    }

    private function makeLink(string $slug, string $url = 'https://example.com/cached'): Link
    {
        return Link::create([
            'slug' => $slug,
            'destination_url' => $url,
            'domain_id' => $this->domain->id,
            'is_active' => true,
        ]);
    }

    public function test_resolve_caches_hit_and_second_lookup_avoids_db(): void
    {
        $link = $this->makeLink('cache-hit-1');

        $first = $this->links->resolveSlug('cache-hit-1', $this->domain);
        $this->assertNotNull($first);
        $this->assertTrue(Cache::has(Link::cacheKey($this->domain->id, 'cache-hit-1')));

        // Rewrite the row behind the cache's back (query builder skips
        // model events, so the cached copy goes stale on purpose).
        DB::table('links')->where('id', $link->id)->update(['destination_url' => 'https://example.com/stale']);

        $second = $this->links->resolveSlug('cache-hit-1', $this->domain);
        $this->assertEquals('https://example.com/cached', $second->destination_url);
    }

    public function test_model_update_invalidates_cache(): void
    {
        $link = $this->makeLink('cache-inv-1');

        $this->get('http://href.nz/cache-inv-1')->assertRedirect('https://example.com/cached');
        $this->assertTrue(Cache::has(Link::cacheKey($this->domain->id, 'cache-inv-1')));

        $link->update(['destination_url' => 'https://example.com/fresh']);

        $resolved = $this->links->resolveSlug('cache-inv-1', $this->domain);
        $this->assertEquals('https://example.com/fresh', $resolved->destination_url);
    }

    public function test_deactivation_via_http_is_visible_immediately(): void
    {
        $link = $this->makeLink('cache-deact-1');

        $this->get('http://href.nz/cache-deact-1')->assertStatus(302);

        $link->update(['is_active' => false]);

        $this->get('http://href.nz/cache-deact-1')->assertStatus(404);
    }

    public function test_expired_link_falls_through_to_404(): void
    {
        $link = $this->makeLink('cache-exp-1');

        $this->get('http://href.nz/cache-exp-1')->assertStatus(302);
        $this->assertTrue(Cache::has(Link::cacheKey($this->domain->id, 'cache-exp-1')));

        $link->update(['expires_at' => now()->subMinute()]);

        $this->assertNull($this->links->resolveSlug('cache-exp-1', $this->domain));
        $this->get('http://href.nz/cache-exp-1')->assertStatus(404);
    }

    public function test_cleanup_command_invalidates_cached_expired_links(): void
    {
        $link = $this->makeLink('cache-clean-1');

        // Prime the cache while the link is still active.
        $this->assertNotNull($this->links->resolveSlug('cache-clean-1', $this->domain));

        // Expire behind the cache's back, then run the bulk cleanup
        // (query-builder path that must invalidate explicitly).
        DB::table('links')->where('id', $link->id)->update(['expires_at' => now()->subDay()->toDateTimeString()]);
        Cache::has(Link::cacheKey($this->domain->id, 'cache-clean-1'));

        $this->artisan('links:deactivate-expired')->assertSuccessful();

        $this->assertFalse($link->fresh()->is_active);
        $this->assertFalse(Cache::has(Link::cacheKey($this->domain->id, 'cache-clean-1')));
        $this->assertNull($this->links->resolveSlug('cache-clean-1', $this->domain));
    }

    public function test_cache_is_scoped_per_domain(): void
    {
        $other = Domain::where('hostname', 'href.re')->first();

        Link::create([
            'slug' => 'shared-slug',
            'destination_url' => 'https://example.com/nz',
            'domain_id' => $this->domain->id,
            'is_active' => true,
        ]);
        Link::create([
            'slug' => 'shared-slug',
            'destination_url' => 'https://example.com/re',
            'domain_id' => $other->id,
            'is_active' => true,
        ]);

        $nz = $this->links->resolveSlug('shared-slug', $this->domain);
        $re = $this->links->resolveSlug('shared-slug', $other);

        $this->assertEquals('https://example.com/nz', $nz->destination_url);
        $this->assertEquals('https://example.com/re', $re->destination_url);
    }

    public function test_misses_are_not_cached(): void
    {
        $this->assertNull($this->links->resolveSlug('never-created', $this->domain));
        $this->assertFalse(Cache::has(Link::cacheKey($this->domain->id, 'never-created')));

        // A later-created slug must resolve immediately (no stale 404).
        $this->makeLink('never-created');
        $this->assertNotNull($this->links->resolveSlug('never-created', $this->domain));
    }
}
