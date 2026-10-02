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

class BioRoundFiveTest extends TestCase
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

    public function test_location_block_links_out_or_renders_plain(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Studio', 'sublabel' => '123 Main St', 'kind' => 'location', 'destination_url' => 'https://example.com/visit'],
            ['label' => 'HQ', 'sublabel' => 'Nowhere', 'kind' => 'location'],
        ], $this->user);

        $html = $this->get('http://bio.example.com/')->getContent();
        $this->assertStringContainsString('📍', $html);
        $this->assertStringContainsString('123 Main St', $html);

        $linked = $page->fresh()->buttons()->where('label', 'Studio')->firstOrFail();
        $this->get("http://bio.example.com/t/{$linked->id}")->assertRedirect('https://example.com/visit');

        $plain = $page->fresh()->buttons()->where('label', 'HQ')->firstOrFail();
        $this->get("http://bio.example.com/t/{$plain->id}")->assertNotFound();
    }

    public function test_audio_block_plays_without_prefetch(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Episode 1', 'kind' => 'audio', 'destination_url' => 'https://example.com/ep1.mp3'],
        ], $this->user);

        $button = $page->fresh()->buttons()->firstOrFail();

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('<audio', escape: false)
            ->assertSee('preload="none"', escape: false);

        $this->get("http://bio.example.com/t/{$button->id}")
            ->assertRedirect('https://example.com/ep1.mp3');
        $this->assertSame(1, $button->fresh()->tap_count);

        // Plays beacon tracks too.
        $this->get("http://bio.example.com/t/{$button->id}/open.gif")->assertOk();
        $this->assertSame(2, $button->fresh()->tap_count);
    }

    public function test_audio_rejects_non_audio_urls(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->expectException(ValidationException::class);
        app(BioService::class)->syncButtons($page, [
            ['label' => 'Nope', 'kind' => 'audio', 'destination_url' => 'https://example.com/page.html'],
        ], $this->user);
    }

    public function test_download_flag_renders_attribute(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Menu', 'kind' => 'link', 'destination_url' => 'https://example.com/menu.pdf', 'download_file' => true],
        ], $this->user);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('download', escape: false);
    }

    public function test_link_icons_render(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Mail me', 'kind' => 'link', 'destination_url' => 'https://example.com/mail', 'icon' => 'mail'],
        ], $this->user);

        $html = $this->get('http://bio.example.com/')->getContent();
        $this->assertStringContainsString('width="18"', $html);
    }

    public function test_quick_add_parses_lines(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('quickAdd', "Blog | https://example.com/blog\nhttps://example.com/shop\nnot a url")
            ->call('quickAddButtons')
            ->assertHasErrors('quickAdd');

        $labels = $page->fresh()->buttons()->orderBy('sort_order')->pluck('label')->all();
        $this->assertSame(['Blog', 'example.com'], $labels);
    }

    public function test_template_creates_prefilled_page(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/bio-pages/from-template', [
            'domain_id' => $this->domain->id,
            'template' => 'event',
        ], $this->headers());

        $response->assertCreated()->assertJsonFragment(['title' => 'Summer fest']);

        $page = BioPage::findOrFail($response->json('id'));
        $this->assertSame(4, $page->buttons()->count());
        $this->assertTrue($page->buttons()->where('kind', 'rsvp')->exists());

        $this->postJson('http://links.t-api.de/v1/bio-pages/from-template', [
            'domain_id' => $this->domain->id,
            'template' => 'nope',
        ], $this->headers())->assertStatus(422);
    }

    public function test_builder_template_select(): void
    {
        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->set('template', 'creator')
            ->set('title', 'Mine')
            ->call('createRoot')
            ->assertHasNoErrors();

        $page = BioPage::where('title', 'Mine')->firstOrFail();
        $this->assertSame(4, $page->buttons()->count());
    }
}
