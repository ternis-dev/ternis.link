<?php

namespace Tests\Unit;

use App\Services\SlugResolverService;
use PHPUnit\Framework\TestCase;

class SlugResolverServiceTest extends TestCase
{
    private SlugResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new SlugResolverService;
    }

    public function test_it_classifies_urls_correctly(): void
    {
        $this->assertEquals('url', $this->resolver->classify('google.com'));
        $this->assertEquals('url', $this->resolver->classify('https://example.org'));
        $this->assertEquals('url', $this->resolver->classify('http://ternis.dev'));
        $this->assertEquals('url', $this->resolver->classify('example.com/some/path'));
        $this->assertEquals('url', $this->resolver->classify('sub.domain.tld'));
    }

    public function test_it_classifies_slugs_correctly(): void
    {
        $this->assertEquals('slug', $this->resolver->classify('myslug'));
        $this->assertEquals('slug', $this->resolver->classify('abc123'));
        $this->assertEquals('slug', $this->resolver->classify('my-link_1'));
        $this->assertEquals('slug', $this->resolver->classify('ABC_xyz-99'));
    }

    public function test_it_normalizes_urls(): void
    {
        $this->assertEquals('https://example.com', $this->resolver->normalizeUrl('example.com'));
        $this->assertEquals('https://example.com', $this->resolver->normalizeUrl('https://example.com'));
        $this->assertEquals('http://example.com', $this->resolver->normalizeUrl('http://example.com'));
    }
}
