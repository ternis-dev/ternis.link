<?php

namespace Tests\Feature;

use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed([PlanSeeder::class, DomainSeeder::class]);

        $response = $this->withHeaders(['Host' => 'href.nz'])->get('/');

        $response->assertStatus(200);
    }
}
