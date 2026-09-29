<?php

namespace Tests\Feature;

use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_ternis_landing_presents_personal_subdomains(): void
    {
        $this->get('http://ternis.link/')
            ->assertStatus(200)
            ->assertSee('on your own name', escape: false)
            ->assertSee('{name}.ternis.link', escape: false)
            ->assertSee('How it works', escape: false)
            ->assertSee('Get Started with Ternis Auth', escape: false);
    }

    public function test_ternis_landing_dropped_the_domain_list_badge(): void
    {
        $response = $this->get('http://ternis.link/');

        $response->assertStatus(200);
        $response->assertDontSee('href.nz · href.re · ternis.link', escape: false);
        $response->assertDontSee('Public shortening lives on', escape: false);
    }

    public function test_ternis_landing_points_guests_to_hrefnz(): void
    {
        $this->get('http://ternis.link/')
            ->assertStatus(200)
            ->assertSee('https://href.nz', escape: false);
    }

    public function test_ternis_landing_links_stats_and_docs(): void
    {
        $this->get('http://ternis.link/')
            ->assertStatus(200)
            ->assertSee('/pages/stats', escape: false)
            ->assertSee('https://docs.ternis.link', escape: false);
    }

    public function test_ternis_landing_shows_live_network_numbers(): void
    {
        $this->get('http://ternis.link/')
            ->assertStatus(200)
            ->assertSee('Live from the network', escape: false)
            ->assertSee('short links created', escape: false)
            ->assertSee('redirects counted', escape: false);
    }

    public function test_ternis_landing_answers_frequently_asked_questions(): void
    {
        $this->get('http://ternis.link/')
            ->assertStatus(200)
            ->assertSee('Frequently asked questions', escape: false)
            ->assertSee('id="faq"', escape: false)
            ->assertSee('href="#faq"', escape: false)
            ->assertSee('claim my {name}.ternis.link subdomain', escape: false)
            ->assertSee('application/ld+json', escape: false)
            ->assertSee('FAQPage', escape: false);
    }
}
