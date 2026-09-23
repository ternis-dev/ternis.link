<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Best-effort GeoIP resolution for click analytics.
 *
 * Resolution order:
 *   1. Injected resolver callable (tests, or a MaxMind-style driver
 *      bound in AppServiceProvider for production).
 *   2. The CF-IPCountry header when served behind Cloudflare.
 *   3. Unknown (nulls — analytics queries already ignore nulls).
 *
 * To plug in a real database driver:
 *
 *   $this->app->singleton(GeoIpService::class, fn () => new GeoIpService(
 *       fn (Request $request) => ['country_code' => ..., 'city' => ...],
 *   ));
 */
class GeoIpService
{
    /**
     * @param  callable|null  $resolver  fn(Request $request): array{country_code?: ?string, city?: ?string}
     */
    public function __construct(
        private mixed $resolver = null,
    ) {}

    /**
     * @return array{country_code: ?string, city: ?string}
     */
    public function lookup(Request $request): array
    {
        if ($this->resolver !== null) {
            return $this->normalize((array) call_user_func($this->resolver, $request));
        }

        $country = $request->header('CF-IPCountry');

        if (is_string($country) && preg_match('/^[a-z]{2}$/i', trim($country))) {
            return ['country_code' => strtoupper(trim($country)), 'city' => null];
        }

        return ['country_code' => null, 'city' => null];
    }

    /**
     * @return array{country_code: ?string, city: ?string}
     */
    private function normalize(array $result): array
    {
        $country = $result['country_code'] ?? $result['country'] ?? null;
        $country = is_string($country) && preg_match('/^[a-z]{2}$/i', trim($country))
            ? strtoupper(trim($country))
            : null;

        $city = $result['city'] ?? null;
        $city = is_string($city) && trim($city) !== ''
            ? substr(trim($city), 0, 255)
            : null;

        return ['country_code' => $country, 'city' => $city];
    }
}
