<?php

namespace App\Http\Controllers;

use App\Support\ContentCollection;
use Illuminate\Support\Str;

/**
 * File-driven collections under /pages/{changelog,news,blog}.
 *
 * Index + per-entry pages (HTML) with text/markdown twins ({uri}.md).
 * Ternis host only, same pinning as the other /pages/* content.
 * Adding a post is adding resources/content/{collection}/{slug}.md.
 */
class ContentController extends Controller
{
    public const MARKDOWN = 'text/markdown; charset=UTF-8';

    public function index(string $collection)
    {
        $meta = $this->meta($collection);

        return view('content.index', [
            'collection' => $collection,
            'meta' => $meta,
            'entries' => ContentCollection::entries($collection),
            'collections' => ContentCollection::COLLECTIONS,
        ]);
    }

    public function indexMd(string $collection)
    {
        $meta = $this->meta($collection);

        return response()->view('content.index-md', [
            'collection' => $collection,
            'meta' => $meta,
            'entries' => ContentCollection::entries($collection),
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }

    public function show(string $collection, string $slug)
    {
        $meta = $this->meta($collection);
        $entry = ContentCollection::entry($collection, $slug);

        if ($entry === null) {
            abort(404);
        }

        // Undated filename slugs 301 to the canonical dated URL so
        // old links keep working and crawlers consolidate.
        if ($slug !== $entry['canonical']) {
            return redirect($this->canonicalUrl($collection, $entry['canonical']), 301);
        }

        $neighbors = $this->neighbors($collection, $entry['slug']);
        $toc = $this->tableOfContents($entry['body']);
        $html = Str::markdown($entry['body']);

        // Anchor every h2 in document order so the TOC links land.
        foreach ($toc as $item) {
            $html = preg_replace(
                '/<h2>/',
                '<h2 id="'.e($item['id']).'">',
                $html,
                1
            );
        }

        return view('content.show', [
            'collection' => $collection,
            'meta' => $meta,
            'entry' => $entry,
            'html' => $html,
            'toc' => $toc,
            'readingTime' => max(1, (int) round(str_word_count(strip_tags($entry['body'])) / 200)),
            'newer' => $neighbors['newer'],
            'older' => $neighbors['older'],
        ]);
    }

    public function showMd(string $collection, string $slug)
    {
        $this->meta($collection);
        $entry = ContentCollection::entry($collection, $slug);

        if ($entry === null) {
            abort(404);
        }

        if ($slug !== $entry['canonical']) {
            return redirect($this->canonicalUrl($collection, $entry['canonical'].'.md'), 301);
        }

        $md = '# '.$entry['title']."\n\n"
           .'> '.$entry['date'].' · '.$entry['description']."\n\n"
           .$entry['body'];

        return response($md, 200, ['Content-Type' => self::MARKDOWN]);
    }

    /**
     * Absolute canonical entry URL, preserving the query string.
     */
    private function canonicalUrl(string $collection, string $canonical): string
    {
        $query = request()->getQueryString();

        return url('/pages/'.$collection.'/'.$canonical).($query ? '?'.$query : '');
    }

    /**
     * Table of contents from ## headings (needs 2+ to be useful).
     * Duplicate titles get suffixed ids, mirroring common renderers.
     *
     * @return list<array{title: string, id: string}>
     */
    private function tableOfContents(string $body): array
    {
        $toc = [];
        $seen = [];

        foreach (explode("\n", $body) as $line) {
            if (! str_starts_with($line, '## ')) {
                continue;
            }

            $title = trim(substr($line, 3));

            if ($title === '') {
                continue;
            }

            $id = Str::slug($title);

            if (isset($seen[$id])) {
                $seen[$id]++;
                $id .= '-'.$seen[$id];
            } else {
                $seen[$id] = 1;
            }

            $toc[] = ['title' => $title, 'id' => $id];
        }

        return count($toc) >= 2 ? $toc : [];
    }

    /**
     * Adjacent entries for prev/next navigation. entries() is
     * newest-first, so the previous item is the newer one.
     *
     * @return array{newer: array{slug: string, title: string}|null, older: array{slug: string, title: string}|null}
     */
    private function neighbors(string $collection, string $slug): array
    {
        $entries = ContentCollection::entries($collection);

        foreach ($entries as $index => $entry) {
            if ($entry['slug'] === $slug) {
                return [
                    'newer' => $entries[$index - 1] ?? null,
                    'older' => $entries[$index + 1] ?? null,
                ];
            }
        }

        return ['newer' => null, 'older' => null];
    }

    /**
     * @return array{title: string, subtitle: string, item: string}
     */
    private function meta(string $collection): array
    {
        $meta = ContentCollection::COLLECTIONS[$collection] ?? null;

        if ($meta === null) {
            abort(404);
        }

        return $meta;
    }
}
