<?php

namespace Tests\Feature;

use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_extension_page_is_public_on_ternis_host(): void
    {
        $manifest = json_decode((string) file_get_contents(base_path('extension/manifest.json')), true);

        $this->get('http://ternis.link/pages/extension')
            ->assertStatus(200)
            ->assertSee('Shorten any tab', escape: false)
            ->assertSee('v'.$manifest['version'], escape: false)
            ->assertSee('version JSON', escape: false);
    }

    public function test_extension_markdown_twin(): void
    {
        $this->get('http://ternis.link/pages/extension.md')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('Browser Extension', escape: false);
    }

    public function test_extension_version_json_matches_manifest(): void
    {
        $manifest = json_decode((string) file_get_contents(base_path('extension/manifest.json')), true);

        $this->get('http://ternis.link/pages/extension/version')
            ->assertStatus(200)
            ->assertJsonPath('version', $manifest['version'])
            ->assertJsonPath('name', $manifest['name'])
            ->assertJsonStructure(['version', 'name', 'download_url', 'download_ready']);
    }

    public function test_extension_download_redirects_without_build(): void
    {
        // No zip exists in a fresh checkout — users land back on the
        // page (which shows the pending-build state) instead of a 404.
        foreach (glob(public_path('extension/*.zip')) ?: [] as $zip) {
            unlink($zip);
        }

        $this->get('http://ternis.link/pages/extension/download')
            ->assertRedirect('http://ternis.link/pages/extension');
        $this->get('http://ternis.link/pages/extension')
            ->assertStatus(200)
            ->assertSee('Build pending', escape: false);
    }

    public function test_short_extension_url_redirects_to_canonical_page(): void
    {
        $this->get('http://ternis.link/extension')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/extension');

        // Subpaths and query strings survive the redirect.
        $this->get('http://ternis.link/extension/download')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/extension/download');

        $this->get('http://ternis.link/extension/version?foo=bar')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/extension/version?foo=bar');

        $this->get('http://ternis.link/extension.md')
            ->assertStatus(301)
            ->assertRedirect('http://ternis.link/pages/extension.md');
    }

    public function test_extension_routes_404_on_other_hosts(): void
    {
        $this->get('http://href.nz/pages/extension')->assertNotFound();
        $this->get('http://href.nz/pages/extension.md')->assertNotFound();
        $this->get('http://href.nz/extension')->assertNotFound();
        $this->get('http://dash.ternis.link/pages/extension')->assertNotFound();
        $this->get('http://links.t-api.de/pages/extension/version')->assertNotFound();
    }

    public function test_extension_build_command_packages_zip(): void
    {
        $this->artisan('extension:build')->assertSuccessful();

        $manifest = json_decode((string) file_get_contents(base_path('extension/manifest.json')), true);
        $zip = public_path("extension/ternis-link-extension-v{$manifest['version']}.zip");

        $this->assertFileExists($zip);

        $archive = new \ZipArchive;
        $this->assertTrue($archive->open($zip));
        foreach (['manifest.json', 'popup.html', 'popup.js', 'background.js'] as $required) {
            $this->assertNotFalse($archive->locateName($required), "missing {$required}");
        }
        $archive->close();

        // With a build present the download serves the zip.
        $this->get('http://ternis.link/pages/extension/download')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/zip');

        $this->get('http://ternis.link/pages/extension/version')
            ->assertJsonPath('download_ready', true);

        $this->get('http://ternis.link/pages/extension')
            ->assertStatus(200)
            ->assertSee('/pages/extension/download', escape: false);

        // Clean up so other tests see the no-build state.
        foreach (glob(public_path('extension/*.zip')) ?: [] as $file) {
            unlink($file);
        }
    }

    public function test_extension_docs_page_exists(): void
    {
        $this->get('http://docs.ternis.link/extension')->assertStatus(200);
    }

    public function test_extension_in_sitemap_and_llms(): void
    {
        $this->get('http://ternis.link/sitemap.xml')
            ->assertStatus(200)
            ->assertSee('/pages/extension', escape: false);

        $this->get('http://ternis.link/llms.txt')
            ->assertStatus(200)
            ->assertSee('/pages/extension', escape: false);
    }
}
