<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ApiKeyManager;
use App\Livewire\Dashboard\DomainManager;
use App\Livewire\Dashboard\LinkForm;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormReworkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_link_form_trims_padded_urls_before_validating(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', '  https://example.com/padded  ')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('destination_url', '');

        $this->assertDatabaseHas('links', ['destination_url' => 'https://example.com/padded']);
    }

    public function test_link_form_uses_friendly_validation_messages(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'not-a-url')
            ->call('create')
            ->assertHasErrors(['destination_url' => 'url'])
            ->assertSee('include https://', escape: false);

        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/ok')
            ->set('slug', 'bad slug!')
            ->call('create')
            ->assertHasErrors(['slug' => 'regex'])
            ->assertSee('dashes and underscores', escape: false);
    }

    public function test_link_form_rejects_past_expiration_with_friendly_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/ok')
            ->set('expires_at', '2000-01-01T00:00')
            ->call('create')
            ->assertHasErrors(['expires_at' => 'after'])
            ->assertSee('must be in the future', escape: false);
    }

    public function test_api_key_manager_trims_labels_and_guides_on_empty(): void
    {
        $name = Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', '  Padded Label  ')
            ->call('createKey')
            ->assertHasNoErrors()
            ->get('newlyCreatedKey');

        $this->assertIsString($name);
        $this->assertSame('Padded Label', $this->user->apiKeys()->firstOrFail()->name);

        Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', '')
            ->call('createKey')
            ->assertHasErrors(['keyName' => 'required'])
            ->assertSee('recognise it later', escape: false);
    }

    public function test_domain_manager_guides_on_empty_hostname(): void
    {
        $user = User::factory()->create([
            'plan_id' => Plan::where('name', 'family')->firstOrFail()->id,
        ]);

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('hostname', '')
            ->call('addDomain')
            ->assertHasErrors(['hostname' => 'required'])
            ->assertSee('Please enter a hostname.', escape: false);
    }

    public function test_ui_alert_renders_icon_per_tone(): void
    {
        foreach (['success', 'error', 'info'] as $tone) {
            $this->blade('<x-ui.alert :tone="$tone">message</x-ui.alert>', ['tone' => $tone])
                ->assertSeeHtml('<svg')
                ->assertSeeHtml('viewBox="0 0 24 24"');
        }

        $error = (string) $this->blade('<x-ui.alert tone="error">bad</x-ui.alert>');
        $this->assertStringContainsString('role="alert"', $error);
        $this->assertStringContainsString('red', $error);
    }

    public function test_ui_input_renders_without_error_bag(): void
    {
        $html = (string) $this->blade('<x-ui.input label="Hostname" name="hostname" />');

        $this->assertStringContainsString('Hostname', $html);
        $this->assertStringNotContainsString('aria-invalid', $html);
    }

    public function test_dashboard_nav_uses_consistent_stroke_icons(): void
    {
        $response = $this->actingAs($this->user)->get('http://dash.ternis.link/dashboard');

        $response->assertStatus(200);
        $response->assertSee('viewBox="0 0 24 24"', escape: false);
        // Blocky filled icon paths are gone.
        $response->assertDontSee('M3 3h7v7H3z', escape: false);
    }
}
