<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every asset referenced via @vite() in Blade must be a registered
 * input in vite.config.js. A typo or a forgotten input compiles fine
 * locally (dev server serves anything) and passes stubbed tests, then
 * 500s in production with "Unable to locate file in Vite manifest".
 */
class ViteInputsTest extends TestCase
{
    public function test_all_vite_references_are_registered_inputs(): void
    {
        $inputs = $this->viteInputs();
        $this->assertNotEmpty($inputs);

        $missing = [];

        foreach ($this->bladeFiles(resource_path('views')) as $path) {
            foreach ($this->viteRefs(file_get_contents($path)) as $ref) {
                if (! in_array($ref, $inputs, true)) {
                    $missing[] = "{$ref} (referenced in {$path})";
                }
            }
        }

        $this->assertSame([], $missing);
    }

    /**
     * @return list<string>
     */
    private function viteInputs(): array
    {
        $config = file_get_contents(base_path('vite.config.js'));

        preg_match_all("/'(resources\/[^']+)'/", $config, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return list<string>
     */
    private function bladeFiles(string $dir): array
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function viteRefs(string $contents): array
    {
        $refs = [];

        if (preg_match_all('/@vite\(\s*\[(.*?)\]\s*\)/s', $contents, $blocks)) {
            foreach ($blocks[1] as $block) {
                if (preg_match_all("/'(resources\/[^']+)'/", $block, $m)) {
                    array_push($refs, ...$m[1]);
                }
            }
        }

        return array_values(array_unique($refs));
    }
}
