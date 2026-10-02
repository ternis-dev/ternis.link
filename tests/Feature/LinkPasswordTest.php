<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkEditForm;
use App\Livewire\Dashboard\LinkForm;
use App\Models\ApiKey;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LinkPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();

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

    private function lockedLink(): Link
    {
        return Link::create([
            'slug' => 'secret12',
            'destination_url' => 'https://example.com/vault',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'password_hash' => Hash::make('correct-horse'),
            'is_active' => true,
        ]);
    }

    public function test_create_with_password_hashes_and_hides_secrets(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/vault',
            'domain_id' => $this->domain->id,
            'password' => 'correct-horse',
        ], $this->headers());

        $response->assertCreated();

        $link = Link::where('slug', $response->json('slug'))->firstOrFail();
        $this->assertNotNull($link->password_hash);
        $this->assertNotSame('correct-horse', $link->password_hash);

        // Neither the hash nor the encrypted creator IP may leak.
        $content = $response->getContent();
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertStringNotContainsString('creator_ip_encrypted', $content);
    }

    public function test_guest_password_prohibited_and_short_rejected(): void
    {
        $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/x',
            'password' => 'correct-horse',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/x',
            'domain_id' => $this->domain->id,
            'password' => 'short',
        ], $this->headers())->assertStatus(422);
    }

    public function test_locked_link_shows_interstitial_without_leak_or_track(): void
    {
        $link = $this->lockedLink();

        $this->get('http://href.nz/secret12')
            ->assertOk()
            ->assertSee('password-protected', escape: false)
            ->assertDontSee('https://example.com/vault', escape: false);

        $this->assertSame(0, $link->fresh()->click_count);
        $this->assertSame(0, Click::count());
    }

    public function test_unlock_flow_tracks_only_after_success(): void
    {
        $link = $this->lockedLink();

        $this->post('http://href.nz/secret12/unlock', ['password' => 'nope'])
            ->assertSessionHasErrors('password');
        $this->assertSame(0, $link->fresh()->click_count);

        $this->post('http://href.nz/secret12/unlock', ['password' => 'correct-horse'])
            ->assertRedirect('https://href.nz/secret12');

        $this->get('http://href.nz/secret12')
            ->assertRedirect('https://example.com/vault');
        $this->assertSame(1, $link->fresh()->click_count);
    }

    public function test_unlock_is_throttled(): void
    {
        $link = $this->lockedLink();

        for ($i = 0; $i < 10; $i++) {
            $this->post("http://href.nz/{$link->slug}/unlock", ['password' => 'nope']);
        }

        $this->post("http://href.nz/{$link->slug}/unlock", ['password' => 'nope'])
            ->assertStatus(429);
    }

    public function test_preview_sandbox_stays_opaque(): void
    {
        $this->lockedLink();

        $this->get('http://href.nz/preview/secret12')
            ->assertOk()
            ->assertSee('password-protected', escape: false)
            ->assertDontSee('https://example.com/vault', escape: false);
    }

    public function test_api_update_sets_and_clears_password(): void
    {
        $link = Link::create([
            'slug' => 'plain123',
            'destination_url' => 'https://example.com/p',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'password' => 'correct-horse',
        ], $this->headers())->assertOk();
        $this->assertNotNull($link->fresh()->password_hash);

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'remove_password' => true,
        ], $this->headers())->assertOk();
        $this->assertNull($link->fresh()->password_hash);
    }

    public function test_dashboard_forms_set_and_remove_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/form')
            ->set('domain_id', $this->domain->id)
            ->set('password', 'correct-horse')
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/form')->firstOrFail();
        $this->assertNotNull($link->password_hash);

        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $link])
            ->assertSee('password-protected', escape: false)
            ->set('password', 'rotated-secret')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('rotated-secret', $link->fresh()->password_hash));

        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $link->fresh()])
            ->call('removePassword')
            ->assertHasNoErrors();

        $this->assertNull($link->fresh()->password_hash);
    }
}
