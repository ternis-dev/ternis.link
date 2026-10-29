<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * my.href.nz has its own design system: its own component set
 * (x-hnz.*), its own layout (sidebar instead of the top bar) and its
 * own stylesheet. It is the only public dashboard; my.ternis.link 301s here.
 */
class HnzDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_host_serves_the_hnz_layout(): void
    {
        $html = $this->actingAs($this->user)
            ->get('http://my.href.nz/')
            ->assertOk()
            ->getContent();

        // The sidebar shell of the my.href.nz design.
        $this->assertStringContainsString('hnz-root', $html);
        $this->assertStringContainsString('hnz-sidebar', $html);
        $this->assertStringContainsString('hnz-nav-link', $html);

        // Its own stylesheet is referenced by the layout (the assets
        // themselves are stubbed out in tests — see withoutVite() in
        // TestCase; ViteInputsTest checks the inputs are registered).
        $this->assertStringContainsString(
            'hnz-dashboard.css',
            file_get_contents(resource_path('views/components/layouts/hnz-dashboard.blade.php'))
        );
    }

    public function test_all_sections_render(): void
    {
        foreach (['/', '/links', '/groups', '/insights', '/api-keys', '/domains', '/settings', '/notifications'] as $path) {
            $this->actingAs($this->user)->get("http://my.href.nz{$path}")->assertOk();
        }
    }

    public function test_retired_hosts_redirect_permanently_to_my_href_nz(): void
    {
        $this->get('http://my.ternis.link/links?x=1')->assertStatus(301)->assertRedirect('http://my.href.nz/links?x=1');
        $this->get('http://my.href.yt/')->assertStatus(301)->assertRedirect('http://my.href.nz/');
    }

    public function test_the_alias_host_uses_its_own_components(): void
    {
        $html = $this->actingAs($this->user)
            ->get('http://my.href.nz/links')
            ->assertOk()
            ->getContent();

        // The my.href.nz button component (rounded-xl + gradient),
        // not the flat emerald one.
        $this->assertStringContainsString('from-violet-600 to-blue-600', $html);
        $this->assertStringNotContainsString('rounded-full font-semibold transition-colors', $html);
    }

    public function test_slug_availability_check_reports_a_free_slug(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class, ['scope' => 'public'])
            ->set('slug', 'a-brand-new-slug')
            ->call('checkSlugAvailability')
            ->assertSet('slugStatus', 'free');
    }

    public function test_slug_availability_check_reports_a_taken_slug(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        Link::create([
            'slug' => 'already-taken',
            'destination_url' => 'https://example.com/taken',
            'domain_id' => $domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(LinkForm::class, ['scope' => 'public'])
            ->set('slug', 'already-taken')
            ->call('checkSlugAvailability')
            ->assertSet('slugStatus', 'taken');
    }
}
