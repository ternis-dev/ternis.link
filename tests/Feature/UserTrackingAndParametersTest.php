<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserTrackingAndParametersTest extends TestCase
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
            'name' => 'Tracking Test Key',
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_query_parameters_stored_in_json_on_clicks_table(): void
    {
        $link = Link::create([
            'slug' => 'paramtest1',
            'destination_url' => 'https://example.com/landing',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->get('http://href.nz/paramtest1?source=newsletter&campaign=fall&ref=123');
        $response->assertRedirect('https://example.com/landing?source=newsletter&campaign=fall&ref=123');

        $this->assertDatabaseHas('clicks', [
            'link_id' => $link->id,
        ]);

        $click = Click::where('link_id', $link->id)->firstOrFail();
        $this->assertIsArray($click->query_params);
        $this->assertSame('newsletter', $click->query_params['source']);
        $this->assertSame('fall', $click->query_params['campaign']);
        $this->assertSame('123', $click->query_params['ref']);
    }

    public function test_dynamic_tags_set_by_customer_applications_via_comma_and_array(): void
    {
        $link = Link::create([
            'slug' => 'tagtest1',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // 1. Comma-separated ?tags=
        $this->get('http://href.nz/tagtest1?tags=newsletter,promo-fall,vip');
        $click1 = Click::where('link_id', $link->id)->latest('id')->firstOrFail();
        $this->assertSame(['newsletter', 'promo-fall', 'vip'], $click1->tags);

        // 2. Array syntax ?tag[]=
        $this->get('http://href.nz/tagtest1?tag[]=editorial&tag[]=issue47');
        $click2 = Click::where('link_id', $link->id)->latest('id')->firstOrFail();
        $this->assertSame(['editorial', 'issue47'], $click2->tags);

        // 3. Header syntax X-Click-Tags
        $this->withHeaders(['X-Click-Tags' => 'sponsor,edition-9'])
            ->get('http://href.nz/tagtest1');
        $click3 = Click::where('link_id', $link->id)->latest('id')->firstOrFail();
        $this->assertSame(['sponsor', 'edition-9'], $click3->tags);
    }

    public function test_user_tracking_disabled_by_default(): void
    {
        $link = Link::create([
            'slug' => 'usertrack0',
            'destination_url' => 'https://example.com/dest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => false,
        ]);

        $this->get('http://href.nz/usertrack0?uid=usr_12345&email=reader@example.com');
        $click = Click::where('link_id', $link->id)->firstOrFail();

        // Parameter stored in json query_params, but user_identifier is not tracked without opt-in
        $this->assertNull($click->user_identifier);
        $this->assertSame('usr_12345', $click->query_params['uid']);
    }

    public function test_user_tracking_when_enabled_extracts_user_identifier(): void
    {
        $link = Link::create([
            'slug' => 'usertrack1',
            'destination_url' => 'https://example.com/dest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => true,
        ]);

        $this->get('http://href.nz/usertrack1?uid=customer_987');
        $click = Click::where('link_id', $link->id)->firstOrFail();

        $this->assertSame('customer_987', $click->user_identifier);
    }

    public function test_user_tracking_pseudonymizes_email_with_sha256_for_privacy(): void
    {
        $link = Link::create([
            'slug' => 'usertrack2',
            'destination_url' => 'https://example.com/dest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => true,
        ]);

        $email = 'Subscriber.Name@Example.com';
        $this->get('http://href.nz/usertrack2?email='.urlencode($email));
        $click = Click::where('link_id', $link->id)->firstOrFail();

        // Must be pseudonymized, NOT plaintext
        $expectedHash = 'em_'.substr(hash('sha256', strtolower(trim($email))), 0, 32);
        $this->assertSame($expectedHash, $click->user_identifier);
        $this->assertStringNotContainsString('@', $click->user_identifier);
    }

    public function test_user_tracking_respects_dnt_header(): void
    {
        $link = Link::create([
            'slug' => 'usertrack3',
            'destination_url' => 'https://example.com/dest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => true,
        ]);

        $this->withHeaders(['DNT' => '1'])
            ->get('http://href.nz/usertrack3?uid=subscriber_555');

        $click = Click::where('link_id', $link->id)->firstOrFail();
        $this->assertNull($click->user_identifier);
    }

    public function test_api_creates_link_with_user_tracking_enabled(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/api-tracking',
            'domain_id' => $this->domain->id,
            'slug' => 'apitrack1',
            'user_tracking_enabled' => true,
        ], $this->headers());

        $response->assertCreated();
        $this->assertTrue($response->json('user_tracking_enabled'));

        $link = Link::where('slug', 'apitrack1')->firstOrFail();
        $this->assertTrue($link->user_tracking_enabled);
    }

    public function test_api_click_endpoints_expose_parameters_tags_and_user_identifier(): void
    {
        $link = Link::create([
            'slug' => 'apiclick1',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => true,
        ]);

        // Record a click with tags, uid, and query params
        $this->get('http://href.nz/apiclick1?uid=user_alpha&tags=newsletter,promo&ref=app');

        // GET /v1/links/{link}/clicks
        $response = $this->getJson("http://links.t-api.de/v1/links/{$link->id}/clicks", $this->headers());
        $response->assertOk();
        $first = $response->json('data.0');

        $this->assertSame('user_alpha', $first['user_identifier']);
        $this->assertSame(['newsletter', 'promo'], $first['tags']);
        $this->assertSame('user_alpha', $first['query_params']['uid']);
        $this->assertSame('app', $first['query_params']['ref']);

        // Test filtering by tag
        $tagFiltered = $this->getJson("http://links.t-api.de/v1/links/{$link->id}/clicks?tag=newsletter", $this->headers());
        $tagFiltered->assertOk();
        $this->assertCount(1, $tagFiltered->json('data'));

        $noTag = $this->getJson("http://links.t-api.de/v1/links/{$link->id}/clicks?tag=nonexistent", $this->headers());
        $noTag->assertOk();
        $this->assertCount(0, $noTag->json('data'));

        // GET /v1/links/{link}/clicks/summary
        $summary = $this->getJson("http://links.t-api.de/v1/links/{$link->id}/clicks/summary", $this->headers());
        $summary->assertOk();
        $this->assertSame(1, $summary->json('total_clicks'));
        $this->assertSame(1, $summary->json('unique_users'));
    }

    public function test_csv_export_includes_query_params_tags_and_user_identifier(): void
    {
        $link = Link::create([
            'slug' => 'csvexport1',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'user_tracking_enabled' => true,
        ]);

        $this->get('http://href.nz/csvexport1?uid=usr_csv&tags=sale,lead&code=save10');

        $this->actingAs($this->user);
        $response = $this->get("http://dash.ternis.link/links/{$link->id}/export");
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('user_identifier,tags,query_params', $content);
        $this->assertStringContainsString('usr_csv', $content);
        $this->assertStringContainsString('"sale, lead"', $content);
        $this->assertStringContainsString('save10', $content);
    }
}
