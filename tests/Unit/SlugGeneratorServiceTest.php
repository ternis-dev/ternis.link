<?php

namespace Tests\Unit;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\Link;
use App\Services\SlugGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_valid_slug_characters(): void
    {
        $domain = Domain::create([
            'hostname' => 'href.nz',
            'type' => DomainType::Public,
            'is_active' => true,
        ]);

        $generator = new SlugGeneratorService;
        $slug = $generator->generate(8, $domain->id);

        $this->assertEquals(8, strlen($slug));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $slug);
        $this->assertStringNotContainsString('.', $slug);
        $this->assertStringNotContainsString('/', $slug);
        $this->assertStringNotContainsString(':', $slug);
    }

    public function test_it_generates_unique_slugs(): void
    {
        $domain = Domain::create([
            'hostname' => 'href.nz',
            'type' => DomainType::Public,
            'is_active' => true,
        ]);

        Link::create([
            'slug' => 'fixed1',
            'destination_url' => 'https://example.com',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

        $generator = new SlugGeneratorService;
        $slug = $generator->generate(6, $domain->id);

        $this->assertNotEquals('fixed1', $slug);
    }
}
