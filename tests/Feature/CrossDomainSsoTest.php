<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CrossDomainSso;
use App\Support\SsoHosts;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossDomainSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    public function test_every_configured_host_is_allowed_but_foreign_ones_are_not(): void
    {
        foreach (array_keys(config('domains.map')) as $host) {
            if (config('domains.map')[$host] !== 'api') {
                $this->assertTrue(SsoHosts::isAllowed($host), $host);
            }
        }
        $this->assertTrue(SsoHosts::isAllowed('foo.href.nz'));
        $this->assertFalse(SsoHosts::isAllowed('evil.example.com'));
        $this->assertFalse(SsoHosts::isAllowed('href.nz.evil.com'));
        $this->assertFalse(SsoHosts::isAllowed('links.t-api.de'));
    }

    public function test_safe_path_rejects_open_redirects(): void
    {
        foreach (['//evil.com', 'https://evil.com', '/\\evil.com', "/a\r\nb", '', null] as $bad) {
            $this->assertSame('/', SsoHosts::safePath($bad));
        }
        $this->assertSame('/links?x=1', SsoHosts::safePath('/links?x=1'));
    }

    public function test_guest_start_bounces_to_identity_host(): void
    {
        $this->get('http://my.href.nz/auth/sso/start?to=/links')
            ->assertRedirect('http://dash.ternis.link/auth/sso/issue?host=my.href.nz&to=%2Flinks');
    }

    public function test_issue_for_signed_in_user_hands_token_to_target_host_and_logs_in_there(): void
    {
        $user = User::factory()->create();

        $location = $this->actingAs($user)
            ->get('http://dash.ternis.link/auth/sso/issue?host=meinlink.at&to=/')
            ->assertRedirectContains('http://meinlink.at/auth/sso/consume?token=')
            ->headers->get('Location');

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->get($location)->assertRedirect('http://meinlink.at/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_token_is_single_use_and_bound_to_its_host(): void
    {
        $user = User::factory()->create();
        $sso = app(CrossDomainSso::class);

        $token = $sso->issue($user, 'href.nz');
        $this->assertNull($sso->consume($token, 'meinlink.at'), 'burned by wrong host');
        $this->assertNull($sso->consume($token, 'href.nz'), 'single use');

        $token = $sso->issue($user, 'href.nz');
        $this->assertSame($user->id, $sso->consume($token, 'href.nz')['user']->id);
        $this->assertNull($sso->consume($token, 'href.nz'));
    }

    public function test_issue_rejects_unknown_target_host(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('http://dash.ternis.link/auth/sso/issue?host=evil.example.com')
            ->assertStatus(400);
    }

    public function test_silent_issue_for_guest_returns_none_without_login(): void
    {
        $this->get('http://dash.ternis.link/auth/sso/issue?host=href.nz&to=/&silent=1')
            ->assertRedirect('http://href.nz/auth/sso/consume?none=1&to=%2F');
    }

    public function test_guest_issue_goes_through_login_and_resumes(): void
    {
        $url = 'http://dash.ternis.link/auth/sso/issue?host=my.href.nz&to=/';
        $this->get($url)->assertRedirect('http://dash.ternis.link/login');
        $this->assertStringContainsString('/auth/sso/issue?host=my.href.nz', session('url.intended'));
    }

    public function test_invalid_token_sends_user_back_to_login(): void
    {
        $this->get('http://href.nz/auth/sso/consume?token=nope')->assertRedirect('http://href.nz/login');
        $this->assertGuest();
    }

    public function test_auth_redirect_on_foreign_domain_starts_handoff(): void
    {
        $this->get('http://my.href.nz/auth/redirect')->assertRedirect('/auth/sso/start?to=%2F');
    }

    public function test_logout_anywhere_revokes_other_hosts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['sso_login_at' => now()->subMinute()->toIso8601String()]);

        app(CrossDomainSso::class)->markLoggedOut($user);

        $this->get('http://ternis.link/pages/stats');
        $this->assertGuest();
    }

    public function test_landing_pages_do_not_redirect_guests_to_sso_silent(): void
    {
        $headers = ['Accept' => 'text/html', 'User-Agent' => 'Mozilla/5.0 Firefox'];

        // Guests on href.nz, href.re, meinlink.at must see the landing page, not be bounced to sso
        $this->withHeaders($headers)->get('http://href.nz/')->assertOk();
        $this->withHeaders($headers)->get('http://href.re/')->assertOk();
        $this->withHeaders($headers)->get('http://meinlink.at/')->assertOk();
    }
}
