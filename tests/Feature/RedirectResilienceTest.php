<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\ClickTrackerService;
use Database\Seeders\DomainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RedirectResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DomainSeeder::class);
    }

    protected function tearDown(): void
    {
        // The shared test teardown flushes the default cache.
        config(['cache.default' => 'array']);
        parent::tearDown();
    }

    private function link(array $attributes = []): Link
    {
        return Link::create(array_merge([
            'domain_id' => Domain::where('hostname', 'href.nz')->firstOrFail()->id,
            'slug' => 'resilient',
            'destination_url' => 'https://example.com/destination',
            'is_active' => true,
            'utm_source' => 'newsletter',
        ], $attributes));
    }

    public function test_missing_target_and_analytics_tables_still_redirect_with_query_and_utm(): void
    {
        $this->link();
        Schema::drop('link_targets');
        Schema::drop('clicks');

        $this->get('http://href.nz/resilient?campaign=launch')
            ->assertRedirect('https://example.com/destination?utm_source=newsletter&campaign=launch');
    }

    public function test_queue_or_geolocation_failure_does_not_break_redirect(): void
    {
        $this->link();
        $this->mock(ClickTrackerService::class)->shouldReceive('track')->andThrow(new \RuntimeException('Queue unavailable'));

        $this->get('http://href.nz/resilient')
            ->assertRedirect('https://example.com/destination?utm_source=newsletter');
        $this->get('http://href.nz/url/example.com/direct')->assertRedirect('https://example.com/direct');
    }

    public function test_database_cache_table_is_optional_on_cold_and_warm_requests(): void
    {
        $this->link();
        config(['cache.default' => 'database']);
        Cache::forgetDriver('database');
        Schema::drop('cache');

        $this->get('http://href.nz/resilient')
            ->assertRedirect('https://example.com/destination?utm_source=newsletter');
        $this->get('http://href.nz/resilient')
            ->assertRedirect('https://example.com/destination?utm_source=newsletter');
    }

    public function test_missing_sessions_table_does_not_break_public_redirects_or_unlock_protected_links(): void
    {
        $this->link();
        $this->link(['slug' => 'locked', 'password_hash' => Hash::make('secret')]);
        config(['session.driver' => 'database']);
        app('session')->forgetDrivers();
        Schema::drop('sessions');

        $this->get('http://href.nz/resilient')
            ->assertRedirect('https://example.com/destination?utm_source=newsletter');
        $this->get('http://href.nz/locked')->assertOk()->assertDontSee('https://example.com/destination', false);
    }

    public function test_custom_domain_shortlinks_survive_missing_bio_tables(): void
    {
        $domain = Domain::create([
            'hostname' => 'custom.example',
            'type' => 'partner',
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
            'verified_at' => now(),
        ]);
        $this->link(['domain_id' => $domain->id]);
        Schema::drop('bio_pages');

        $this->get('http://custom.example/resilient')
            ->assertRedirect('https://example.com/destination?utm_source=newsletter');
        $this->get('http://custom.example/unknown')->assertNotFound();
    }

    public function test_missing_tracking_links_table_does_not_break_direct_urls(): void
    {
        Schema::drop('links');

        $this->get('http://href.nz/url/example.com/direct')->assertRedirect('https://example.com/direct');
        $this->get('http://href.nz/go/example.com/direct')->assertRedirect('https://example.com/direct');
    }

    public function test_optional_table_failures_do_not_bypass_link_restrictions(): void
    {
        Schema::drop('link_targets');
        $this->link(['slug' => 'inactive', 'is_active' => false]);
        $this->link(['slug' => 'removed', 'is_removed' => true]);
        $this->link(['slug' => 'expired', 'expires_at' => now()->subMinute()]);
        $this->link(['slug' => 'locked', 'password_hash' => Hash::make('secret')]);

        foreach (['inactive', 'removed', 'expired', 'unknown'] as $slug) {
            $this->get('http://href.nz/'.$slug)->assertNotFound();
        }
        $this->get('http://href.nz/locked')->assertOk()->assertDontSee('https://example.com/destination', false);
    }

    public function test_deployment_check_fails_for_unapplied_migrations(): void
    {
        $this->artisan('deploy:check')->assertSuccessful();
        DB::table('migrations')->where('migration', '2026_10_01_000029_create_link_targets_table')->delete();
        $this->artisan('deploy:check')->assertFailed();
    }
}
