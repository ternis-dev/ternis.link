<?php

namespace Tests\Feature;

use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }
    public function test_legal_pages_render_publicly(): void
    {
        foreach (['privacy' => 'Privacy Policy', 'terms' => 'Terms of Service'] as $slug => $title) {
            $this->get("http://ternis.link/legal/{$slug}")
                ->assertStatus(200)
                ->assertSee($title, escape: false)
                ->assertSee('Privacy Policy', escape: false)
                ->assertSee('Terms of Service', escape: false)
                ->assertSee('https://ternis.dev/en/legal/imprint', escape: false);
        }
    }

    public function test_imprint_redirects_to_central_legal_page(): void
    {
        $this->get('http://ternis.link/legal/imprint')
            ->assertStatus(302)
            ->assertRedirect('https://ternis.dev/en/legal/imprint');
    }

    public function test_legal_pages_work_on_dashboard_host(): void
    {
        $this->get('http://dash.ternis.link/legal/privacy')->assertStatus(200);
    }

    public function test_unknown_legal_slug_404s(): void
    {
        $this->get('http://ternis.link/legal/quests')->assertStatus(404);
        $this->get('http://ternis.link/legal/privacy.md')->assertStatus(404);
    }

    public function test_legal_pages_rejected_off_allowed_hosts(): void
    {
        $this->get('http://href.nz/legal/privacy')->assertStatus(404);
    }

    public function test_landings_link_to_legal_pages(): void
    {
        $this->get('http://href.nz/')
            ->assertStatus(200)
            ->assertSee('https://ternis.link/legal/privacy', escape: false);
    }
}
