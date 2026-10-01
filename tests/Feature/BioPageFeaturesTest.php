<?php

namespace Tests\Feature;

use App\Enums\DomainType;
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
            ->assertRedirect('https://bio.example.com');

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
}
