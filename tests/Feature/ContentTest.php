<?php

namespace Tests\Feature;

use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_collection_indexes_list_entries_newest_first(): void
    {
        $body = $this->get('http://ternis.link/pages/changelog')->assertStatus(200)->getContent();

        $this->assertStringContainsString('Changelog', $body);
        $this->assertStringContainsString('sketch-login-for-href-nz', $body);
        $this->assertStringContainsString('/pages/changelog/machine-readable-site-files', $body);

        // Newest first: entries share the seed date, so order falls
        // back to slug descending.
        $this->assertTrue(
            strpos($body, 'sketch-login-for-href-nz') < strpos($body, 'machine-readable-site-files')
        );

        $this->get('http://ternis.link/pages/news')
            ->assertStatus(200)
            ->assertSee('Network stats are now public', escape: false);

        $this->get('http://ternis.link/pages/blog')
            ->assertStatus(200)
            ->assertSee('Why we self-host everything', escape: false);
    }

    public function test_entry_renders_markdown(): void
    {
        $this->get('http://ternis.link/pages/blog/why-we-self-host-everything')
            ->assertStatus(200)
            ->assertSee('Why we self-host everything', escape: false)
            ->assertSee('Turnstile', escape: false)
            ->assertSee('← Blog', escape: false);
    }

    public function test_collections_404_on_other_hosts(): void
    {
        $this->get('http://href.nz/pages/news')->assertNotFound();
        $this->get('http://href.nz/pages/changelog/machine-readable-site-files')->assertNotFound();
        $this->get('http://dash.ternis.link/pages/blog')->assertNotFound();
    }

    public function test_unknown_slugs_and_collections_404(): void
    {
        $this->get('http://ternis.link/pages/news/no-such-story')->assertNotFound();
        $this->get('http://ternis.link/pages/news/no-such-story.md')->assertNotFound();
        $this->get('http://ternis.link/pages/podcast')->assertNotFound();
        $this->get('http://ternis.link/pages/news/Has_Caps')->assertNotFound();
    }

    public function test_markdown_twins_render(): void
    {
        $this->get('http://ternis.link/pages/changelog.md')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Changelog', escape: false)
            ->assertSee('/pages/changelog/machine-readable-site-files.md', escape: false);

        $entry = $this->get('http://ternis.link/pages/news/public-network-stats.md')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Network stats are now public', escape: false);

        // Twin carries the raw body, not rendered HTML.
        $this->assertStringContainsString('aggregate-only analytics', $entry->getContent());
        $this->assertStringNotContainsString('<article', $entry->getContent());
    }

    public function test_html_pages_advertise_markdown_alternates(): void
    {
        $this->get('http://ternis.link/pages/blog')
            ->assertSee('/pages/blog.md', escape: false);

        $this->get('http://ternis.link/pages/blog/why-we-self-host-everything')
            ->assertSee('/pages/blog/why-we-self-host-everything.md', escape: false);
    }

    public function test_sitemap_includes_collections(): void
    {
        $body = $this->get('http://ternis.link/sitemap.xml')->assertStatus(200)->getContent();

        $this->assertStringContainsString('<loc>http://ternis.link/pages/changelog</loc>', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/news</loc>', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/blog</loc>', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/blog/why-we-self-host-everything</loc>', $body);
    }

    public function test_every_blog_entry_resolves_with_twin(): void
    {
        // Guards new posts against broken front matter or slugs:
        // each entry must render HTML and serve its markdown twin.
        foreach (\App\Support\ContentCollection::entries('blog') as $entry) {
            $this->get("http://ternis.link/pages/blog/{$entry['slug']}")
                ->assertStatus(200)
                ->assertSee($entry['title']);

            $this->get("http://ternis.link/pages/blog/{$entry['slug']}.md")
                ->assertStatus(200)
                ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        }

        $this->assertNotEmpty(\App\Support\ContentCollection::entries('blog'));
    }

    public function test_blog_shortcuts_redirect_to_canonical_home(): void
    {
        $this->get('http://ternis.link/blog')->assertRedirect('http://ternis.link/pages/blog');
        $this->get('http://ternis.link/blogs')->assertRedirect('http://ternis.link/pages/blog');
        $this->get('http://ternis.link/blogs/why-we-self-host-everything')
            ->assertRedirect('http://ternis.link/pages/blog/why-we-self-host-everything');
        $this->get('http://ternis.link/blog?utm_source=nav')
            ->assertRedirect('http://ternis.link/pages/blog?utm_source=nav');

        // Bare slugs starting with "blog" stay on short-link resolution.
        $this->get('http://ternis.link/blogroll')->assertNotFound();

        // Shortcuts 404 off the home host (never shadow slugs or docs).
        $this->get('http://href.nz/blog')->assertNotFound();
        $this->get('http://href.nz/blogs')->assertNotFound();
        $this->get('http://docs.ternis.link/blog')->assertNotFound();
    }

    public function test_entry_has_lede_reading_time_and_structured_data(): void
    {
        $body = $this->get('http://ternis.link/pages/blog/why-we-self-host-everything')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('min read', $body);
        $this->assertStringContainsString('application/ld+json', $body);
        $this->assertStringContainsString('"@type":"BlogPosting"', $body);
    }

    public function test_entry_navigates_to_neighbors(): void
    {
        // Newest-first: the newest post links back to an older one.
        $entries = \App\Support\ContentCollection::entries('blog');
        $newest = $entries[0]['slug'];

        $body = $this->get("http://ternis.link/pages/blog/{$newest}")
            ->assertStatus(200)
            ->getContent();

        if (count($entries) > 1) {
            $this->assertStringContainsString('/pages/blog/'.$entries[1]['slug'], $body);
        }
    }

    public function test_llms_mentions_collections(): void
    {
        $this->get('http://ternis.link/llms.txt')
            ->assertSee('https://ternis.link/pages/changelog', escape: false);

        $this->get('http://ternis.link/llms-full.txt')
            ->assertSee('https://ternis.link/pages/blog', escape: false);
    }
}
