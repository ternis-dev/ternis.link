<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkGroupsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'clicked.at')->first();
    }

    private function makeLink(string $slug, int $clicks = 0): Link
    {
        return Link::create([
            'slug' => $slug,
            'destination_url' => 'https://ternis.dev/'.$slug,
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'click_count' => $clicks,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_groups(): void
    {
        $this->get('http://dash.ternis.link/groups')->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_user_can_list_create_and_open_group(): void
    {
        $this->actingAs($this->user)->get('http://dash.ternis.link/groups')->assertStatus(200)->assertSee('Link Groups');

        $response = $this->actingAs($this->user)->post('http://dash.ternis.link/groups', [
            'name' => 'Q4 Campaign',
            'description' => 'Fall push',
            'color' => '#ff0000',
        ]);
        $response->assertRedirect();

        $group = LinkGroup::where('user_id', $this->user->id)->first();
        $this->assertNotNull($group);
        $this->assertSame('Q4 Campaign', $group->name);

        $this->actingAs($this->user)->get("http://dash.ternis.link/groups/{$group->id}")
            ->assertStatus(200)->assertSee('Q4 Campaign');
    }

    public function test_nested_groups_combine_stats(): void
    {
        $parent = $this->user->groups()->create(['name' => 'Parent']);
        $child = $this->user->groups()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $a = $this->makeLink('group-a', 5);
        $b = $this->makeLink('group-b', 7);

        $parent->items()->create(['link_id' => $a->id, 'sort_order' => 0]);
        $child->items()->create(['link_id' => $b->id, 'sort_order' => 0]);

        $this->assertSame([$child->id], $parent->fresh()->descendantIds());
        $this->assertCount(2, $parent->fresh()->allLinkIds());
        $this->assertSame(12, $parent->fresh()->totalClicks());

        $this->actingAs($this->user)->get("http://dash.ternis.link/groups/{$parent->id}")
            ->assertStatus(200)->assertSee('12');
    }

    public function test_cannot_nest_group_inside_its_descendant(): void
    {
        $parent = $this->user->groups()->create(['name' => 'Parent']);
        $child = $this->user->groups()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->actingAs($this->user)->put("http://dash.ternis.link/groups/{$parent->id}", [
            'name' => 'Parent',
            'parent_id' => $child->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_add_and_remove_links(): void
    {
        $group = $this->user->groups()->create(['name' => 'Bundle']);
        $link = $this->makeLink('group-c');

        $this->actingAs($this->user)->post("http://dash.ternis.link/groups/{$group->id}/links", [
            'link_ids' => [$link->id],
        ])->assertRedirect();

        $this->assertTrue($group->fresh()->links()->where('links.id', $link->id)->exists());

        $this->actingAs($this->user)->delete("http://dash.ternis.link/groups/{$group->id}/links/{$link->id}")
            ->assertRedirect();

        $this->assertFalse($group->fresh()->links()->where('links.id', $link->id)->exists());
        // The link itself survives detaching.
        $this->assertNotNull(Link::find($link->id));
    }

    public function test_delete_moves_subgroups_up_and_keeps_links(): void
    {
        $parent = $this->user->groups()->create(['name' => 'Parent']);
        $child = $this->user->groups()->create(['name' => 'Child', 'parent_id' => $parent->id]);
        $link = $this->makeLink('group-d');
        $parent->items()->create(['link_id' => $link->id, 'sort_order' => 0]);

        $this->actingAs($this->user)->delete("http://dash.ternis.link/groups/{$parent->id}")->assertRedirect();

        $this->assertTrue($parent->fresh()->is_removed);
        $this->assertNull($child->fresh()->parent_id);
        $this->assertNotNull(Link::find($link->id));
    }

    public function test_users_cannot_see_each_others_groups(): void
    {
        $group = $this->user->groups()->create(['name' => 'Mine']);
        $other = User::factory()->create();

        $this->actingAs($other)->get("http://dash.ternis.link/groups/{$group->id}")->assertStatus(404);
    }
}
