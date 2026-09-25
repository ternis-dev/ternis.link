<?php

namespace Tests\Feature;

use App\Support\CommitVersion;
use Illuminate\Foundation\Vite;
use Tests\TestCase;

class ViteCommitStampTest extends TestCase
{
    protected function tearDown(): void
    {
        CommitVersion::flush();

        parent::tearDown();
    }

    public function test_built_stylesheets_are_stamped_with_the_commit_id(): void
    {
        $build = 'build-test-'.uniqid();
        $directory = public_path($build);

        mkdir($directory, 0755, true);
        file_put_contents($directory.'/manifest.json', json_encode([
            'resources/css/app.css' => [
                'file' => 'assets/app-abc123.css',
                'name' => 'app',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
            'resources/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'name' => 'app',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
        ]));

        CommitVersion::pin('deadbee');
        $this->withVite();

        try {
            $vite = $this->app->make(Vite::class)
                ->useHotFile($directory.DIRECTORY_SEPARATOR.'no-hot-file');

            $html = (string) $vite([
                'resources/css/app.css',
                'resources/js/app.js',
            ], $build);

            // Stylesheets carry the commit id, scripts keep the bare hash.
            $this->assertStringContainsString('assets/app-abc123.css?v=deadbee', $html);
            $this->assertStringNotContainsString('assets/app-abc123.js?v=', $html);
        } finally {
            $this->withoutVite();

            @unlink($directory.DIRECTORY_SEPARATOR.'manifest.json');
            @rmdir($directory);
        }
    }
}
