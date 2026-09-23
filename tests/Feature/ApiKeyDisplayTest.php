<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ApiKeyManager;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function test_created_key_displays_raw_token_never_a_hash(): void
    {
        $shown = Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'My Key')
            ->call('createKey')
            ->get('newlyCreatedKey');

        // The real, usable token — not a hash of any kind.
        $this->assertIsString($shown);
        $this->assertTrue(str_starts_with($shown, 'tl_'));
        $this->assertFalse(str_starts_with($shown, '$2y$'));
        $this->assertFalse(str_starts_with($shown, '$argon'));

        $stored = $this->user->apiKeys()->firstOrFail();

        // Only the SHA-256 digest is persisted, and it matches the shown key.
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $stored->key_hash);
        $this->assertTrue(hash_equals($stored->key_hash, hash('sha256', $shown)));
        $this->assertTrue($stored->verifyToken($shown));
    }

    public function test_displayed_key_authenticates_but_hash_does_not(): void
    {
        $shown = Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'API Key')
            ->call('createKey')
            ->get('newlyCreatedKey');

        $this->actingAs($this->user)
            ->get('http://links.t-api.de/v1/links', ['Authorization' => "Bearer {$shown}"])
            ->assertStatus(200);

        $stored = $this->user->apiKeys()->firstOrFail();

        // Presenting the stored hash itself must never authenticate.
        $this->get('http://links.t-api.de/v1/links', ['Authorization' => "Bearer {$stored->key_hash}"])
            ->assertStatus(401);
    }

    public function test_bcrypt_hash_can_never_be_persisted(): void
    {
        $this->expectException(\RuntimeException::class);

        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => Hash::make('tl_fakerawkeyvalue'),
            'key_prefix' => 'tl_fake',
            'api_version' => 1,
            'name' => 'Bogus',
        ]);
    }

    public function test_dismissing_hides_the_one_time_key(): void
    {
        $test = Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'Temp')
            ->call('createKey')
            ->assertSee('tl_');

        $shown = $test->get('newlyCreatedKey');

        // The full secret disappears; only the masked prefix (tl_xxx****)
        // remains visible in the key table.
        $test->call('dismissNewKey')
            ->assertDontSee($shown)
            ->assertSet('newlyCreatedKey', null);
    }
}
