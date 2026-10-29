<?php

use App\Http\Controllers\ContentController;
use App\Support\ContentCollection;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Blog & Content Module Routes (changelog, news, blog, RSS/XML feeds)
|--------------------------------------------------------------------------
*/

// Short memorable URLs for the blog (ternis.link/blog, /blogs)
foreach (['/blogs', '/blog'] as $shortcut) {
    Route::get($shortcut.'{any?}', function () use ($shortcut) {
        if (request()->attributes->get('domain_type') !== 'ternis') {
            abort(404);
        }

        $suffix = substr(request()->getPathInfo(), strlen($shortcut));
        $query = request()->getQueryString();
        $qs = $query ? '?'.$query : '';

        // Entry shortcuts resolve straight to the canonical dated URL
        if (preg_match('#^/([a-z0-9-]+)(\.md)?$#', $suffix, $m)) {
            $entry = ContentCollection::entry('blog', $m[1]);

            if ($entry !== null) {
                return redirect('/pages/blog/'.$entry['canonical'].($m[2] ?? '').$qs, 301);
            }
        }

        return redirect('/pages/blog'.rtrim($suffix, '/').$qs, 301);
    })->where('any', '(/.*)?');
}

Route::middleware(['ensure.domain:ternis'])
    ->where(['collection' => 'changelog|news|blog'])
    ->name('pages.collection.')
    ->group(function () {
        Route::get('/pages/{collection}', [ContentController::class, 'index'])->name('index');
        Route::get('/pages/{collection}.md', [ContentController::class, 'indexMd'])->name('index-md');
        Route::get('/pages/{collection}.xml', [ContentController::class, 'rss'])->name('xml');
        Route::get('/pages/{collection}.rss', [ContentController::class, 'rss'])->name('rss');
        Route::get('/pages/{collection}/feed.xml', [ContentController::class, 'rss']);
        Route::get('/pages/{collection}/rss.xml', [ContentController::class, 'rss']);
        Route::get('/pages/{collection}/{slug}', [ContentController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('show');
        Route::get('/pages/{collection}/{slug}.md', [ContentController::class, 'showMd'])
            ->where('slug', '[a-z0-9-]+')
            ->name('show-md');
    });

Route::middleware(['ensure.domain:ternis'])->group(function () {
    Route::get('/pages/feed.xml', fn () => redirect('/pages/blog.xml', 301))->name('pages.feed');
    Route::get('/pages/rss.xml', fn () => redirect('/pages/blog.rss', 301));
    Route::get('/feed.xml', fn () => redirect('/pages/blog.xml', 301))->name('feed');
    Route::get('/rss.xml', fn () => redirect('/pages/blog.rss', 301));
    Route::get('/blog.xml', fn () => redirect('/pages/blog.xml', 301));
    Route::get('/blog.rss', fn () => redirect('/pages/blog.rss', 301));
});
