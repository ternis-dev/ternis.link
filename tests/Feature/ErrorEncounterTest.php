<?php

namespace Tests\Feature;

use App\Models\ErrorEncounter;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorEncounterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_404_probe_is_logged_with_code_and_message(): void
    {
        $this->assertEquals(0, ErrorEncounter::count());

        // Unknown slugs render the branded miss page directly (no
        // exception), so probe an unknown host — ResolveDomain aborts.
        $this->get('http://no-such-host-xyz.example/some-path')->assertStatus(404);

        $encounter = ErrorEncounter::firstOrFail();
        $this->assertEquals(404, $encounter->http_code);
        $this->assertEquals('no-such-host-xyz.example', $encounter->host);
        $this->assertEquals('GET', $encounter->method);
        $this->assertNotEmpty($encounter->error_message);
        $this->assertNotEmpty($encounter->exception_class);
        $this->assertNotNull($encounter->ip_hash);
        $this->assertEquals(hash('sha256', '127.0.0.1'), $encounter->ip_hash);
        $this->assertNull($encounter->user_id);
    }

    public function test_403_is_logged_with_actor_and_host(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://admin.ternis.link/admin')
            ->assertForbidden();

        $encounter = ErrorEncounter::where('http_code', 403)->firstOrFail();
        $this->assertEquals('admin.ternis.link', $encounter->host);
        $this->assertEquals($user->id, $encounter->user_id);
    }

    public function test_healthz_failures_are_not_logged(): void
    {
        // Health probes flap during deploys — never error-log them.
        $this->get('http://10.0.0.5/healthz');

        $this->assertEquals(0, ErrorEncounter::count());
    }

    public function test_validation_noise_is_not_logged(): void
    {
        $this->postJson('http://href.nz/v1/links/public', [
            'destination_url' => 'https://example.com/ok',
            'slug' => 'custom-not-allowed',
        ])->assertStatus(422);

        $this->assertEquals(0, ErrorEncounter::count());
    }
}
