<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $user = User::factory()->create();
        $domain = Domain::where('hostname', 'href.nz')->first();

        $link = Link::create([
            'slug' => 'sitelink1',
            'destination_url' => 'https://example.com/site',
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'is_active' => true,
            'click_count' => 3,
        ]);
        Click::create(['link_id' => $link->id, 'is_direct_url' => false, 'referrer' => 'https://secret.example/', 'user_agent' => 'SecretAgent/1.0']);
    }

    public function test_robots_is_curated_on_short_link_hosts(): void
    {
        $response = $this->get('http://href.nz/robots.txt')->assertStatus(200);
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringContainsString('Allow: /$', $body);
        $this->assertStringContainsString('Allow: /pages/', $body);
        $this->assertStringContainsString('Disallow: /auth/', $body);
        $this->assertStringContainsString('Disallow: /url/', $body);
        $this->assertStringContainsString('Disallow: /preview/', $body);
        $this->assertStringContainsString('Sitemap: http://href.nz/sitemap.xml', $body);
    }

    public function test_robots_disallows_everything_on_app_hosts(): void
    {
        foreach (['http://dash.ternis.link/robots.txt', 'http://admin.ternis.link/robots.txt', 'http://links.t-api.de/robots.txt'] as $url) {
            $body = $this->get($url)->assertStatus(200)->getContent();
            $this->assertStringContainsString('Disallow: /', $body);
            $this->assertStringNotContainsString('Allow:', $body);
        }
    }

    public function test_sitemap_lists_same_host_pages_on_ternis(): void
    {
        $response = $this->get('http://ternis.link/sitemap.xml')->assertStatus(200);
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringContainsString('<loc>http://ternis.link/</loc>', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/stats</loc>', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/stats/domains</loc>', $body);
        $this->assertStringNotContainsString('pages/stats/links', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/legal/privacy</loc>', $body);
        $this->assertStringContainsString('<loc>http://ternis.link/pages/legal/terms</loc>', $body);
        // Redirects (imprint) are not canonical URLs and stay out.
        $this->assertStringNotContainsString('imprint', $body);
    }

    public function test_sitemap_on_public_host_lists_only_homepage(): void
    {
        // Must be a real route — not swallowed by the /{slug} catch-all.
        $body = $this->get('http://href.nz/sitemap.xml')->assertStatus(200)->getContent();
        $this->assertStringContainsString('<loc>http://href.nz/</loc>', $body);
        $this->assertStringNotContainsString('/pages/', $body);
    }

    public function test_sitemap_is_strictly_valid_xml(): void
    {
        foreach (['http://ternis.link/sitemap.xml', 'http://docs.ternis.link/sitemap.xml', 'http://href.nz/sitemap.xml'] as $url) {
            $response = $this->get($url)->assertStatus(200);
            $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

            $doc = simplexml_load_string($response->getContent());
            $this->assertNotFalse($doc, "Invalid XML from {$url}");
            $this->assertNotEmpty($doc->url);
        }
    }

    public function test_robots_advertises_sitemap_on_crawlable_hosts(): void
    {
        foreach (['http://ternis.link/robots.txt', 'http://docs.ternis.link/robots.txt', 'http://href.nz/robots.txt'] as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            $body = $this->get($url)->assertStatus(200)->getContent();
            $this->assertStringContainsString("Sitemap: http://{$host}/sitemap.xml", $body);
        }
    }

    public function test_sitemap_carries_changefreq_and_priority(): void
    {
        $body = $this->get('http://ternis.link/sitemap.xml')->assertStatus(200)->getContent();

        $this->assertStringContainsString('<changefreq>daily</changefreq><priority>1.0</priority>', $body);
        $this->assertStringContainsString('<changefreq>monthly</changefreq><priority>0.6</priority>', $body);
        $this->assertStringContainsString('<changefreq>yearly</changefreq><priority>0.3</priority>', $body);
    }

    public function test_sitemap_on_docs_host_lists_guides(): void
    {
        $body = $this->get('http://docs.ternis.link/sitemap.xml')->assertStatus(200)->getContent();

        $this->assertStringContainsString('<loc>http://docs.ternis.link/</loc>', $body);
        $this->assertStringContainsString('<loc>http://docs.ternis.link/api</loc>', $body);
        $this->assertStringContainsString('<loc>http://docs.ternis.link/authentication</loc>', $body);
        $this->assertStringNotContainsString('ternis.link/pages/', $body);
    }

    public function test_robots_allows_crawling_on_docs_host(): void
    {
        $body = $this->get('http://docs.ternis.link/robots.txt')->assertStatus(200)->getContent();

        $this->assertStringContainsString('Allow: /', $body);
        $this->assertStringContainsString('Sitemap: http://docs.ternis.link/sitemap.xml', $body);
        $this->assertStringNotContainsString('Disallow: /', $body);
    }

    public function test_llms_txt_points_at_full_and_markdown_twins(): void
    {
        $response = $this->get('http://ternis.link/llms.txt')->assertStatus(200);
        $this->assertStringStartsWith('text/markdown', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringContainsString('# ternis.link', $body);
        $this->assertStringContainsString('http://ternis.link/llms-full.txt', $body);
        $this->assertStringContainsString('https://ternis.link/pages/stats.md', $body);
        $this->assertStringContainsString('https://links.t-api.de/v1/', $body);
    }

    public function test_llms_full_txt_inlines_legal_api_and_snapshot(): void
    {
        $body = $this->get('http://href.nz/llms-full.txt')->assertStatus(200)->getContent();
        $this->assertStringContainsString('# ternis.link (full)', $body);
        // Full legal source inline…
        $this->assertStringContainsString('legal@ternis.dev', $body);
        // …API endpoint list…
        $this->assertStringContainsString('/v1/links/{link}/clicks/summary', $body);
        // …and a live aggregate snapshot (seeded link counted, no PII).
        $this->assertStringContainsString('Links Created (All Time): 1', $body);
        $this->assertStringNotContainsString('secret.example', $body);
        $this->assertStringNotContainsString('SecretAgent', $body);
    }

    public function test_stats_markdown_twins_render_on_ternis_host(): void
    {
        $this->get('http://ternis.link/pages/stats.md')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Network Stats', escape: false)
            ->assertSee('| Day | Links |', escape: false)
            ->assertSee('| Day | Clicks |', escape: false)
            ->assertSee('Links Created (All Time): 1', escape: false);
    }

    public function test_stats_markdown_twins_carry_tables_and_no_pii(): void
    {
        $domains = $this->get('http://ternis.link/pages/stats/domains.md')
            ->assertStatus(200)
            ->assertSee('| Domain | Links | Clicks |', escape: false);

        // Platform rows are public; user hostnames never leak here.
        $this->assertStringContainsString('| href.nz |', $domains->getContent());

        // The public Top Links page is gone (leaderboards distort
        // member analytics) — HTML and Markdown both 404.
        $this->get('http://ternis.link/pages/stats/links')->assertNotFound();
        $this->get('http://ternis.link/pages/stats/links.md')->assertNotFound();

        foreach ([$domains->getContent()] as $body) {
            $this->assertStringNotContainsString('secret.example', $body);
            $this->assertStringNotContainsString('SecretAgent', $body);
            $this->assertStringNotContainsString('https://example.com/site', $body);
        }
    }

    public function test_stats_markdown_twins_404_on_other_hosts(): void
    {
        $this->get('http://href.nz/pages/stats.md')->assertNotFound();
        $this->get('http://dash.ternis.link/pages/stats.md')->assertNotFound();
        $this->get('http://href.nz/pages/stats/domains.md')->assertNotFound();
    }

    public function test_legal_markdown_serves_source_verbatim(): void
    {
        $expected = (string) file_get_contents(resource_path('legal/privacy.md'));

        $this->get('http://ternis.link/pages/legal/privacy.md')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Privacy Policy', escape: false)
            ->assertSee('legal@ternis.dev', escape: false);

        $this->assertSame($expected, $this->get('http://ternis.link/pages/legal/privacy.md')->getContent());
    }

    public function test_legal_markdown_redirects_and_404s_like_html(): void
    {
        $this->get('http://ternis.link/pages/legal/imprint.md')
            ->assertStatus(302)
            ->assertRedirect('https://ternis.dev/en/legal/imprint');

        $this->get('http://ternis.link/pages/legal/nope.md')->assertNotFound();
        $this->get('http://href.nz/pages/legal/privacy.md')->assertNotFound();
    }

    public function test_html_pages_advertise_markdown_alternates(): void
    {
        $this->get('http://ternis.link/pages/stats')
            ->assertSee('rel="alternate" type="text/markdown"', escape: false)
            ->assertSee('/pages/stats.md', escape: false);

        $this->get('http://ternis.link/pages/legal/terms')
            ->assertSee('/pages/legal/terms.md', escape: false);
    }

    public function test_html_pages_have_canonical_urls(): void
    {
        $this->get('http://ternis.link/pages/stats')
            ->assertSee('<link rel="canonical" href="http://ternis.link/pages/stats">', escape: false);

        $this->get('http://ternis.link/pages/stats/domains')
            ->assertSee('<link rel="canonical" href="http://ternis.link/pages/stats/domains">', escape: false);

        $this->get('http://ternis.link/pages/extension')
            ->assertSee('<link rel="canonical" href="http://ternis.link/pages/extension">', escape: false);

        $this->get('http://ternis.link/pages/legal/terms')
            ->assertSee('<link rel="canonical" href="http://ternis.link/pages/legal/terms">', escape: false);
    }
}
