<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SessionCookieDomainTest extends TestCase
{
    use RefreshDatabase;

    public static function hosts(): array
    {
        return [
            'public shortener' => ['href.nz', null],
            'other shortener' => ['meinlink.at', null],
            'domain suffix lookalike' => ['notternis.link', null],
            'domain prefix lookalike' => ['ternis.link.example.com', null],
            'apex' => ['ternis.link', '.ternis.link'],
            'dashboard' => ['dash.ternis.link', '.ternis.link'],
            'public dashboard' => ['my.ternis.link', '.ternis.link'],
        ];
    }

    #[DataProvider('hosts')]
    public function test_session_and_csrf_cookies_use_a_valid_domain(string $host, ?string $expectedDomain): void
    {
        config(['session.domain' => '.ternis.link']);
        // A web route isolates the cookie behavior from page rendering and auth.
        // Dotted hostnames are literal map keys, not nested config paths.
        config(['domains.map' => [$host => 'public']]);
        Route::middleware('web')->get('/testing/session-cookie', fn () => response()->json([
            'token' => csrf_token(),
        ]));

        $response = $this->get('https://'.$host.'/testing/session-cookie');
        $response->assertOk();

        $cookies = collect($response->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());
        foreach ([config('session.cookie'), 'XSRF-TOKEN'] as $name) {
            $this->assertTrue($cookies->has($name));
            $this->assertSame($expectedDomain, $cookies[$name]->getDomain());
        }
        $this->assertSame('.ternis.link', config('session.domain'));
    }
}
