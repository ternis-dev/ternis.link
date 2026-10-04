<?php

namespace App\Http\Controllers;

use App\Support\DomainUrls;
use Illuminate\Support\Str;

/**
 * Static app pages under the /pages/ namespace (ternis.link only).
 *
 * GET /pages/legal/{slug} — Markdown-driven legal pages (privacy,
 * terms, imprint). Source lives in resources/legal/*.md; only
 * allowlisted slugs render (no traversal, no arbitrary files).
 */
class PagesController extends Controller
{
    /**
     * @var array<string, string> slug => page title (rendered locally)
     */
    public const PAGES = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
    ];

    /**
     * @var array<string, string> slug => canonical URL (single source
     *                            of truth lives on ternis.dev, never duplicated here).
     */
    public const REDIRECTS = [
        'imprint' => 'https://ternis.dev/en/legal/imprint',
    ];

    public function legal(string $slug)
    {
        if ($slug === 'imprint' || $slug === 'impressum') {
            return DomainUrls::handleImpressumRedirect(request());
        }

        if (isset(self::REDIRECTS[$slug])) {
            return redirect()->away(self::REDIRECTS[$slug], 302);
        }

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
            'redirects' => self::REDIRECTS,
            'current' => $slug,
        ]);
    }

    /**
     * Markdown twin of a legal page (GET /pages/legal/{slug}.md,
     * text/markdown) — serves the source file verbatim so agents and
     * llms-full.txt consumers get exactly what the HTML renders.
     */
    public function legalMd(string $slug)
    {
        if (isset(self::REDIRECTS[$slug])) {
            return redirect()->away(self::REDIRECTS[$slug], 302);
        }

        if (! isset(self::PAGES[$slug])) {
            abort(404);
        }

        $path = resource_path("legal/{$slug}.md");

        if (! is_file($path)) {
            abort(404);
        }

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }
}
