<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\SecurityAlert;
use App\Support\Activity;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    private function user(): User
    {
        return User::factory()->create([
            'plan_id' => Plan::where('name', 'family')->firstOrFail()->id,
        ]);
    }

    /** Raw key + headers for an authenticated user. */
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

    public function test_api_requires_authentication(): void
    {
        foreach (['api-keys', 'notifications', 'activity', 'settings'] as $path) {
            $this->getJson("http://links.t-api.de/v1/{$path}")->assertStatus(401);
        }
    }

    public function test_api_key_create_lists_and_revokes(): void
    {
        $user = $this->user();
        $headers = $this->headersFor($user);

        $this->postJson('http://links.t-api.de/v1/api-keys', [], $headers)
            ->assertStatus(422);

        $created = $this->postJson(
            'http://links.t-api.de/v1/api-keys',
            ['name' => 'Mac'],
            $headers
        )->assertStatus(201);

        $raw = $created->json('api_key');
        $this->assertIsString($raw);
        $this->assertStringStartsWith('tl_', $raw);
        $created->assertJsonPath('name', 'Mac');
        $created->assertJsonPath('key_prefix', substr($raw, 0, 8));
        // The digest is never exposed.
        $this->assertArrayNotHasKey('key_hash', $created->json());

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'action' => ActivityLog::API_KEY_CREATED,
        ]);

        $list = $this->getJson('http://links.t-api.de/v1/api-keys', $headers)->assertStatus(200);
        $list->assertJsonFragment(['name' => 'Mac']);
        $this->assertArrayNotHasKey('key_hash', $list->json('data')[0]);

        // The fresh key authenticates too.
        $this->getJson('http://links.t-api.de/v1/api-keys', ['Authorization' => "Bearer {$raw}"])
            ->assertStatus(200);

        $keyId = $created->json('id');
        $this->deleteJson("http://links.t-api.de/v1/api-keys/{$keyId}", [], $headers)->assertStatus(204);
        $this->assertNotNull(ApiKey::find($keyId)->revoked_at);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'action' => ActivityLog::API_KEY_REVOKED,
        ]);

        // Revoked keys stop authenticating.
        $this->getJson('http://links.t-api.de/v1/api-keys', ['Authorization' => "Bearer {$raw}"])
            ->assertStatus(401);
    }

    public function test_api_key_scoped_to_owner(): void
    {
        $owner = $this->user();
        $stranger = $this->user();
        $key = ApiKey::create([
            'user_id' => $owner->id,
            'key_hash' => hash('sha256', $raw = 'tl_'.Str::random(48)),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Mine',
        ]);

        $this->deleteJson(
            "http://links.t-api.de/v1/api-keys/{$key->id}",
            [],
            $this->headersFor($stranger)
        )->assertStatus(403);
    }

    public function test_notifications_list_and_mark_read(): void
    {
        config(['queue.default' => 'sync']);
        $user = $this->user();
        $headers = $this->headersFor($user);
        $user->notify(new SecurityAlert('Hello', ['World']));

        $list = $this->getJson('http://links.t-api.de/v1/notifications', $headers)->assertStatus(200);
        $id = $list->json('data')[0]['id'];
        $this->assertNull($list->json('data')[0]['read_at']);

        $this->postJson("http://links.t-api.de/v1/notifications/{$id}/read", [], $headers)
            ->assertStatus(200)
            ->assertJsonPath('read_at', fn ($v) => $v !== null);

        $user->notify(new SecurityAlert('Again', ['Second']));
        $this->postJson('http://links.t-api.de/v1/notifications/read', [], $headers)->assertStatus(200);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_activity_lists_own_actions(): void
    {
        $user = $this->user();
        $other = $this->user();
        $headers = $this->headersFor($user);

        Activity::record(ActivityLog::AUTH_LOGIN, $user, null, ['via' => 'api']);
        Activity::record(ActivityLog::AUTH_LOGIN, $other, null, ['via' => 'api']);

        $response = $this->getJson('http://links.t-api.de/v1/activity', $headers)->assertStatus(200);
        $response->assertJsonFragment(['action' => ActivityLog::AUTH_LOGIN]);
        foreach ($response->json('data') as $row) {
            $this->assertTrue(
                $row['actor_id'] === $user->id || $row['subject_owner_id'] === $user->id,
                'Activity feed leaked another user’s rows.'
            );
        }
    }

    public function test_settings_show_and_update(): void
    {
        $user = $this->user();
        $headers = $this->headersFor($user);

        $this->getJson('http://links.t-api.de/v1/settings', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['nav_layout', 'theme', 'notify_security_email']);

        $this->patchJson('http://links.t-api.de/v1/settings', ['theme' => 'neon'], $headers)
            ->assertStatus(422);

        $this->patchJson('http://links.t-api.de/v1/settings', [
            'theme' => 'dark',
            'nav_layout' => 'top',
            'notify_security_email' => false,
        ], $headers)->assertStatus(200)
            ->assertJsonPath('theme', 'dark')
            ->assertJsonPath('nav_layout', 'top')
            ->assertJsonPath('notify_security_email', false);

        $this->assertSame('dark', $user->fresh()->theme);
    }
}
