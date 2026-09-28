<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

/**
 * Package extension/ into public/extension/*.zip for the download page.
 *
 * Single source of truth for the version is extension/manifest.json.
 * Output: public/extension/ternis-link-extension-v{version}.zip plus a
 * `ternis-link-extension.zip` copy as the stable "latest" URL.
 */
class BuildExtensionCommand extends Command
{
    protected $signature = 'extension:build {--print : Only print version and output path}';

    protected $description = 'Zip the Chrome extension (extension/) into public/extension/ for download.';

    public function handle(): int
    {
        $root = base_path('extension');
        $manifestPath = $root.'/manifest.json';

        if (! is_file($manifestPath)) {
            $this->error('extension/manifest.json not found.');

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest) || empty($manifest['version']) || empty($manifest['name'])) {
            $this->error('extension/manifest.json is invalid (needs name + version).');

            return self::FAILURE;
        }

        $version = (string) $manifest['version'];
        $outDir = public_path('extension');

        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $versioned = $outDir."/ternis-link-extension-v{$version}.zip";
        $latest = $outDir.'/ternis-link-extension.zip';

        if ($this->option('print')) {
            $this->line("version: {$version}");
            $this->line("output: {$versioned}");

            return self::SUCCESS;
        }

        foreach ([$versioned, $latest] as $target) {
            if (! $this->zip($root, $target)) {
                return self::FAILURE;
            }
        }

        $size = number_format(filesize($versioned) / 1024, 1);

        $this->info("Built v{$version} ({$size} KB):");
        $this->line("  {$versioned}");
        $this->line("  {$latest}");

        return self::SUCCESS;
    }

    private function zip(string $root, string $target): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Cannot write {$target}.");

            return false;
        }

        $excluded = ['dist', '.git', '.DS_Store'];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($it as $file) {
            $rel = substr($file->getPathname(), strlen($root) + 1);

            foreach ($excluded as $skip) {
                if ($rel === $skip || str_starts_with($rel, $skip.'/') || basename($rel) === $skip) {
                    continue 2;
                }
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($rel);
            } else {
                $zip->addFile($file->getPathname(), $rel);
            }
        }

        // The extension must at least contain the MV3 entry points.
        foreach (['manifest.json', 'popup.html', 'popup.js', 'background.js'] as $required) {
            if ($zip->locateName($required) === false) {
                $this->error("Missing required file in zip: {$required}.");
                $zip->close();

                return false;
            }
        }

        $zip->close();

        return true;
    }
}
