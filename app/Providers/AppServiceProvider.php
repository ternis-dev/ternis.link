<?php

namespace App\Providers;

use App\Support\CommitVersion;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // HTTP-layer guard for API v1 (per-plan quotas live in LinkService).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stamp built stylesheets with the short commit id
        // (/build/assets/app-abc123.css?v=181242c) so a served CSS file can
        // be traced back to its commit and caches bust when a new build
        // ships. Scripts and other bundles keep Vite's content hash only.
        Vite::createAssetPathsUsing(function (string $path, ?bool $secure = null) {
            $url = asset($path, $secure);

            return str_ends_with($path, '.css') ? CommitVersion::forUrl($url) : $url;
        });

        // Override Livewire's default pagination views with our own
        // Tailwind styling (grayscale system, dark mode, focus rings,
        // proper aria). App paths are checked before the package's, so
        // this applies to every paginated Livewire component.
        View::getFinder()->prependNamespace(
            'livewire',
            resource_path('views/vendor/livewire')
        );
    }
}
