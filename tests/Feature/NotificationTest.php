<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Dashboard\ApiKeyManager;
use App\Models\ErrorEncounter;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\Channels\TernisAuthChannel;
use App\Notifications\SecurityAlert;
use App\Notifications\ServerErrorAlert;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_api_key_creation_notifies_owner(): void
    {
        $user = $this->userOnPlan('free');

        Notification::fake();

        Livewire::actingAs($user)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'ci-key')
            ->call('createKey')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, SecurityAlert::class);
    }

    public function test_security_email_preference_gates_the_mail_channel(): void
    {
        $user = $this->userOnPlan('free', ['notify_security_email' => false]);

        $notification = new SecurityAlert('Test', ['line']);

        $this->assertSame(
            ['database', TernisAuthChannel::class],
            $notification->via($user)
        );

        $user->update(['notify_security_email' => true]);

        $this->assertContains('mail', $notification->via($user->fresh()));
    }

    public function test_server_error_pages_admins_once_per_window(): void
    {
        $admin = $this->userOnPlan('business', ['role' => UserRole::Admin]);

        Notification::fake();

        ErrorEncounter::record(new \RuntimeException('boom-one'));
        ErrorEncounter::record(new \RuntimeException('boom-one'));

        Notification::assertSentTimes(ServerErrorAlert::class, 1);

        $recipients = [];
        Notification::assertSentTo($admin, ServerErrorAlert::class, function ($notification, $channels, $notifiable) use (&$recipients) {
            $recipients[] = $notifiable->id;

            return true;
        });

        $this->assertContains($admin->id, $recipients);
    }

    public function test_non_500_errors_do_not_page_admins(): void
    {
        $this->userOnPlan('business', ['role' => UserRole::Admin]);

        Notification::fake();

        ErrorEncounter::record(new NotFoundHttpException);

        Notification::assertNotSentTo(
            User::where('role', UserRole::Admin->value)->first(),
            ServerErrorAlert::class
        );
    }

    public function test_ternis_auth_channel_sleeps_without_endpoint(): void
    {
        config(['services.ternis_auth.notify_endpoint' => '']);

        $user = $this->userOnPlan('free');

        // No endpoint, no HTTP — resolves without throwing or sending.
        (new TernisAuthChannel)->send(
            $user, new SecurityAlert('Test', ['line'])
        );

        $this->assertTrue(true);
    }

    public function test_notifications_page_renders(): void
    {
        $user = $this->userOnPlan('free');

        $this->actingAs($user)
            ->get('http://dash.ternis.link/notifications')
            ->assertOk()
            ->assertSee('Notifications');
    }

    private function userOnPlan(string $planName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'plan_id' => Plan::where('name', $planName)->firstOrFail()->id,
        ], $attributes));
    }
}
