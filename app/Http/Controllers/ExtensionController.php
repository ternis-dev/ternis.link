<?php

namespace App\Http\Controllers;

/**
 * Chrome extension download + metadata (ternis.link only).
 *
 * The extension itself lives in `extension/` (vanilla MV3, no build).
 * `manifest.json` is the single source of truth for the version; the
 * download zip is produced by `php artisan extension:build` into
 * `public/extension/` and served here. No login, no personal data.
 *
 * GET /pages/extension             → HTML download page
 * GET /pages/extension.md          → Markdown twin (agents, llms-full)
 * GET /pages/extension/version     → { name, version, download_url, … } JSON
 * GET /pages/extension/download    → the zip (404 with build hint when missing)
 */
class ExtensionController extends Controller
{
    public const MARKDOWN = 'text/markdown; charset=UTF-8';

    public function index()
    {
        $meta = $this->meta();

        return view('pages.extension.index', [
            'version' => $meta['version'],
            'downloadUrl' => $meta['download_url'],
            'downloadReady' => $meta['download_ready'],
            'downloadSize' => $meta['download_size'],
            'manifest' => $meta['manifest'],
        ]);
    }

    public function indexMd()
    {
        $meta = $this->meta();

        return response()->view('pages.extension.index-md', [
            'version' => $meta['version'],
            'downloadUrl' => $meta['download_url'],
            'downloadReady' => $meta['download_ready'],
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }

    public function version()
    {
        return response()->json($this->meta());
    }

    public function download()
    {
        $path = $this->zipPath();

        if ($path === null) {
            // Friendlier than a bare 404: the page already explains the
            // pending build, so send users there instead of an error.
            return redirect()->route('pages.extension.index');
        }

        // Unversioned URL serving a rebuildable artifact — never let a
        // stale cached copy survive a fresh `extension:build`.
        return response()->download($path, basename($path), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @return array{name: string, version: string, download_url: string, download_ready: bool, download_size: int|null, manifest: array}
     */
    private function meta(): array
    {
        $manifest = $this->manifest();
        $version = (string) ($manifest['version'] ?? '0.0.0');
        $zipPath = $this->zipPath();

        return [
            'name' => (string) ($manifest['name'] ?? 'ternis.link Shortener'),
            'version' => $version,
            'download_url' => url('/pages/extension/download'),
            'download_ready' => $zipPath !== null,
            'download_size' => $zipPath !== null ? filesize($zipPath) : null,
            'manifest' => $manifest,
        ];
    }

    /** Manifest source of truth; empty array when the file is missing/invalid. */
    private function manifest(): array
    {
        $path = base_path('extension/manifest.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** Versioned zip path when built, null otherwise. */
    private function zipPath(): ?string
    {
        $manifest = $this->manifest();
        $version = (string) ($manifest['version'] ?? '');

        if ($version === '') {
            return null;
        }

        $candidates = [
            public_path("extension/ternis-link-extension-v{$version}.zip"),
            public_path('extension/ternis-link-extension.zip'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
