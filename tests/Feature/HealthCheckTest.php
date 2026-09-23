<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
