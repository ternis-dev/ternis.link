<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthz_returns_ok(): void
    {
        $response = $this->withHeaders(['Host' => 'href.nz'])->get('/healthz');

        $response->assertOk();
        $response->assertJson(['status' => 'ok', 'database' => 'ok']);
    }

    public function test_healthz_answers_on_unknown_host(): void
    {
        // Container / LB probes often hit the pod IP directly, which
        // ResolveDomain would otherwise reject with a 404.
        $response = $this->withHeaders(['Host' => '10.0.0.5'])->get('/healthz');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }
}
