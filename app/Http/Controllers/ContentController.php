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

        $neighbors = $this->neighbors($collection, $slug);

        return view('content.show', [
            'collection' => $collection,
            'meta' => $meta,
            'entry' => $entry,
            'html' => Str::markdown($entry['body']),
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

        $md = '# '.$entry['title']."\n\n"
           .'> '.$entry['date'].' · '.$entry['description']."\n\n"
           .$entry['body'];

        return response($md, 200, ['Content-Type' => self::MARKDOWN]);
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
