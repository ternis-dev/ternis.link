<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DomainManager;
use App\Livewire\Dashboard\LinkEditForm;
use App\Livewire\Dashboard\LinkForm;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Services\DomainService;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LinkMetaAndSubdomainTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    private function userOnPlan(string $planName): User
    {
        return User::factory()->create([
            'plan_id' => Plan::where('name', $planName)->firstOrFail()->id,
        ]);
    }

    // --- descriptions + tags -------------------------------------

    public function test_create_form_stores_description_and_tags(): void
    {
        $user = $this->userOnPlan('free');

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/meta')
            ->set('domain_id', $this->domain->id)
            ->set('description', 'My docs page')
            ->set('tags', 'Docs, release-Q4, docs')
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/meta')->firstOrFail();
        $this->assertSame('My docs page', $link->description);
        $this->assertSame(['docs', 'release-q4'], $link->tags);
    }

    public function test_create_form_rejects_invalid_tags_with_guidance(): void
    {
        $user = $this->userOnPlan('free');

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/bad-tags')
            ->set('domain_id', $this->domain->id)
            ->set('tags', 'ok-tag, nope!')
            ->call('create')
            ->assertHasErrors('tags')
            ->assertSee('lowercase letters, numbers and dashes', escape: false);

        $this->assertDatabaseMissing('links', ['destination_url' => 'https://example.com/bad-tags']);
    }

    public function test_edit_form_updates_description_and_tags(): void
    {
        $user = $this->userOnPlan('free');
        $link = Link::create([
            'slug' => 'meta-edit-1',
            'destination_url' => 'https://example.com/old',
            'domain_id' => $this->domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(LinkEditForm::class, ['link' => $link])
            ->set('description', 'Updated blurb')
            ->set('tags', 'v2, LaUnCh')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $link->fresh();
        $this->assertSame('Updated blurb', $fresh->description);
        $this->assertSame(['v2', 'launch'], $fresh->tags);

        // Clearing both empties them.
        Livewire::actingAs($user)
            ->test(LinkEditForm::class, ['link' => $link])
            ->set('description', '')
            ->set('tags', '')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $link->fresh();
        $this->assertNull($fresh->description);
        $this->assertNull($fresh->tags);
    }

    public function test_api_accepts_description_and_tags(): void
    {
        $user = $this->userOnPlan('pro');
        $raw = 'tl_'.str_repeat('a', 48);
        \App\Models\ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/api-meta',
            'domain_id' => $this->domain->id,
            'description' => 'Via API',
            'tags' => ['api', 'v1'],
        ], ['Authorization' => "Bearer {$raw}"]);

        $response->assertStatus(201);

        $link = Link::where('destination_url', 'https://example.com/api-meta')->firstOrFail();
        $this->assertSame('Via API', $link->description);
        $this->assertSame(['api', 'v1'], $link->tags);
    }

    public function test_api_rejects_invalid_tags(): void
    {
        $user = $this->userOnPlan('pro');
        $raw = 'tl_'.str_repeat('b', 48);
        \App\Models\ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);

        $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/api-bad-tags',
            'domain_id' => $this->domain->id,
            'tags' => ['fine', 'no way!'],
        ], ['Authorization' => "Bearer {$raw}"])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tags.1');
    }

    public function test_service_normalizer_is_conservative(): void
    {
        $this->assertSame(['ab', 'b-c'], LinkService::normalizeTags(' AB , b-c, AB, !!, ,'));
        $this->assertSame([], LinkService::normalizeTags(null));
        $this->assertSame(['xy'], LinkService::normalizeTags(['XY']));
        $this->assertCount(10, LinkService::normalizeTags(array_map(fn ($i) => "tag-{$i}", range(1, 30))));
        $this->assertSame(['bad!'], LinkService::invalidTags('ok, bad!'));
    }

    // --- personal subdomains --------------------------------------

    public function test_family_user_can_claim_subdomain_verified(): void
    {
        $user = User::factory()->family()->create();

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('subdomain', 'Fabian')
            ->call('claimSubdomain')
            ->assertHasNoErrors()
            ->assertSee('fabian.ternis.link', escape: false);

        $domain = Domain::where('hostname', 'fabian.ternis.link')->firstOrFail();
        $this->assertSame($user->id, $domain->user_id);
        $this->assertTrue($domain->isVerified());
        $this->assertTrue($domain->isUsableForLinks());
        $this->assertSame('ternis', $domain->type->value ?? (string) $domain->type);
    }

    public function test_claimed_subdomain_is_usable_for_links(): void
    {
        $user = User::factory()->family()->create();

        app(DomainService::class)->claimSubdomain($user, 'fabian');

        $domain = Domain::where('hostname', 'fabian.ternis.link')->firstOrFail();

        $link = app(LinkService::class)->create(
            destinationUrl: 'https://example.com/on-subdomain',
            domain: $domain,
            user: $user,
        );

        $this->assertSame($domain->id, $link->domain_id);
    }

    public function test_claim_rejects_reserved_duplicates_and_second_claims(): void
    {
        $user = User::factory()->family()->create();

        foreach (['www', 'admin', 'api'] as $reserved) {
            Livewire::actingAs($user)
                ->test(DomainManager::class)
                ->set('subdomain', $reserved)
                ->call('claimSubdomain')
                ->assertHasErrors('subdomain');
        }

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('subdomain', 'first-one')
            ->call('claimSubdomain')
            ->assertHasNoErrors();

        // Duplicate (case-insensitive) by another user.
        $other = User::factory()->family()->create();
        Livewire::actingAs($other)
            ->test(DomainManager::class)
            ->set('subdomain', 'FIRST-one')
            ->call('claimSubdomain')
            ->assertHasErrors('subdomain')
            ->assertSee('already taken', escape: false);

        // Second claim by the same user (service-level: the form hides
        // once a subdomain exists, so the message is asserted here).
        try {
            app(DomainService::class)->claimSubdomain($user, 'second-one');
            $this->fail('Expected a one-per-account rejection.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                'one per account',
                (string) $e->validator->errors()->first('subdomain')
            );
        }

        // Too short.
        Livewire::actingAs($other)
            ->test(DomainManager::class)
            ->set('subdomain', 'ab')
            ->call('claimSubdomain')
            ->assertHasErrors('subdomain');
    }

    public function test_standard_users_cannot_claim(): void
    {
        $user = $this->userOnPlan('business'); // role User despite business plan

        $this->assertFalse($user->canClaimSubdomain());

        $this->actingAs($user)
            ->get('http://dash.ternis.link/domains')
            ->assertStatus(200)
            ->assertDontSee('Your ternis.link Subdomain', escape: false);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(DomainService::class)->claimSubdomain($user, 'nope');
    }

    public function test_admin_and_partner_can_claim(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->canClaimSubdomain());
        $this->assertTrue(User::factory()->partner()->create()->canClaimSubdomain());
        $this->assertFalse(User::factory()->create()->canClaimSubdomain());
    }

    // --- tag discovery --------------------------------------------

    public function test_table_filters_by_tag_and_searches_meta(): void
    {
        $user = $this->userOnPlan('free');
        $this->makeLink($user, 'tagged-docs-1', 'https://example.com/docs', 'Docs page', ['docs']);
        $this->makeLink($user, 'tagged-other-1', 'https://example.com/other', 'Other page', ['other']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\LinkTable::class)
            ->assertSee('All tags', escape: false)
            ->assertSee('docs', escape: false)
            ->set('tag', 'docs')
            ->assertSee('tagged-docs-1', escape: false)
            ->assertDontSee('tagged-other-1', escape: false)
            ->set('tag', '')
            ->set('search', 'Other page')
            ->assertSee('tagged-other-1', escape: false)
            ->assertDontSee('tagged-docs-1', escape: false);
    }

    public function test_admin_moderation_search_finds_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->userOnPlan('free');
        $this->makeLink($user, 'mod-tagged-1', 'https://example.com/m', null, ['moderate-me']);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\LinkModeration::class)
            ->set('search', 'moderate-me')
            ->assertSee('mod-tagged-1', escape: false);
    }

    private function makeLink(User $user, string $slug, string $url, ?string $description, array $tags): Link
    {
        return Link::create([
            'slug' => $slug,
            'destination_url' => $url,
            'description' => $description,
            'tags' => $tags,
            'domain_id' => $this->domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }
}
