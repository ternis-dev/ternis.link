<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private Domain $secondDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->secondDomain = Domain::where('hostname', 'ternis.link')->first();
    }

    private function headersFor(User $user): array
    {
        $raw = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);

        return [
            'Authorization' => "Bearer {$raw}",
        ];
    }

    private function userOnPlan(string $planName): User
    {
        $plan = Plan::where('name', $planName)->firstOrFail();

        return User::factory()->create(['plan_id' => $plan->id]);
    }

    private function createLinkPayload(Domain $domain, ?string $slug = null, ?string $url = null, ?int $slugLength = null): array
    {
        return array_filter([
            'destination_url' => $url ?? 'https://example.com/'.Str::random(8),
            'domain_id' => $domain->id,
            'slug' => $slug,
            'slug_length' => $slugLength,
        ], fn ($value) => $value !== null);
    }

    public function test_custom_slug_shorter_than_plan_minimum_is_rejected(): void
    {
        $user = $this->userOnPlan('free'); // min_slug_length = 6

        $response = $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain, 'abc'), $this->headersFor($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseMissing('links', ['slug' => 'abc']);
    }

    public function test_premium_plan_allows_shorter_slug(): void
    {
        $user = $this->userOnPlan('business'); // min_slug_length = 3

        $response = $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain, 'abc'), $this->headersFor($user));

        $response->assertStatus(201);
        $response->assertJsonFragment(['slug' => 'abc']);
    }

    public function test_duplicate_slug_on_same_domain_is_rejected(): void
    {
        $user = $this->userOnPlan('free');

        Link::create([
            'slug' => 'taken-slug-1',
            'destination_url' => 'https://example.com/original',
            'domain_id' => $this->domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain, 'taken-slug-1'), $this->headersFor($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertEquals(1, Link::where('domain_id', $this->domain->id)->where('slug', 'taken-slug-1')->count());
    }

    public function test_same_slug_on_different_domain_is_allowed(): void
    {
        $user = $this->userOnPlan('free');

        Link::create([
            'slug' => 'shared-slug-1',
            'destination_url' => 'https://example.com/original',
            'domain_id' => $this->domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->secondDomain, 'shared-slug-1'), $this->headersFor($user));

        $response->assertStatus(201);
        $this->assertDatabaseHas('links', [
            'slug' => 'shared-slug-1',
            'domain_id' => $this->secondDomain->id,
        ]);
    }

    public function test_daily_quota_is_enforced(): void
    {
        $plan = Plan::create([
            'name' => 'test-quota',
            'min_slug_length' => 8,
            'custom_subdomain' => false,
            'rate_limit_per_minute' => 300,
            'max_links_per_day' => 2,
        ]);
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $headers = $this->headersFor($user);

        $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers)
            ->assertStatus(201);
        $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers)
            ->assertStatus(201);

        $response = $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('destination_url');
        $this->assertEquals(2, $user->links()->count());
    }

    public function test_unlimited_plan_has_no_daily_quota(): void
    {
        $user = $this->userOnPlan('business'); // max_links_per_day = null
        $headers = $this->headersFor($user);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers)
                ->assertStatus(201);
        }

        $this->assertEquals(3, $user->links()->count());
    }

    public function test_per_minute_rate_limit_returns_429(): void
    {
        $plan = Plan::create([
            'name' => 'test-rate',
            'min_slug_length' => 8,
            'custom_subdomain' => false,
            'rate_limit_per_minute' => 2,
            'max_links_per_day' => null,
        ]);
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $headers = $this->headersFor($user);

        $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers)
            ->assertStatus(201);
        $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers)
            ->assertStatus(201);

        $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $headers)
            ->assertStatus(429);
        $this->assertEquals(2, $user->links()->count());
    }

    public function test_generated_slug_respects_plan_minimum(): void
    {
        $user = $this->userOnPlan('free');

        $response = $this->postJson('http://links.t-api.de/v1/links', $this->createLinkPayload($this->domain), $this->headersFor($user));

        $response->assertStatus(201);
        $this->assertGreaterThanOrEqual(6, strlen($response->json('slug')));
    }

    public function test_livewire_form_rejects_duplicate_slug(): void
    {
        $user = $this->userOnPlan('free');

        Link::create([
            'slug' => 'livewire-taken',
            'destination_url' => 'https://example.com/original',
            'domain_id' => $this->domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/duplicate')
            ->set('domain_id', $this->domain->id)
            ->set('slug', 'livewire-taken')
            ->call('create')
            ->assertHasErrors('slug');

        $this->assertEquals(1, Link::where('domain_id', $this->domain->id)->where('slug', 'livewire-taken')->count());
    }

    public function test_admin_can_choose_generated_slug_length(): void
    {
        $admin = User::factory()->admin()->create();

        $slug = Livewire::actingAs($admin)
            ->test(LinkForm::class)
            ->assertSee('Generated Slug Length', escape: false)
            ->set('destination_url', 'https://example.com/picked-length')
            ->set('domain_id', $this->domain->id)
            ->set('slug_length', 12)
            ->call('create')
            ->assertHasNoErrors()
            ->get('createdSlug');

        $this->assertIsString($slug);
        $this->assertSame(12, strlen($slug));
    }

    public function test_capable_plan_can_choose_generated_slug_length(): void
    {
        $user = $this->userOnPlan('family');

        $slug = Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->assertSee('Generated Slug Length', escape: false)
            ->set('destination_url', 'https://example.com/family-length')
            ->set('domain_id', $this->domain->id)
            ->set('slug_length', 10)
            ->call('create')
            ->assertHasNoErrors()
            ->get('createdSlug');

        $this->assertSame(10, strlen($slug));
    }

    public function test_slug_length_outside_bounds_is_rejected(): void
    {
        $user = $this->userOnPlan('family');

        foreach ([2, 99] as $length) {
            Livewire::actingAs($user)
                ->test(LinkForm::class)
                ->set('destination_url', 'https://example.com/bad-length')
                ->set('domain_id', $this->domain->id)
                ->set('slug_length', $length)
                ->call('create')
                ->assertHasErrors('slug_length');
        }

        $this->assertDatabaseMissing('links', ['destination_url' => 'https://example.com/bad-length']);
    }

    public function test_free_plan_has_no_picker_and_ignores_tampered_length(): void
    {
        $user = $this->userOnPlan('free'); // min_slug_length = 6, no length choice

        $slug = Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->assertDontSee('Generated Slug Length', escape: false)
            ->set('destination_url', 'https://example.com/tampered')
            ->set('domain_id', $this->domain->id)
            ->set('slug_length', 32)
            ->call('create')
            ->assertHasNoErrors()
            ->get('createdSlug');

        $this->assertSame(6, strlen($slug));
    }

    public function test_custom_slug_wins_over_picked_length(): void
    {
        $user = $this->userOnPlan('family');

        $slug = Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/custom-wins')
            ->set('domain_id', $this->domain->id)
            ->set('slug', 'my-picked')
            ->set('slug_length', 32)
            ->call('create')
            ->assertHasNoErrors()
            ->get('createdSlug');

        $this->assertSame('my-picked', $slug);
    }

    public function test_api_applies_slug_length_for_privileged_users(): void
    {
        $user = $this->userOnPlan('family');

        $payload = $this->createLinkPayload($this->domain);
        $payload['slug_length'] = 16;

        $response = $this->postJson('http://links.t-api.de/v1/links', $payload, $this->headersFor($user));

        $response->assertStatus(201);
        $this->assertSame(16, strlen($response->json('slug')));
    }

    public function test_api_rejects_slug_length_outside_bounds(): void
    {
        $user = $this->userOnPlan('family');

        foreach ([2, 99] as $length) {
            $payload = $this->createLinkPayload($this->domain);
            $payload['slug_length'] = $length;

            $this->postJson('http://links.t-api.de/v1/links', $payload, $this->headersFor($user))
                ->assertStatus(422)
                ->assertJsonValidationErrors('slug_length');
        }
    }

    public function test_api_ignores_slug_length_for_ineligible_users(): void
    {
        $user = $this->userOnPlan('free'); // min_slug_length = 6, no length choice

        $payload = $this->createLinkPayload($this->domain);
        $payload['slug_length'] = 32;

        $response = $this->postJson('http://links.t-api.de/v1/links', $payload, $this->headersFor($user));

        $response->assertStatus(201);
        $this->assertSame(6, strlen($response->json('slug')));
    }

    public function test_api_custom_slug_wins_over_slug_length(): void
    {
        $user = $this->userOnPlan('family');

        $payload = $this->createLinkPayload($this->domain, 'api-picked');
        $payload['slug_length'] = 32;

        $response = $this->postJson('http://links.t-api.de/v1/links', $payload, $this->headersFor($user));

        $response->assertStatus(201);
        $response->assertJsonFragment(['slug' => 'api-picked']);
    }

    public function test_api_filters_links_by_tag(): void
    {
        $user = $this->userOnPlan('pro');

        Link::create([
            'slug' => 'api-tag-docs-1', 'destination_url' => 'https://example.com/d',
            'tags' => ['docs'], 'domain_id' => $this->domain->id, 'user_id' => $user->id, 'is_active' => true,
        ]);
        Link::create([
            'slug' => 'api-tag-other-1', 'destination_url' => 'https://example.com/o',
            'tags' => ['other'], 'domain_id' => $this->domain->id, 'user_id' => $user->id, 'is_active' => true,
        ]);

        $response = $this->getJson('http://links.t-api.de/v1/links?tag=DOCS', $this->headersFor($user));

        $response->assertStatus(200);
        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertContains('api-tag-docs-1', $slugs);
        $this->assertNotContains('api-tag-other-1', $slugs);
    }
}
