<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Uid\Ulid;

/**
 * Convert every surrogate auto-increment PK to a ULID, preserving all
 * rows and rewriting foreign keys.
 *
 * Why ULIDs: lexicographically sortable (roughly time-ordered, so
 * `orderByDesc('id')` keeps working), index-friendly, and they leak
 * neither row counts nor creation order (no IDOR enumeration).
 *
 * Untouched: api_versions (natural version-number key, not a surrogate
 * id) and the framework cache/jobs tables. sessions keeps its string
 * PK, but its user_id becomes ULID-capable (database session driver).
 *
 * Strategy (identical on SQLite/MySQL/PgSQL): per table, read all rows,
 * drop the table, recreate it with the ULID schema, re-insert with
 * remapped keys — parents before children. Legacy FK constraints are
 * dropped first on drivers that enforce cross-table constraint-name
 * uniqueness (MySQL); SQLite never enforces those.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->dropLegacyForeignKeys();
        }

        Schema::disableForeignKeyConstraints();

        // Pass 1 — read + remap, parents before children.
        $plans = $this->remap('plans', fn (array $row) => []);
        $planMap = $plans['map'];

        $users = $this->remap('users', fn (array $row) => [
            'plan_id' => $this->mapFk($planMap, $row['plan_id']),
        ]);
        $userMap = $users['map'];

        $sessions = $this->remap('sessions', fn (array $row) => [
            'user_id' => $this->mapFk($userMap, $row['user_id']),
            'payload' => $this->remapSessionPayload($row['payload'], $userMap),
        ], keepId: true);

        $oauth = $this->remap('oauth_identities', fn (array $row) => [
            'user_id' => $userMap[$row['user_id']],
        ]);

        $domains = $this->remap('domains', fn (array $row) => [
            'user_id' => $this->mapFk($userMap, $row['user_id']),
        ]);
        $domainMap = $domains['map'];

        $links = $this->remap('links', fn (array $row) => [
            'domain_id' => $domainMap[$row['domain_id']],
            'user_id' => $this->mapFk($userMap, $row['user_id']),
        ]);
        $linkMap = $links['map'];

        $clicks = $this->remap('clicks', fn (array $row) => [
            'link_id' => $linkMap[$row['link_id']],
        ]);

        $apiKeys = $this->remap('api_keys', fn (array $row) => [
            'user_id' => $userMap[$row['user_id']],
        ]);

        $encounters = $this->remap('error_encounters', fn (array $row) => [
            'user_id' => $this->mapFk($userMap, $row['user_id']),
        ]);

        // Pass 2 — drop, children before parents.
        foreach (['clicks', 'api_keys', 'error_encounters', 'oauth_identities', 'links', 'sessions', 'domains', 'users', 'plans'] as $table) {
            Schema::dropIfExists($table);
        }

        // Pass 3 — recreate with the ULID schema, parents first.
        $this->createPlansTable();
        $this->createUsersTable();
        $this->createSessionsTable();
        $this->createOauthIdentitiesTable();
        $this->createDomainsTable();
        $this->createLinksTable();
        $this->createClicksTable();
        $this->createApiKeysTable();
        $this->createErrorEncountersTable();

        // Pass 4 — re-insert, parents first.
        foreach (['plans' => $plans, 'users' => $users, 'sessions' => $sessions, 'oauth_identities' => $oauth, 'domains' => $domains, 'links' => $links, 'clicks' => $clicks, 'api_keys' => $apiKeys, 'error_encounters' => $encounters] as $table => $data) {
            foreach (array_chunk($data['rows'], 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the conversion: rebuild integer PKs with fresh
     * auto-increment values and remap foreign keys onto them.
     * Key values change — only use for local rollbacks.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->dropLegacyForeignKeys();
        }

        Schema::disableForeignKeyConstraints();

        $read = fn (string $table) => DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();

        $plans = $read('plans');
        $users = $read('users');
        $sessions = $read('sessions');
        $oauth = $read('oauth_identities');
        $domains = $read('domains');
        $links = $read('links');
        $clicks = $read('clicks');
        $apiKeys = $read('api_keys');
        $encounters = $read('error_encounters');

        foreach (['clicks', 'api_keys', 'error_encounters', 'oauth_identities', 'links', 'sessions', 'domains', 'users', 'plans'] as $table) {
            Schema::dropIfExists($table);
        }

        // Original integer schema (abbreviated: PKs + FK types only;
        // remaining columns are recreated 1:1 below).
        $this->createIntPlansTable();
        $this->createIntUsersTable();
        $this->createIntSessionsTable();
        $this->createIntOauthIdentitiesTable();
        $this->createIntDomainsTable();
        $this->createIntLinksTable();
        $this->createIntClicksTable();
        $this->createIntApiKeysTable();
        $this->createIntErrorEncountersTable();

        $fresh = [];
        $nextId = [];
        $assign = function (string $table, array $row, callable $remap) use (&$fresh, &$nextId) {
            $nextId[$table] = ($nextId[$table] ?? 0) + 1;
            $fresh[$table][] = array_merge($row, ['id' => $nextId[$table]], $remap($row, $nextId[$table]));
        };

        foreach ($plans as $row) {
            $assign('plans', $row, fn () => []);
        }
        $planMap = $this->idMap($plans, $fresh['plans'] ?? []);
        foreach ($users as $row) {
            $assign('users', $row, fn ($r) => ['plan_id' => $r['plan_id'] === null ? null : $planMap[$r['plan_id']]]);
        }
        $userMap = $this->idMap($users, $fresh['users'] ?? []);
        foreach ($sessions as $row) {
            $fresh['sessions'][] = array_merge($row, ['user_id' => $row['user_id'] === null ? null : $userMap[$row['user_id']]]);
        }
        foreach ($oauth as $row) {
            $assign('oauth_identities', $row, fn ($r) => ['user_id' => $userMap[$r['user_id']]]);
        }
        foreach ($domains as $row) {
            $assign('domains', $row, fn ($r) => ['user_id' => $r['user_id'] === null ? null : $userMap[$r['user_id']]]);
        }
        $domainMap = $this->idMap($domains, $fresh['domains'] ?? []);
        foreach ($links as $row) {
            $assign('links', $row, fn ($r) => [
                'domain_id' => $domainMap[$r['domain_id']],
                'user_id' => $r['user_id'] === null ? null : $userMap[$r['user_id']],
            ]);
        }
        $linkMap = $this->idMap($links, $fresh['links'] ?? []);
        foreach ($clicks as $row) {
            $assign('clicks', $row, fn ($r) => ['link_id' => $linkMap[$r['link_id']]]);
        }
        foreach ($apiKeys as $row) {
            $assign('api_keys', $row, fn ($r) => ['user_id' => $userMap[$r['user_id']]]);
        }
        foreach ($encounters as $row) {
            $assign('error_encounters', $row, fn ($r) => ['user_id' => $r['user_id'] === null ? null : $userMap[$r['user_id']]]);
        }

        foreach (['plans', 'users', 'sessions', 'oauth_identities', 'domains', 'links', 'clicks', 'api_keys', 'error_encounters'] as $table) {
            foreach (array_chunk($fresh[$table] ?? [], 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            foreach (['plans', 'users', 'oauth_identities', 'domains', 'links', 'clicks', 'api_keys', 'error_encounters'] as $table) {
                DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))");
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Read every row and compute its replacement (new id + remapped
     * FKs). Returns ['map' => oldId => newId, 'rows' => replacements].
     * The closure returns FK remaps only — the id is set here, once.
     */
    private function remap(string $table, callable $remap, bool $keepId = false): array
    {
        $map = [];
        $rows = [];

        foreach (DB::table($table)->orderBy('id')->cursor() as $record) {
            $row = (array) $record;
            $newId = $keepId ? $row['id'] : $this->ulidFor($row);
            $map[$row['id']] = $newId;
            $rows[] = array_merge($row, $remap($row), ['id' => $newId]);
        }

        return ['map' => $map, 'rows' => $rows];
    }

    /**
     * Time-ordered ULID from the row's created_at so chronological
     * order survives the conversion (orderBy('id') keeps working).
     * Lowercase, matching Laravel's HasUlids convention.
     */
    private function ulidFor(array $row): string
    {
        try {
            $at = $row['created_at'] ?? null;

            if ($at !== null && $at !== '') {
                return strtolower(Ulid::generate(new DateTimeImmutable((string) $at)));
            }
        } catch (Throwable) {
            // Fall through to a random ULID.
        }

        return strtolower((string) Illuminate\Support\Str::ulid());
    }

    private function mapFk(array $map, mixed $old): mixed
    {
        return $old === null ? null : $map[$old];
    }

    /**
     * Rewrite the auth identifier inside a serialized session payload
     * (login_web_* keys) from the old int id to the new ULID, so
     * active sessions survive the conversion. Falls back to the
     * untouched payload on any surprise — worst case is a re-login.
     */
    private function remapSessionPayload(mixed $payload, array $userMap): mixed
    {
        if (! is_string($payload) || $payload === '') {
            return $payload;
        }

        try {
            $data = unserialize($payload, ['allowed_classes' => false]);
        } catch (Throwable) {
            return $payload;
        }

        if (! is_array($data)) {
            return $payload;
        }

        foreach ($data as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'login_')
                && (is_int($value) || (is_string($value) && ctype_digit($value)))
                && isset($userMap[(int) $value])) {
                $data[$key] = $userMap[(int) $value];
            }
        }

        try {
            return serialize($data);
        } catch (Throwable) {
            return $payload;
        }
    }

    private function idMap(array $oldRows, array $newRows): array
    {
        $map = [];

        foreach ($oldRows as $i => $row) {
            $map[$row['id']] = $newRows[$i]['id'];
        }

        return $map;
    }

    private function dropLegacyForeignKeys(): void
    {
        $keys = [
            'users' => ['plan_id'],
            'oauth_identities' => ['user_id'],
            'domains' => ['user_id'],
            'links' => ['domain_id', 'user_id'],
            'clicks' => ['link_id'],
            'api_keys' => ['user_id', 'api_version'],
            'error_encounters' => ['user_id'],
        ];

        foreach ($keys as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropForeign([$column]);
                }
            });
        }
    }

    private function createPlansTable(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->integer('min_slug_length')->default(8);
            $table->boolean('custom_subdomain')->default(false);
            $table->integer('rate_limit_per_minute')->default(10);
            $table->integer('max_links_per_day')->nullable();
            $table->timestamps();
        });
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('sso_sub')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('avatar_url', 512)->nullable();
            $table->string('sso_user_type', 50)->nullable();
            $table->string('role')->default('user');
            $table->foreignUlid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('nav_layout', 10)->default('side');
            $table->string('theme', 10)->default('system');
            $table->timestamps();
        });
    }

    private function createSessionsTable(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 26)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    private function createOauthIdentitiesTable(): void
    {
        Schema::create('oauth_identities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at');
            $table->json('sso_claims')->nullable();
            $table->timestamp('claims_synced_at')->nullable();
            $table->timestamps();
        });
    }

    private function createDomainsTable(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('hostname')->unique();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    private function createLinksTable(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug');
            $table->text('destination_url');
            $table->foreignUlid('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('click_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->string('creator_ip_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'slug']);
            $table->index('user_id');
            $table->index('expires_at');
            $table->index(['creator_ip_hash', 'created_at']);
        });
    }

    private function createClicksTable(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('link_id')->constrained('links')->cascadeOnDelete();
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 255)->nullable();
            $table->boolean('is_direct_url')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['link_id', 'created_at']);
            $table->index('created_at');
            $table->index('is_direct_url');
        });
    }

    private function createApiKeysTable(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key_hash', 64)->unique();
            $table->string('key_prefix', 8);
            $table->unsignedInteger('api_version');
            $table->foreign('api_version')->references('version')->on('api_versions')->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    private function createErrorEncountersTable(): void
    {
        Schema::create('error_encounters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('exception_class');
            $table->string('method', 10);
            $table->string('host');
            $table->string('path', 2048);
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['http_code', 'created_at']);
            $table->index(['exception_class', 'created_at']);
        });
    }

    private function createIntPlansTable(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('min_slug_length')->default(8);
            $table->boolean('custom_subdomain')->default(false);
            $table->integer('rate_limit_per_minute')->default(10);
            $table->integer('max_links_per_day')->nullable();
            $table->timestamps();
        });
    }

    private function createIntUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('sso_sub')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('avatar_url', 512)->nullable();
            $table->string('sso_user_type', 50)->nullable();
            $table->string('role')->default('user');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('nav_layout', 10)->default('side');
            $table->string('theme', 10)->default('system');
            $table->timestamps();
        });
    }

    private function createIntSessionsTable(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    private function createIntOauthIdentitiesTable(): void
    {
        Schema::create('oauth_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at');
            $table->json('sso_claims')->nullable();
            $table->timestamp('claims_synced_at')->nullable();
            $table->timestamps();
        });
    }

    private function createIntDomainsTable(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('hostname')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    private function createIntLinksTable(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->text('destination_url');
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('click_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->string('creator_ip_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'slug']);
            $table->index('user_id');
            $table->index('expires_at');
            $table->index(['creator_ip_hash', 'created_at']);
        });
    }

    private function createIntClicksTable(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 255)->nullable();
            $table->boolean('is_direct_url')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['link_id', 'created_at']);
            $table->index('created_at');
            $table->index('is_direct_url');
        });
    }

    private function createIntApiKeysTable(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key_hash', 64)->unique();
            $table->string('key_prefix', 8);
            $table->unsignedInteger('api_version');
            $table->foreign('api_version')->references('version')->on('api_versions')->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    private function createIntErrorEncountersTable(): void
    {
        Schema::create('error_encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('exception_class');
            $table->string('method', 10);
            $table->string('host');
            $table->string('path', 2048);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['http_code', 'created_at']);
            $table->index(['exception_class', 'created_at']);
        });
    }
};
