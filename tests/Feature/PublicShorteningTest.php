<?php

namespace Tests\Feature;

use App\Exceptions\DemoUrlBlockedException;
use App\Jobs\EvaluateLinkThreatJob;
use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use App\Models\Link;
use App\Models\SpamAppeal;
use App\Models\User;
use App\Services\AiThreatDetectionService;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PublicShorteningTest extends TestCase
{
    use RefreshDatabase;

    private Domain $publicDomain;

    private Domain $ternisDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->ternisDomain = Domain::where('hostname', 'ternis.link')->firstOrFail();
    }

    public function test_demo_url_always_shows_a_security_validation_message_without_creating_a_link(): void
    {
        config(['ai_security.enabled' => false]);
        Queue::fake();

        foreach ([ShortenForm::class, \App\Livewire\Meinlink\ShortenForm::class] as $component) {
            Livewire::test($component)
                ->set('selectedDomain', 'href.nz')
                ->set('destination_url', DemoUrlBlockedException::URL)
                ->call('create')
                ->assertHasErrors('destination_url')
                ->assertSet('errorKind', 'security')
                ->assertSet('shortUrl', null)
                ->assertSee('Demo:');
        }

        $this->assertDatabaseMissing('links', [
            'destination_url' => DemoUrlBlockedException::URL,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_public_api_rejects_the_demo_url_with_a_validation_error(): void
    {
        $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => DemoUrlBlockedException::URL,
        ])->assertUnprocessable()->assertJsonValidationErrors('destination_url');
    }

    public function test_public_forms_show_an_immediate_block_and_allow_anonymous_appeals(): void
    {
        Queue::fake();
        $scanner = $this->mock(AiThreatDetectionService::class);
        $scanner->shouldReceive('shouldEvaluateLink')->andReturn(true);
        $scanner->shouldReceive('evaluateLink')->twice()->andReturnUsing(function (Link $link) {
            $link->update(['is_active' => false, 'tags' => ['quarantine', 'phishing']]);

            return null;
        });

        foreach ([ShortenForm::class, \App\Livewire\Meinlink\ShortenForm::class] as $component) {
            $url = 'https://example.com/security-'.Str::random(8);
            $form = Livewire::test($component)
                ->set('selectedDomain', 'href.nz')
                ->set('destination_url', $url)
                ->call('create')
                ->assertSet('shortUrl', null)
                ->assertSet('securityCheckLinkId', null)
                ->assertSet('destination_url', $url)
                ->assertSet('errorKind', 'security')
                ->assertHasErrors('destination_url')
                ->assertSee('abuse@ternis.dev');

            $link = Link::findOrFail($form->get('blockedLinkId'));
            $form->call('submitSecurityAppeal')
                ->assertHasErrors(['appealEmail', 'appealReason'])
                ->assertSet('appealSubmitted', false);
            $form->set('appealEmail', 'guest@example.com')
                ->set('appealReason', 'This is my legitimate website. Please review it.')
                ->call('submitSecurityAppeal')
                ->assertSet('appealSubmitted', true);

            $this->assertDatabaseHas('spam_appeals', [
                'link_id' => $link->id,
                'user_id' => null,
                'contact_email' => 'guest@example.com',
                'status' => 'pending',
            ]);
            $this->assertFalse($link->fresh()->is_active);
            $form->call('submitSecurityAppeal');
            $this->assertSame(1, $link->hasMany(SpamAppeal::class)->count());
            $form->call('resetForm')->assertHasNoErrors()->assertSet('blockedLinkId', null);
        }
        Queue::assertNotPushed(EvaluateLinkThreatJob::class);
    }

    public function test_anonymous_appeal_cannot_be_submitted_without_a_blocked_link(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('appealEmail', 'guest@example.com')
            ->set('appealReason', 'Please unblock this website.')
            ->call('submitSecurityAppeal')
            ->assertSet('appealSubmitted', false);
        $this->assertDatabaseCount('spam_appeals', 0);
    }

    public function test_immediate_ai_block_does_not_show_a_success_result(): void
    {
        Queue::fake();
        $link = app(LinkService::class)->create(
            destinationUrl: 'https://example.com/security-immediate',
            domain: $this->publicDomain,
            user: null,
        );
        $link->update(['is_active' => false, 'tags' => ['quarantine']]);
        $this->mock(LinkService::class)->shouldReceive('create')->once()->andReturn($link);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', $link->destination_url)
            ->call('create')
            ->assertSet('shortUrl', null)
            ->assertHasErrors('destination_url')
            ->assertSee('URL blocked by our security check')
            ->assertDontSee('Done — your short link is ready');
    }

    public function test_security_poll_keeps_an_active_link_available(): void
    {
        Queue::fake();

        $form = Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/security-active')
            ->call('create');
        $shortUrl = $form->get('shortUrl');

        $form->call('checkCreatedLinkSafety')
            ->assertSet('shortUrl', $shortUrl)
            ->assertHasNoErrors();

        $form->call('resetForm')
            ->call('checkCreatedLinkSafety')
            ->assertHasNoErrors()
            ->assertSet('securityCheckLinkId', null);
    }

    public function test_guest_can_create_link_via_public_api_without_token(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/guest-link',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['slug', 'destination_url', 'short_url']);
        $this->assertStringStartsWith('https://href.nz/', $response->json('short_url'));
        $this->assertEquals(LinkService::GUEST_SLUG_LENGTH, strlen($response->json('slug')));

        $this->assertDatabaseHas('links', [
            'slug' => $response->json('slug'),
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
        ]);

        $link = Link::where('slug', $response->json('slug'))->firstOrFail();
        $this->assertNotNull($link->creator_ip_hash);
        $this->assertEquals(hash('sha256', '127.0.0.1'), $link->creator_ip_hash);
    }

    public function test_guest_custom_slug_is_rejected(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/custom',
            'slug' => 'guest-slug-1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseMissing('links', ['slug' => 'guest-slug-1']);
    }

    public function test_guest_custom_slug_below_minimum_is_rejected(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/short',
            'slug' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseMissing('links', ['slug' => 'abc']);
    }

    public function test_guest_custom_slug_via_service_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(LinkService::class)->create(
            destinationUrl: 'https://example.com/service-custom',
            domain: $this->publicDomain,
            user: null,
            customSlug: 'custom-slug-1',
            creatorIpHash: hash('sha256', '127.0.0.1'),
        );
    }

    public function test_guest_cannot_create_on_non_public_domain(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/nope',
            'domain_id' => $this->ternisDomain->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('domain_id');
        $this->assertDatabaseMissing('links', ['destination_url' => 'https://example.com/nope']);
    }

    public function test_public_api_is_throttled_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('http://links.t-api.de/v1/links/public', [
                'destination_url' => 'https://example.com/throttle-'.$i,
            ])
                ->assertStatus(201);
        }

        $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/throttle-over',
        ])
            ->assertStatus(429);
    }

    public function test_anonymous_daily_quota_is_enforced(): void
    {
        $service = app(LinkService::class);
        $ipHash = hash('sha256', '203.0.113.7');

        for ($i = 0; $i < LinkService::ANONYMOUS_DAILY_LIMIT; $i++) {
            $service->create(
                destinationUrl: 'https://example.com/quota-'.$i,
                domain: $this->publicDomain,
                creatorIpHash: $ipHash,
            );
        }

        $this->expectException(ValidationException::class);

        $service->create(
            destinationUrl: 'https://example.com/quota-over',
            domain: $this->publicDomain,
            creatorIpHash: $ipHash,
        );
    }

    public function test_guest_can_create_link_via_web_form(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/web-guest')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($value) => is_string($value) && str_starts_with($value, 'https://href.nz/'));

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/web-guest',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
        ]);

        $link = Link::where('destination_url', 'https://example.com/web-guest')->firstOrFail();
        $this->assertEquals(LinkService::GUEST_SLUG_LENGTH, strlen($link->slug));
    }

    public function test_landing_page_shows_guest_form_on_public_domain(): void
    {
        $response = $this->get('http://href.nz/');

        $response->assertStatus(200);
        $response->assertSee('Shorten a link', escape: false);
    }

    public function test_new_page_shows_clean_guest_form_on_public_domain(): void
    {
        $response = $this->get('http://href.nz/new');

        $response->assertOk()
            ->assertSee('make something', escape: false)
            ->assertSee('continue as a guest', escape: false)
            ->assertSee('Shorten a link', escape: false);
    }

    public function test_new_page_is_not_available_on_other_domains(): void
    {
        $this->get('http://href.re/new')->assertNotFound();
    }

    public function test_form_tracks_live_url_state(): void
    {
        Livewire::test(ShortenForm::class)
            ->assertSet('urlState', 'idle')
            ->set('destination_url', 'not a url')
            ->assertSet('urlState', 'invalid')
            ->set('destination_url', 'https://example.com/looks-good')
            ->assertSet('urlState', 'valid')
            ->set('destination_url', '')
            ->assertSet('urlState', 'idle');
    }

    public function test_form_shows_remaining_guest_quota(): void
    {
        $test = Livewire::test(ShortenForm::class);
        $this->assertSame(LinkService::ANONYMOUS_DAILY_LIMIT, $test->get('quotaLeft'));
        $test->assertSee(LinkService::ANONYMOUS_DAILY_LIMIT.' of '.LinkService::ANONYMOUS_DAILY_LIMIT.' free links left today');

        Link::create([
            'slug' => 'quota-'.Str::random(6),
            'destination_url' => 'https://example.com/quota',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);

        $test = Livewire::test(ShortenForm::class);
        $this->assertSame(LinkService::ANONYMOUS_DAILY_LIMIT - 1, $test->get('quotaLeft'));
    }

    public function test_successful_create_resets_url_state(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/state-reset')
            ->assertSet('urlState', 'valid')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('urlState', 'idle');
    }

    public function test_preview_shows_for_valid_input(): void
    {
        $url = 'https://example.com/preview-me';

        Livewire::test(ShortenForm::class)
            ->set('destination_url', $url)
            ->assertSee('You’re shortening', escape: false)
            ->assertSee('href.nz/••••••••', escape: false)
            ->assertSee(strlen($url).' / '.LinkService::PUBLIC_MAX_URL_LENGTH);
    }

    public function test_quota_error_flags_oops_card_with_login_nudge(): void
    {
        for ($i = 0; $i < LinkService::ANONYMOUS_DAILY_LIMIT; $i++) {
            Link::create([
                'slug' => 'quota-'.Str::random(8),
                'destination_url' => 'https://example.com/quota-'.$i,
                'domain_id' => $this->publicDomain->id,
                'user_id' => null,
                'creator_ip_hash' => hash('sha256', '127.0.0.1'),
                'is_active' => true,
            ]);
        }

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/over-quota')
            ->call('create')
            ->assertSet('quotaExceeded', true)
            ->assertSet('errorKind', 'quota')
            ->assertSee('Daily limit reached', escape: false)
            ->assertSee('higher daily limit');
    }

    public function test_invalid_submit_marks_field_with_error_state(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'nope')
            ->call('create')
            ->assertHasErrors('destination_url')
            ->assertSee('is-error', escape: false)
            ->assertSee('aria-invalid', escape: false);
    }

    public function test_missing_scheme_suggests_one_click_fix(): void
    {
        $test = Livewire::test(ShortenForm::class)
            ->set('destination_url', 'example.com/missing-scheme')
            ->assertSet('urlState', 'invalid')
            ->assertSee('Did you mean', escape: false)
            ->assertSee('https://example.com/missing-scheme');

        $test->call('applyFix')
            ->assertSet('destination_url', 'https://example.com/missing-scheme')
            ->assertSet('urlState', 'valid')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/missing-scheme',
        ]);
    }

    public function test_duplicate_destination_shows_existing_link(): void
    {
        $link = Link::create([
            'slug' => 'alreadyhere1',
            'destination_url' => 'https://example.com/already-here',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/already-here')
            ->assertSee('Already shortened', escape: false)
            ->assertSee('href.nz/alreadyhere1');
    }

    public function test_authenticated_duplicate_is_not_shown_to_guests(): void
    {
        $user = User::factory()->create();

        Link::create([
            'slug' => 'memberlink1',
            'destination_url' => 'https://example.com/member-only',
            'domain_id' => $this->publicDomain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/member-only')
            ->assertDontSee('Already shortened', escape: false)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($value) => is_string($value) && str_starts_with($value, 'https://href.nz/'));
    }

    public function test_error_kinds_match_failure_reason(): void
    {
        // Too long.
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/'.str_repeat('a', LinkService::PUBLIC_MAX_URL_LENGTH))
            ->call('create')
            ->assertSet('errorKind', 'too_long')
            ->assertSee('That link is too long', escape: false);

        // Plain invalid.
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'nope')
            ->call('create')
            ->assertSet('errorKind', 'invalid');
    }

    public function test_business_and_public_landings_differ(): void
    {
        $public = $this->get('http://href.nz/');
        $public->assertStatus(200);
        $public->assertSee('href<span>.nz</span>', escape: false);

        $business = $this->get('http://href.re/');
        $business->assertStatus(200);
        $business->assertSee('href<span>.re</span>', escape: false);

        // Business landing offers no guest form.
        $business->assertDontSee('Shorten a link', escape: false);
    }

    public function test_created_guest_link_redirects(): void
    {
        $slug = 'guest-'.Str::random(8);
        Link::create([
            'slug' => $slug,
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'is_active' => true,
        ]);

        $response = $this->get('http://href.nz/'.$slug);

        $response->assertRedirect('https://example.com/target');
    }
}
