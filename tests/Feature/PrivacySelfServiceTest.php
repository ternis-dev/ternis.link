<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivacySelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private User $user;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->user = User::factory()->create();

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    public function test_export_creates_zip_without_secrets(): void
    {
        Link::create([
            'slug' => 'priv01',
            'destination_url' => 'https://example.com/p',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/account/export', [], ['Authorization' => "Bearer {$this->rawApiKey}"]);
        $response->assertStatus(202);

        $exportId = $response->json('id');
        $export = \App\Models\PrivacyExport::findOrFail($exportId);
        $this->assertSame('done', $export->fresh()->status);

        $zipPath = \Illuminate\Support\Facades\Storage::disk('local')->path($export->fresh()->path);
        $this->assertFileExists($zipPath);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains('links.csv', $names);
        $this->assertContains('profile.json', $names);
        $this->assertNotContains('api_keys.csv', array_diff($names, ['api-keys.csv']));
    }

    public function test_deletion_requires_sso(): void
    {
        $this->postJson('http://links.t-api.de/v1/account/deletion', ['confirm' => 'DELETE-ME'], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertForbidden();
    }

    public function test_erasure_anonymizes_links(): void
    {
        $link = Link::create([
            'slug' => 'priv02',
            'destination_url' => 'https://example.com/q',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'creator_ip_hash' => hash('sha256', '1.2.3.4'),
            'is_active' => true,
        ]);

        app(\App\Services\AccountErasureService::class)->erase($this->user);

        $this->assertNull($link->fresh()->user_id);
        $this->assertNull($link->fresh()->creator_ip_hash);
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }
}
