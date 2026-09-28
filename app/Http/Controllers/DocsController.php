<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
/**
 * Developer docs on the docs host (docs.ternis.link): renders the
 * Markdown files from docs/*.md as HTML, each with a raw Markdown
 * twin ({uri}.md) for crawlers and agents. No login, no chrome —
 * same file-driven pattern as /pages/* content, but the source of
 * truth stays the repo docs so code and documentation cannot drift.
 */
class DocsController extends Controller
{
    public const MARKDOWN = 'text/markdown; charset=UTF-8';

    /**
     * Slug → source file allowlist. Unknown slugs match no page (404).
     */
    public const PAGES = [
        'readme' => 'README.md',
        'architecture' => 'architecture.md',
        'authentication' => 'authentication.md',
        'domains-routing' => 'domains-routing.md',
        'links' => 'links.md',
        'extension' => 'extension.md',
    ];

    /**
     * One-line, user-facing summaries for the index cards.
     */
    public const DESCRIPTIONS = [
        'readme' => 'What ternis.link is and where everything lives.',
        'architecture' => 'How a link goes from paste to redirect to stats.',
        'authentication' => 'Sign in without a password, plus API keys.',
        'domains-routing' => 'Which host does what, plus custom domains.',
        'links' => 'Shorten links, custom slugs, QR codes, clicks.',
        'extension' => 'Shorten any tab: popup, menu, omnibox.',
    ];

    public function index()
    {
        return view('docs.index', [
            'pages' => collect(self::PAGES)->map(fn ($file, $slug) => [
                'slug' => $slug,
                'title' => $this->title($slug, $file),
                'description' => self::DESCRIPTIONS[$slug] ?? '',
            ])->values(),
        ]);
    }

    public function show(string $slug)
    {
        // Localhost dev: pinned dashboard routes never match, so a docs
        // slug that doubles as a dashboard URI (/links) falls through
        // here with the dashboard type. Delegate it (same pattern as
        // the /new closure) instead of leaking the docs page onto the
        // dashboard — every other dashboard-typed slug 404s below.
        if ($slug === 'links' && request()->attributes->get('domain_type') === 'dashboard') {
            if (! auth()->check()) {
                return redirect('/login');
            }

            return app(DashboardController::class)->links();
        }

        $page = $this->page($slug);

        if ($page === null || request()->attributes->get('domain_type') !== 'docs') {
            abort(404);
        }

        return view('docs.show', [
            'slug' => $slug,
            'title' => $page['title'],
            'html' => Str::markdown($page['body']),
            'pages' => $this->nav(),
        ]);
    }

    public function showMd(string $slug)
    {
        $page = $this->page($slug);

        if ($page === null) {
            abort(404);
        }

        return response($page['raw'], 200, ['Content-Type' => self::MARKDOWN]);
    }

    /**
     * Raw OpenAPI 3.1 document (source: docs/api-v1-openapi.yaml).
     */
    public function openapi()
    {
        $path = base_path('docs/api-v1-openapi.yaml');

        if (! is_file($path)) {
            abort(404);
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            abort(500);
        }

        return response($contents, 200, ['Content-Type' => 'application/yaml']);
    }

    /**
     * @return array{title: string, body: string, raw: string}|null
     */
    private function page(string $slug): ?array
    {
        $file = self::PAGES[$slug] ?? null;

        if ($file === null) {
            return null;
        }

        $path = base_path('docs/'.$file);

        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            abort(500);
        }

        return [
            'title' => $this->title($slug, $file),
            'body' => $raw,
            'raw' => $raw,
        ];
    }

    private function nav(): array
    {
        return collect(self::PAGES)->map(fn ($file, $slug) => [
            'slug' => $slug,
            'title' => $this->title($slug, $file),
            'description' => self::DESCRIPTIONS[$slug] ?? '',
        ])->values()->all();
    }

    private function title(string $slug, string $file): string
    {
        $path = base_path('docs/'.$file);

        if (is_file($path)) {
            foreach (explode("\n", file_get_contents($path) ?: '') as $line) {
                if (str_starts_with($line, '# ')) {
                    return trim(substr($line, 2));
                }
            }
        }

        return Str::headline($slug);
    }
}
