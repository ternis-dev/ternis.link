<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminThreatExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Domain $domain;

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

        $this->domain = Domain::where('hostname', 'href.nz')->first();
    }

    public function test_threat_export_requires_admin_role(): void
    {
        $this->actingAs($this->regularUser)
            ->get('http://admin.ternis.link/threats/export')
            ->assertForbidden();
    }

    public function test_admin_can_export_threats_as_csv_with_formula_sanitization(): void
    {
        $link = Link::create([
            'user_id' => $this->regularUser->id,
            'domain_id' => $this->domain->id,
            'slug' => '=cmd_exploit',
            'destination_url' => 'https://malicious.example.com/exploit.exe',
            'is_active' => false,
            'tags' => ['quarantine', 'malware'],
        ]);

        ThreatEvaluation::create([
            'link_id' => $link->id,
            'user_id' => $this->regularUser->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'destination_url' => '+http://evil.com/payload',
            'destination_host' => 'evil.com',
            'evaluation_type' => 'realtime',
            'verdict' => 'malware',
            'threat_score' => 95,
            'confidence' => 0.98,
            'action_taken' => 'quarantined',
            'reasoning' => '=1+1 dangerous command payload',
        ]);

        ThreatEvaluation::create([
            'link_id' => $link->id,
            'user_id' => $this->regularUser->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'destination_url' => 'https://safe.example.com/docs',
            'destination_host' => 'safe.example.com',
            'evaluation_type' => 'realtime',
            'verdict' => 'safe',
            'threat_score' => 5,
            'confidence' => 0.99,
            'action_taken' => 'allowed',
            'reasoning' => 'Standard technical documentation',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/threats/export?format=csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertTrue(str_contains((string) $response->headers->get('Content-Disposition'), 'threat-security-audit-'));

        $content = $response->streamedContent();

        // CSV Header check
        $this->assertStringContainsString('Evaluation ID', $content);
        $this->assertStringContainsString('Destination URL', $content);
        $this->assertStringContainsString('Verdict', $content);
        $this->assertStringContainsString('Threat Score', $content);

        // Formula injection sanitization check: values starting with =, +, -, @ must be prefixed with '
        $this->assertStringContainsString("'+http://evil.com/payload", $content);
        $this->assertStringContainsString("'=1+1 dangerous command payload", $content);
        $this->assertStringContainsString("'=cmd_exploit", $content);

        // Active filter test: filter by verdict=malware
        $filteredResponse = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/threats/export?format=csv&verdict=malware');

        $filteredResponse->assertOk();
        $filteredContent = $filteredResponse->streamedContent();

        $this->assertStringContainsString('evil.com', $filteredContent);
        $this->assertStringNotContainsString('safe.example.com', $filteredContent);
    }

    public function test_admin_can_export_threats_as_json(): void
    {
        ThreatEvaluation::create([
            'creator_ip_hash' => hash('sha256', '10.0.0.1'),
            'destination_url' => 'https://phishing.example.com/login',
            'destination_host' => 'phishing.example.com',
            'evaluation_type' => 'backfill',
            'verdict' => 'phishing',
            'threat_score' => 88,
            'confidence' => 0.94,
            'action_taken' => 'quarantined',
            'reasoning' => 'Spoofed credential harvest portal',
            'metadata' => ['detector' => 'typesafe_ai'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/threats/export?format=json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $content = $response->streamedContent();
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);
        $this->assertEquals('https://phishing.example.com/login', $decoded[0]['destination_url']);
        $this->assertEquals('phishing', $decoded[0]['verdict']);
        $this->assertEquals(88, $decoded[0]['threat_score']);
        $this->assertEquals('quarantined', $decoded[0]['action_taken']);
    }
}
