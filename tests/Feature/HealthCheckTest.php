<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthz_returns_ok(): void
    {
        $response = $this->get('http://href.nz/healthz');

        $response->assertOk();
        $response->assertJson(['status' => 'ok', 'database' => 'ok']);
    }

    public function test_healthz_answers_on_unknown_host(): void
    {
        // Container / LB probes often hit the pod IP directly, which
        // ResolveDomain would otherwise reject with a 404.
        $response = $this->get('http://10.0.0.5/healthz');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_readiness_reports_unapplied_migrations_without_disabling_live_servers(): void
    {
        DB::table('migrations')->where('migration', '2026_10_01_000029_create_link_targets_table')->delete();

        $this->get('http://10.0.0.5/readyz')
            ->assertStatus(503)
            ->assertJson(['status' => 'degraded', 'database' => 'ok', 'migrations' => 'pending']);
        $this->get('http://10.0.0.5/healthz')->assertOk();
    }
}
