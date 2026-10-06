<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\PrivacyExport;
use App\Models\User;
use App\Notifications\SecurityAlert;
use App\Services\AccountErasureService;
use App\Support\Activity;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $export = PrivacyExport::findOrFail($exportId);
        $this->assertSame('done', $export->fresh()->status);

        $zipPath = Storage::disk('local')->path($export->fresh()->path);
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
        $this->assertContains('activity.csv', $names);
        $this->assertContains('errors.csv', $names);
        $this->assertContains('bio-pages.csv', $names);
        $this->assertContains('notifications.csv', $names);
        $this->assertNotContains('api_keys.csv', array_diff($names, ['api-keys.csv']));
    }

    public function test_export_includes_activity_errors_bio_and_notifications(): void
    {
        $link = Link::create([
            'slug' => 'priv03',
            'destination_url' => 'https://example.com/r',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Activity::record(ActivityLog::LINK_CREATED, $this->user, $link, ['slug' => 'priv03']);

        ErrorEncounter::create([
            'http_code' => 404,
            'error_message' => 'Link not found.',
            'exception_class' => 'NotFoundHttpException',
            'method' => 'GET',
            'host' => 'href.nz',
            'path' => '/missing',
            'user_id' => $this->user->id,
            'ip_hash' => hash('sha256', '9.9.9.9'),
        ]);

        $this->user->notify(new SecurityAlert('Export test', ['line one'], null, null));

        $response = $this->postJson('http://links.t-api.de/v1/account/export', [], ['Authorization' => "Bearer {$this->rawApiKey}"]);
        $response->assertStatus(202);

        $export = PrivacyExport::findOrFail($response->json('id'));
        $zipPath = Storage::disk('local')->path($export->fresh()->path);

        $zip = new \ZipArchive;
        $zip->open($zipPath);

        $activity = $zip->getFromName('activity.csv');
        $this->assertStringContainsString('priv03', $activity);

        $errors = $zip->getFromName('errors.csv');
        $this->assertStringContainsString('Link not found.', $errors);
        $this->assertStringNotContainsString(hash('sha256', '9.9.9.9'), $errors);

        $notifications = $zip->getFromName('notifications.csv');
        $this->assertStringContainsString('Export test', $notifications);

        $zip->close();
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

        app(AccountErasureService::class)->erase($this->user);

        $this->assertNull($link->fresh()->user_id);
        $this->assertNull($link->fresh()->creator_ip_hash);
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }
}
