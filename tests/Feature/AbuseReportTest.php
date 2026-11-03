<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AbuseReport;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Notifications\AdminSecurityAlert;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AbuseReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected Domain $domain;

    protected Link $link;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'email' => 'admin@ternis.dev',
        ]);

        $this->user = User::factory()->create([
            'role' => UserRole::User->value,
            'email' => 'creator@example.com',
        ]);

        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $this->link = Link::create([
            'slug' => 'malicious-phish',
            'domain_id' => $this->domain->id,
            'destination_url' => 'https://evil-phish-target.com/login',
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_abuse_report_form_renders_publicly(): void
    {
        $this->get('http://ternis.link/pages/abuse')
            ->assertOk()
            ->assertSee('Report Abuse')
            ->assertSee('Trust & Safety', escape: false)
            ->assertSee('Suspect URL or Short Link')
            ->assertSee('abuse@ternis.dev');
    }

    public function test_abuse_report_form_prepopulates_url(): void
    {
        $this->get('http://ternis.link/pages/abuse?url=https://href.nz/malicious-phish')
            ->assertOk()
            ->assertSee('value="https://href.nz/malicious-phish"', escape: false);
    }

    public function test_abuse_report_markdown_twin_served(): void
    {
        $response = $this->get('http://ternis.link/pages/abuse.md')->assertOk();
        $this->assertStringContainsString('text/markdown', (string) $response->headers->get('Content-Type'));
        $response->assertSee('Ternis Link Network — Abuse & Threat Reporting', escape: false)
            ->assertSee('abuse@ternis.dev', escape: false)
            ->assertSee('https://ternis.link/pages/abuse', escape: false);
    }

    public function test_abuse_shortcuts_and_subdomain_redirect_to_pages_abuse(): void
    {
        // On ternis.link, /abuse 301s to /pages/abuse
        $this->get('http://ternis.link/abuse')
            ->assertRedirect('http://ternis.link/pages/abuse');

        // On shortener host, /abuse 301s to https://ternis.link/pages/abuse
        $this->get('http://href.nz/abuse?url=test')
            ->assertRedirect('https://ternis.link/pages/abuse?url=test');

        // On dashboard host, /abuse 301s to https://ternis.link/pages/abuse
        $this->get('http://dash.ternis.link/abuse')
            ->assertRedirect('https://ternis.link/pages/abuse');

        // On abuse.ternis.link, root 301s to https://ternis.link/pages/abuse
        $this->get('http://abuse.ternis.link/?target=xyz')
            ->assertRedirect('https://ternis.link/pages/abuse?target=xyz');
    }

    public function test_user_can_submit_abuse_report(): void
    {
        Notification::fake();

        $response = $this->post('http://ternis.link/pages/abuse', [
            'reported_url' => 'https://href.nz/malicious-phish',
            'category' => 'phishing',
            'reporter_email' => 'security@victim.org',
            'reporter_name' => 'Victim Security Response',
            'reason' => 'This link mimics a banking login to harvest user passwords.',
        ]);

        $response->assertRedirect('http://ternis.link/pages/abuse?success=1');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('abuse_reports', [
            'reported_url' => 'https://href.nz/malicious-phish',
            'category' => 'phishing',
            'reporter_email' => 'security@victim.org',
            'status' => 'pending',
            'link_id' => $this->link->id,
        ]);

        Notification::assertSentTo(
            $this->admin,
            AdminSecurityAlert::class,
            function (AdminSecurityAlert $notification) {
                return str_contains($notification->title, 'Abuse Report')
                    && str_contains($notification->title, 'Phishing');
            }
        );
    }

    public function test_json_submission_returns_created(): void
    {
        Notification::fake();

        $response = $this->postJson('http://ternis.link/pages/abuse', [
            'reported_url' => 'https://unknown-link.com/download.exe',
            'category' => 'malware',
            'reporter_email' => 'analyst@cert.at',
            'reason' => 'Distributing trojan malware payload.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('abuse_reports', [
            'reported_url' => 'https://unknown-link.com/download.exe',
            'category' => 'malware',
            'link_id' => null,
        ]);
    }

    public function test_honeypot_drops_bot_submissions(): void
    {
        Notification::fake();

        $response = $this->post('http://ternis.link/pages/abuse', [
            'company_website' => 'http://spambot.ru',
            'reported_url' => 'https://href.nz/malicious-phish',
            'category' => 'spam',
            'reporter_email' => 'bot@spambot.ru',
            'reason' => 'Automated spam submission.',
        ]);

        $response->assertRedirect('http://ternis.link/pages/abuse?success=1');
        $this->assertDatabaseCount('abuse_reports', 0);
        Notification::assertNothingSent();
    }

    public function test_admin_can_view_and_moderate_abuse_reports(): void
    {
        $report = AbuseReport::create([
            'link_id' => $this->link->id,
            'reported_url' => 'https://href.nz/malicious-phish',
            'category' => 'phishing',
            'reporter_email' => 'victim@example.com',
            'reason' => 'Credential harvester targeting employees.',
            'status' => 'pending',
        ]);

        // Admin can list abuse reports
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/abuse-reports')
            ->assertOk()
            ->assertSee('Abuse &amp; Threat Reports', escape: false)
            ->assertSee('https://href.nz/malicious-phish', escape: false)
            ->assertSee('victim@example.com', escape: false);

        // Admin marks investigating
        $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/abuse-reports/{$report->id}/investigate")
            ->assertRedirect();

        $this->assertEquals('investigating', $report->fresh()->status);
        $this->assertEquals($this->admin->id, $report->fresh()->reviewed_by_id);

        // Admin resolves and quarantines the link
        $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/abuse-reports/{$report->id}/resolve", [
                'action_taken' => 'quarantined',
                'admin_notes' => 'Confirmed phishing form.',
            ])
            ->assertRedirect();

        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertEquals('quarantined', $report->fresh()->action_taken);
        $this->assertEquals('Confirmed phishing form.', $report->fresh()->admin_notes);

        // Assert link was quarantined and deactivated
        $this->assertFalse($this->link->fresh()->is_active);
        $this->assertContains('quarantine', $this->link->fresh()->tags ?? []);
    }

    public function test_admin_can_dismiss_abuse_report(): void
    {
        $report = AbuseReport::create([
            'link_id' => $this->link->id,
            'reported_url' => 'https://href.nz/malicious-phish',
            'category' => 'other',
            'reporter_email' => 'rival@competitor.com',
            'reason' => 'I dislike this link.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/abuse-reports/{$report->id}/dismiss", [
                'admin_notes' => 'Not a policy violation.',
            ])
            ->assertRedirect();

        $this->assertEquals('dismissed', $report->fresh()->status);
        $this->assertTrue($this->link->fresh()->is_active);
    }
}
