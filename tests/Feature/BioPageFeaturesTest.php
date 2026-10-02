<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\PageAnalytics;
use App\Livewire\Bio\PageBuilder;
use App\Models\ApiKey;
use App\Models\BioPage;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\User;
use App\Services\BioService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BioPageFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create([
            'plan_id' => Plan::where('name', 'business')->firstOrFail()->id,
        ]);
        $this->domain = Domain::create([
            'hostname' => 'bio.example.com',
            'user_id' => $this->user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_password_locks_page_until_unlocked(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Secret']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);
        $bio->setPassword($page, 'correct-horse');

        $button = $page->fresh()->buttons()->firstOrFail();

        // Locked: interstitial, no content, nothing tracked.
        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('password-protected', escape: false)
            ->assertDontSee('Shop', escape: false);
        $this->get("http://bio.example.com/t/{$button->id}")->assertNotFound();
        $this->assertSame(0, $page->fresh()->view_count);

        // Wrong password stays locked.
        $this->post('http://bio.example.com/unlock/'.$page->id, ['password' => 'nope'])
            ->assertSessionHasErrors('password');

        // Right password unlocks the session.
        $this->post('http://bio.example.com/unlock/'.$page->id, ['password' => 'correct-horse'])
            ->assertRedirect('https://bio.example.com/');

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('Shop', escape: false);
        $this->assertSame(1, $page->fresh()->view_count);
    }

    public function test_unlock_is_throttled(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Secret']);
        app(BioService::class)->setPassword($page, 'correct-horse');

        for ($i = 0; $i < 10; $i++) {
            $this->post('http://bio.example.com/unlock/'.$page->id, ['password' => 'nope']);
        }

        $this->post('http://bio.example.com/unlock/'.$page->id, ['password' => 'nope'])
            ->assertStatus(429);
    }

    public function test_api_sets_and_clears_password(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [
            'password' => 'short',
        ], $this->headers())->assertStatus(422);

        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [
            'password' => 'long-enough-secret',
        ], $this->headers())->assertOk();

        $this->assertNotNull($page->fresh()->password_hash);

        // Hash never leaks through the API.
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}", $this->headers())
            ->assertOk()
            ->assertJsonMissing(['password_hash']);

        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [
            'remove_password' => true,
        ], $this->headers())->assertOk();

        $this->assertNull($page->fresh()->password_hash);
    }

    public function test_button_style_outline_renders(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root', 'button_style' => 'outline']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('background:transparent', escape: false);
    }

    public function test_thumbnails_and_social_icons_render(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop', 'thumbnail_url' => 'https://example.com/thumb.png'],
            ['label' => 'IG', 'kind' => 'social', 'destination_url' => 'https://instagram.com/x', 'icon' => 'instagram'],
        ], $this->user);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('https://example.com/thumb.png', escape: false)
            ->assertSee('socialrow', escape: false)
            ->assertSee('<svg', escape: false);
    }

    public function test_stats_include_referrers_countries_and_subpages(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);

        $this->get('http://bio.example.com/', ['Referer' => 'https://search.example/']);
        $this->get('http://bio.example.com/socials');

        $stats = $this->getJson("http://links.t-api.de/v1/bio-pages/{$root->id}/stats?days=7", $this->headers());
        $stats->assertOk()
            ->assertJsonFragment(['referrer' => 'https://search.example/'])
            ->assertJsonFragment(['slug' => 'socials', 'title' => 'Socials']);
    }

    public function test_builder_sets_password_and_style(): void
    {
        $user = $this->user;
        $domain = $this->domain;

        $page = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('button_style', 'soft')
            ->set('page_password', 'builder-secret')
            ->call('savePage')
            ->assertHasNoErrors();

        $fresh = $page->fresh();
        $this->assertSame('soft', $fresh->button_style);
        $this->assertNotNull($fresh->password_hash);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('removePassword')
            ->assertHasNoErrors();

        $this->assertNull($page->fresh()->password_hash);
    }

    public function test_cover_and_footer_render(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, [
            'title' => 'Root',
            'cover_url' => 'https://example.com/cover.jpg',
            'footer_text' => '© My Brand 2026',
        ]);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('https://example.com/cover.jpg', escape: false)
            ->assertSee('© My Brand 2026', escape: false)
            ->assertDontSee('Powered by ternis.link', escape: false);
    }

    public function test_contact_button_downloads_vcard(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Jane Doe', 'kind' => 'contact', 'contact_email' => 'jane@example.com', 'contact_phone' => '+49123456789'],
        ], $this->user);

        $button = $page->fresh()->buttons()->firstOrFail();

        $response = $this->get("http://bio.example.com/t/{$button->id}");
        $response->assertOk()->assertHeader('Content-Type', 'text/vcard; charset=utf-8');
        $this->assertStringContainsString('FN:Jane Doe', $response->getContent());
        $this->assertStringContainsString('EMAIL:jane@example.com', $response->getContent());
        $this->assertSame(1, $button->fresh()->tap_count);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('⤓', escape: false);
    }

    public function test_contact_requires_email_or_phone(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->expectException(ValidationException::class);
        app(BioService::class)->syncButtons($page, [
            ['label' => 'Nobody', 'kind' => 'contact'],
        ], $this->user);
    }

    public function test_duplicate_sub_page_in_builder(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);
        $bio->syncButtons($sub, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);

        // A tap on the original, so the copy must start at zero.
        $button = $sub->fresh()->buttons()->firstOrFail();
        $this->get("http://bio.example.com/t/{$button->id}")->assertRedirect();

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('duplicateSub', $sub->id)
            ->assertHasNoErrors();

        $copy = BioPage::where('parent_id', $root->id)->where('slug', 'socials-copy')->firstOrFail();
        $this->assertSame('Socials (copy)', $copy->title);
        $this->assertFalse((bool) $copy->is_active);
        $this->assertSame(1, $copy->buttons()->count());
        $this->assertSame(0, $copy->buttons()->firstOrFail()->tap_count);
    }

    public function test_api_duplicates_root_to_another_domain(): void
    {
        $second = Domain::create([
            'hostname' => 'bio2.example.com',
            'user_id' => $this->user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root', 'footer_text' => 'Hi']);
        $bio->syncButtons($root, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);

        $response = $this->postJson("http://links.t-api.de/v1/bio-pages/{$root->id}/duplicate", [
            'domain_id' => $second->id,
        ], $this->headers());

        $response->assertCreated();
        $this->assertSame('Hi', $response->json('footer_text'));

        $copy = BioPage::findOrFail($response->json('id'));
        $this->assertSame($second->id, $copy->domain_id);
        $this->assertFalse((bool) $copy->is_active);
        $this->assertSame(1, $copy->buttons()->count());
    }

    public function test_stats_include_unique_visitors(): void
    {
        $root = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->get('http://bio.example.com/');
        $this->get('http://bio.example.com/');

        $stats = $this->getJson("http://links.t-api.de/v1/bio-pages/{$root->id}/stats?days=7", $this->headers());
        $stats->assertOk()->assertJsonFragment(['views' => 2, 'unique_visitors' => 1]);
    }

    public function test_announcement_renders_with_link(): void
    {
        app(BioService::class)->createPage($this->user, $this->domain, [
            'title' => 'Root',
            'announcement_text' => 'Tour dates live!',
            'announcement_url' => 'https://example.com/tour',
        ]);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('Tour dates live!', escape: false)
            ->assertSee('https://example.com/tour', escape: false);
    }

    public function test_open_new_renders_target_blank(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Blog', 'kind' => 'link', 'destination_url' => 'https://example.com/blog', 'open_new' => true],
            ['label' => 'Home', 'kind' => 'link', 'destination_url' => 'https://example.com/home'],
        ], $this->user);

        $html = $this->get('http://bio.example.com/')->getContent();
        $this->assertSame(1, substr_count($html, 'target="_blank"'));
    }

    public function test_auto_theme_uses_media_query(): void
    {
        app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root', 'theme' => 'auto']);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('prefers-color-scheme', escape: false);
    }

    public function test_image_block_renders_and_needs_thumbnail(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Sunset', 'kind' => 'image', 'thumbnail_url' => 'https://example.com/sunset.jpg'],
        ], $this->user);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('<figure', escape: false)
            ->assertSee('https://example.com/sunset.jpg', escape: false);

        $this->expectException(ValidationException::class);
        $bio->syncButtons($page, [
            ['label' => 'Empty', 'kind' => 'image'],
        ], $this->user);
    }

    public function test_bio_domain_unknown_slug_gets_branded_404(): void
    {
        app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->get('http://bio.example.com/does-not-exist')
            ->assertNotFound()
            ->assertSee('Nothing here', escape: false)
            ->assertSee('Root', escape: false);
    }

    public function test_badge_and_grid_layout_render(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root', 'layout' => 'grid']);
        $bio->syncButtons($page, [
            ['label' => 'Drop', 'kind' => 'link', 'destination_url' => 'https://example.com/drop', 'badge' => 'NEW'],
        ], $this->user);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('NEW', escape: false)
            ->assertSee('grid-template-columns', escape: false);
    }

    public function test_countdown_renders_target_and_ticks(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Launch', 'kind' => 'countdown', 'event_at' => now()->addDays(2)->toDateTimeString()],
        ], $this->user);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('data-countdown', escape: false)
            ->assertSee('Launch', escape: false);

        $this->expectException(ValidationException::class);
        $bio->syncButtons($page, [
            ['label' => 'Nope', 'kind' => 'countdown'],
        ], $this->user);
    }

    public function test_hide_branding_is_plan_gated(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root', 'hide_branding' => true]);

        $this->assertTrue((bool) $page->fresh()->hide_branding);
        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('Powered by ternis.link', escape: false);

        $free = User::factory()->create([
            'plan_id' => Plan::where('name', 'free')->firstOrFail()->id,
        ]);

        // Free plans never qualify — the flag is stripped wherever set.
        $this->assertFalse(BioService::canHideBranding($free));
        $this->assertTrue(BioService::canHideBranding($this->user));
    }

    public function test_password_hint_shows_on_locked_page(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, [
            'title' => 'Secret', 'password_hint' => 'Our dog’s name',
        ]);
        $bio->setPassword($page, 'correct-horse');

        // API accepts the hint too.
        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [
            'password_hint' => 'Updated hint',
        ], $this->headers())->assertOk();

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('Updated hint', escape: false);
    }

    public function test_plain_domain_keeps_generic_404(): void
    {
        $this->get('http://href.nz/does-not-exist-xyz')
            ->assertNotFound()
            ->assertDontSee('Nothing here', escape: false);
    }

    public function test_quote_and_coupon_blocks(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Ship fast', 'sublabel' => 'A founder', 'kind' => 'quote'],
            ['label' => 'Launch deal', 'sublabel' => 'SHIP20', 'kind' => 'coupon'],
        ], $this->user);

        $coupon = $page->fresh()->buttons()->where('kind', 'coupon')->firstOrFail();

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('Ship fast', escape: false)
            ->assertSee('SHIP20', escape: false)
            ->assertSee('data-coupon-copy', escape: false);

        // Crafted GET on a coupon lands back on the page, untracked.
        $this->get("http://bio.example.com/t/{$coupon->id}")
            ->assertRedirect('https://bio.example.com/');
        $this->assertSame(0, $coupon->fresh()->tap_count);

        // Copy beacon tracks.
        $this->get("http://bio.example.com/t/{$coupon->id}/open.gif")->assertOk();
        $this->assertSame(1, $coupon->fresh()->tap_count);
    }

    public function test_coupon_requires_code(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->expectException(ValidationException::class);
        app(BioService::class)->syncButtons($page, [
            ['label' => 'Empty deal', 'kind' => 'coupon'],
        ], $this->user);
    }

    public function test_expired_page_redirects_to_gone_url(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, [
            'title' => 'Event',
            'expires_at' => now()->addHour()->toDateTimeString(),
            'gone_url' => 'https://example.com/next',
        ]);

        $this->get('http://bio.example.com/')->assertOk();

        $page->update(['expires_at' => now()->subMinute()]);

        $this->get('http://bio.example.com/')
            ->assertRedirect('https://example.com/next');
    }

    public function test_show_stats_toggles_public_counter(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root', 'show_stats' => true]);

        $this->get('http://bio.example.com/');
        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('views', escape: false);

        $page->update(['show_stats' => false]);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('views', escape: false);
    }

    public function test_definition_export_omits_secrets_and_counters(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);
        $bio->setPassword($page, 'correct-horse');

        $export = $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/export", $this->headers());
        $export->assertOk()->assertJsonFragment(['title' => 'Root', 'label' => 'Shop']);

        $content = $export->getContent();
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertStringNotContainsString('tap_count', $content);
    }

    public function test_video_block_plays_behind_facade(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Talk', 'kind' => 'video', 'destination_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ], $this->user);

        $button = $page->fresh()->buttons()->firstOrFail();

        $response = $this->get('http://bio.example.com/');
        $response->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', escape: false)
            ->assertDontSee('<iframe', escape: false);

        // No-JS fallback: tap goes to the watch page, tracked.
        $this->get("http://bio.example.com/t/{$button->id}")
            ->assertRedirect('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertSame(1, $button->fresh()->tap_count);
    }

    public function test_video_rejects_non_allowlisted_hosts(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->expectException(ValidationException::class);
        app(BioService::class)->syncButtons($page, [
            ['label' => 'Evil', 'kind' => 'video', 'destination_url' => 'https://evil.example/video.mp4'],
        ], $this->user);
    }

    public function test_expired_page_resolves_nothing(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, [
            'title' => 'Gone',
            'expires_at' => now()->addHour()->toDateTimeString(),
        ]);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);

        $button = $page->fresh()->buttons()->firstOrFail();

        $this->get('http://bio.example.com/')->assertOk()->assertSee('Shop', escape: false);

        $page->update(['expires_at' => now()->subMinute()]);

        // Expired roots fall back to landing (like unpublished ones);
        // the bio content and its taps are gone.
        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('Shop', escape: false);
        $this->get("http://bio.example.com/t/{$button->id}")->assertNotFound();
    }

    public function test_bio_qr_endpoints(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $png = $this->actingAs($this->user)->get("http://dash.ternis.link/bio/{$page->id}/qr");
        $png->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $png->getContent());

        $svg = $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/qr", $this->headers());
        $svg->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/qr?format=gif", $this->headers())
            ->assertStatus(422);
    }

    public function test_analytics_shows_browsers_and_recent(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);

        $button = $page->fresh()->buttons()->firstOrFail();
        $this->get('http://bio.example.com/', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0']);
        $this->get("http://bio.example.com/t/{$button->id}", ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0']);

        Livewire::actingAs($this->user)
            ->test(PageAnalytics::class, ['page' => $page])
            ->assertSee('Chrome', escape: false)
            ->assertSee('Shop', escape: false);
    }
}
