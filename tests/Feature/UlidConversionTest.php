<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises the int → ULID conversion migration against a real
 * legacy dataset: replays the original migrations, inserts
 * integer-keyed rows across every table, runs only the conversion,
 * and proves nothing is lost and relations still resolve.
 */
class UlidConversionTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $legacyMigrations = [
        '2026_09_18_000001_create_plans_table.php',
        '2026_09_18_000002_create_users_table.php',
        '2026_09_18_000003_create_oauth_identities_table.php',
        '2026_09_18_000004_create_domains_table.php',
        '2026_09_18_000005_create_links_table.php',
        '2026_09_18_000006_create_clicks_table.php',
        '2026_09_18_000007_create_api_versions_table.php',
        '2026_09_18_000008_create_api_keys_table.php',
        '2026_09_23_000009_add_verification_token_to_domains_table.php',
        '2026_09_23_000010_add_creator_ip_hash_to_links_table.php',
        '2026_09_23_000011_create_error_encounters_table.php',
        '2026_09_24_000012_add_dashboard_preferences_to_users_table.php',
    ];

    /** @var list<string> */
    private array $tables = [
        'clicks', 'api_keys', 'error_encounters', 'oauth_identities',
        'links', 'sessions', 'domains', 'users', 'plans', 'api_versions',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase migrated the ULID schema — tear it down so we
        // can replay the legacy integer chain from scratch.
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        foreach ($this->legacyMigrations as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    public function test_conversion_preserves_rows_and_relations(): void
    {
        $this->seedLegacyDataset();

        (require database_path('migrations/2026_09_24_000013_convert_ids_to_ulids.php'))->up();

        // Every surrogate id is now a 26-char ULID string.
        foreach (['plans', 'users', 'oauth_identities', 'domains', 'links', 'clicks', 'api_keys', 'error_encounters'] as $table) {
            $ids = DB::table($table)->pluck('id');
            $this->assertNotEmpty($ids, "expected rows in {$table}");
            foreach ($ids as $id) {
                $this->assertIsString($id);
                $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', $id, "bad ULID in {$table}");
            }
            $this->assertCount($ids->unique()->count(), $ids, "duplicate ids in {$table}");
        }

        // Row counts preserved.
        $this->assertSame(1, DB::table('plans')->count());
        $this->assertSame(2, DB::table('users')->count());
        $this->assertSame(2, DB::table('domains')->count());
        $this->assertSame(3, DB::table('links')->count());
        $this->assertSame(2, DB::table('clicks')->count());
        $this->assertSame(1, DB::table('api_keys')->count());
        $this->assertSame(1, DB::table('oauth_identities')->count());
        $this->assertSame(1, DB::table('error_encounters')->count());
        $this->assertSame(1, DB::table('sessions')->count());

        // Relations resolve through the remapped keys.
        $link = Link::with(['domain', 'user'])->where('slug', 'legacy-one')->firstOrFail();
        $this->assertSame('links.example.com', $link->domain->hostname);
        $this->assertSame('legacy@example.com', $link->user->email);
        $this->assertSame(2, $link->user->links()->count());

        $oldLink = Link::where('slug', 'legacy-old')->firstOrFail();
        $this->assertSame(2, $oldLink->clicks()->count());

        $click = Click::firstOrFail();
        $this->assertSame($oldLink->id, $click->link_id);

        // Guest link kept its null user; session kept its id, user remapped.
        $this->assertNull(Link::where('slug', 'legacy-guest')->firstOrFail()->user_id);
        $session = DB::table('sessions')->first();
        $this->assertSame('sess-1', $session->id);
        $this->assertSame($link->user_id, $session->user_id);

        // The serialized auth identifier inside the session payload was
        // rewritten too, so the login survives the conversion.
        $payload = unserialize($session->payload, ['allowed_classes' => false]);
        $this->assertSame($link->user_id, $payload['login_web_58c311ff3e2a4b2b8f111c77b0e4b1b']);

        // Chronological order survives: the older link sorts first.
        $ordered = Link::orderBy('id')->pluck('slug')->all();
        $this->assertSame(['legacy-old', 'legacy-one', 'legacy-guest'], $ordered);

        // New rows get ULIDs from the model layer.
        $fresh = Link::create([
            'slug' => 'post-convert',
            'destination_url' => 'https://example.com/new',
            'domain_id' => $link->domain_id,
            'user_id' => $link->user_id,
        ]);
        $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', $fresh->id);
    }

    public function test_rollback_restores_integer_keys(): void
    {
        $this->seedLegacyDataset();

        $migration = require database_path('migrations/2026_09_24_000013_convert_ids_to_ulids.php');
        $migration->up();
        $migration->down();

        $this->assertSame(3, DB::table('links')->count());
        $this->assertSame(2, DB::table('clicks')->count());

        $link = Link::with(['domain', 'user'])->where('slug', 'legacy-one')->firstOrFail();
        $this->assertIsInt($link->id);
        $this->assertSame('links.example.com', $link->domain->hostname);
        $this->assertSame('legacy@example.com', $link->user->email);
        $this->assertSame(2, Link::where('slug', 'legacy-old')->firstOrFail()->clicks()->count());
    }

    private function seedLegacyDataset(): void
    {
        DB::table('api_versions')->insert(['version' => 1, 'status' => 'active']);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'legacy-plan', 'min_slug_length' => 3, 'custom_subdomain' => true,
            'rate_limit_per_minute' => 60, 'max_links_per_day' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'sso_sub' => 'sso-legacy-1', 'name' => 'Legacy', 'email' => 'legacy@example.com',
            'role' => 'user', 'plan_id' => $planId, 'nav_layout' => 'side', 'theme' => 'system',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherId = DB::table('users')->insertGetId([
            'sso_sub' => 'sso-legacy-2', 'name' => 'Other', 'email' => 'other@example.com',
            'role' => 'user', 'plan_id' => null, 'nav_layout' => 'top', 'theme' => 'dark',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('oauth_identities')->insert([
            'user_id' => $userId, 'access_token' => 'tok', 'token_expires_at' => now()->addHour(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $domainId = DB::table('domains')->insertGetId([
            'hostname' => 'links.example.com', 'user_id' => $userId, 'verification_token' => 'tok123',
            'type' => 'partner', 'is_active' => true, 'verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $systemId = DB::table('domains')->insertGetId([
            'hostname' => 'sys.example.com', 'user_id' => null, 'type' => 'public',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Deliberately out-of-order timestamps: conversion must keep
        // chronological (not insertion) order in the new ULIDs.
        $oldLink = DB::table('links')->insertGetId([
            'slug' => 'legacy-old', 'destination_url' => 'https://example.com/old',
            'domain_id' => $domainId, 'user_id' => $userId,
            'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);
        DB::table('links')->insert([
            'slug' => 'legacy-one', 'destination_url' => 'https://example.com/one',
            'domain_id' => $domainId, 'user_id' => $userId, 'click_count' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('links')->insert([
            'slug' => 'legacy-guest', 'destination_url' => 'https://example.com/guest',
            'domain_id' => $systemId, 'user_id' => null, 'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('clicks')->insert([
            ['link_id' => $oldLink, 'referrer' => 'https://referrer.example/', 'created_at' => now()],
            ['link_id' => $oldLink, 'referrer' => null, 'created_at' => now()],
        ]);

        DB::table('api_keys')->insert([
            'user_id' => $userId, 'key_hash' => hash('sha256', 'tl_test'), 'key_prefix' => 'tl_test',
            'api_version' => 1, 'name' => 'Legacy key',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('error_encounters')->insert([
            'http_code' => 404, 'exception_class' => 'RuntimeException', 'method' => 'GET',
            'host' => 'href.nz', 'path' => '/nope', 'user_id' => $otherId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => 'sess-1', 'user_id' => $userId, 'ip_address' => '127.0.0.1',
            'payload' => serialize(['login_web_58c311ff3e2a4b2b8f111c77b0e4b1b' => $userId, '_token' => 'abc']),
            'last_activity' => time(),
        ]);
    }
}
