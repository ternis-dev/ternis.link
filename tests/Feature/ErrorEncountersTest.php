<?php

namespace Tests\Feature;

use App\Livewire\Admin\ErrorEncounterTable;
use App\Models\ErrorEncounter;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ErrorEncountersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();

        ErrorEncounter::create([
            'http_code' => 500,
            'error_message' => 'Boom happened',
            'exception_class' => 'RuntimeException',
            'method' => 'GET',
            'host' => 'href.nz',
            'path' => '/boom',
            'user_id' => $this->user->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('http://admin.ternis.link/errors')
            ->assertRedirect('http://admin.ternis.link/login');
    }

    public function test_non_admin_gets_forbidden(): void
    {
        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/errors')
            ->assertForbidden();
    }

    public function test_admin_can_view_errors_page(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/errors')
            ->assertStatus(200)
            ->assertSee('Error Encounters', escape: false)
            ->assertSee('Boom happened', escape: false)
            ->assertSee('RuntimeException', escape: false)
            ->assertSee('tl-sensitive', escape: false);
    }

    public function test_admin_can_delete_entry(): void
    {
        $entry = ErrorEncounter::firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(ErrorEncounterTable::class)
            ->call('delete', $entry->id)
            ->assertHasNoErrors()
            ->assertDontSee('Boom happened', escape: false);

        $this->assertNull(ErrorEncounter::find($entry->id));
    }

    public function test_code_filter_scopes_results(): void
    {
        ErrorEncounter::create([
            'http_code' => 404,
            'error_message' => 'Missing thing',
            'exception_class' => 'NotFoundHttpException',
            'method' => 'GET',
            'host' => 'href.nz',
            'path' => '/missing',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ErrorEncounterTable::class)
            ->set('codeFilter', '5xx')
            ->assertSee('Boom happened', escape: false)
            ->assertDontSee('Missing thing', escape: false)
            ->set('codeFilter', '4xx')
            ->assertSee('Missing thing', escape: false)
            ->assertDontSee('Boom happened', escape: false);
    }

    public function test_non_admin_blocked_from_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(ErrorEncounterTable::class)
            ->assertForbidden();
    }
}
