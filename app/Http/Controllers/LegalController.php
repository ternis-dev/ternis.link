<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

/**
 * GET /legal/{slug} — Markdown-driven legal pages (privacy, terms,
 * imprint). Source lives in resources/legal/*.md; only allowlisted
 * slugs render (no traversal, no arbitrary files).
 */
class LegalController extends Controller
{
    /**
     * @var array<string, string> slug => page title
     */
    public const PAGES = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'imprint' => 'Imprint',
    ];

    public function show(string $slug)
    {
        if (! isset(self::PAGES[$slug])) {
            abort(404);
        }

        $path = resource_path("legal/{$slug}.md");

        if (! is_file($path)) {
            abort(404);
        }

        return view('legal.show', [
            'title' => self::PAGES[$slug],
            'html' => Str::markdown((string) file_get_contents($path)),
            'pages' => self::PAGES,
            'current' => $slug,
        ]);
    }
}
