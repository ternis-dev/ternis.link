<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use App\Services\AiThreatDetectionService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminThreatUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
        ]);
    }

    public function test_threat_console_requires_admin_role(): void
    {
        $this->actingAs($this->regularUser)
            ->get('http://admin.ternis.link/threats')
            ->assertForbidden();
    }

    public function test_admin_can_view_threat_console_and_evaluations(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $link = Link::create([
            'slug' => 'bad-malware-link',
            'destination_url' => 'https://malicious-test-bundle.com/trojan.exe',
            'domain_id' => $domain->id,
            'is_active' => false,
            'tags' => ['quarantine', 'malware'],
        ]);

        ThreatEvaluation::create([
            'link_id' => $link->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'evaluation_type' => 'link_creation',
            'destination_url' => $link->destination_url,
            'destination_host' => 'malicious-test-bundle.com',
            'verdict' => 'malware',
            'threat_score' => 95,
            'confidence' => 1.0,
            'action_taken' => 'quarantined',
            'reasoning' => 'Confirmed trojan payload',
            'metadata' => [
                'probabilities' => ['malware' => 1.0, 'safe' => 0.0],
                'raw_score' => 3.9,
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/threats')
            ->assertOk();

        $response->assertSee('AI Threat Inspector');
        $response->assertSee('TypeSafe AI Sandbox');
        $response->assertSee('https://malicious-test-bundle.com/trojan.exe');
        $response->assertSee('quarantined');
        $response->assertSee('95/100');
    }

    public function test_admin_sandbox_logs_scans_even_when_provider_is_unavailable(): void
    {
        config(['ai_security.api_key' => null]);
        $this->actingAs($this->admin)->post('http://admin.ternis.link/threats/test', [
            'test_url' => 'https://example.com/admin-scan',
        ])->assertRedirect();
        $this->assertDatabaseHas('threat_evaluations', [
            'evaluation_type' => 'admin_url_scan',
            'destination_url' => 'https://example.com/admin-scan',
            'user_id' => $this->admin->id,
            'verdict' => 'unverified',
            'action_taken' => 'scanned',
        ]);
    }

    public function test_suspicious_exe_url_is_always_evaluated_and_quarantined(): void
    {
        $threatService = app(AiThreatDetectionService::class);
        $this->assertTrue($threatService->isSuspiciousUrl('https://not-a-scam.com/download.exe'));
        $this->assertTrue($threatService->isSuspiciousUrl('https://update-installer.org/payload.scr'));

        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $link = Link::create([
            'slug' => 'test-download-exe',
            'destination_url' => 'https://not-a-scam.com/download.exe',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

        $this->assertTrue($threatService->shouldEvaluateLink($link));
    }
}
