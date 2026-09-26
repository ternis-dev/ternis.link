<?php

namespace Tests\Feature;

use App\Livewire\Public\ShortenForm;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Concerns\SolvesAltcha;

class PrivacyCaptureTest extends TestCase
{
    use RefreshDatabase;
    use SolvesAltcha;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function test_guest_link_stores_hash_and_encrypted_ip(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/capture-me')
            ->set('altcha_payload', $this->solvedAltchaPayload())
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/capture-me')->firstOrFail();

        // Quota hash unchanged (pepper empty in testing).
        $this->assertSame(hash('sha256', '127.0.0.1'), $link->creator_ip_hash);

        // Encrypted copy decrypts to the real address.
        $this->assertNotNull($link->getAttributes()['creator_ip_encrypted']);
        $this->assertSame('127.0.0.1', Crypt::decryptString($link->getAttributes()['creator_ip_encrypted']));
        $this->assertSame('127.0.0.1', $link->creator_ip_encrypted);
    }

    public function test_disabled_capture_stores_hash_only(): void
    {
        config(['privacy.capture_ips' => false]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/no-capture')
            ->set('altcha_payload', $this->solvedAltchaPayload())
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/no-capture')->firstOrFail();

        $this->assertSame(hash('sha256', '127.0.0.1'), $link->creator_ip_hash);
        $this->assertNull($link->getAttributes()['creator_ip_encrypted']);
    }

    public function test_click_stores_encrypted_ip(): void
    {
        $link = Link::create([
            'slug' => 'capture-click-1',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->get('http://href.nz/capture-click-1')->assertRedirect('https://example.com/target');

        $click = Click::where('link_id', $link->id)->firstOrFail();
        $this->assertSame(hash('sha256', '127.0.0.1'), $click->ip_hash);
        $this->assertSame('127.0.0.1', $click->ip_encrypted);
    }

    public function test_prune_deletes_only_expired_encrypted_ips(): void
    {
        $old = Link::create([
            'slug' => 'prune-old-1',
            'destination_url' => 'https://example.com/old',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'creator_ip_encrypted' => '127.0.0.1',
            'is_active' => true,
        ]);
        \Illuminate\Support\Facades\DB::table('links')->where('id', $old->id)->update(['created_at' => now()->subDays(40)]);
        $oldClick = Click::create([
            'link_id' => $old->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'ip_encrypted' => '127.0.0.1',
            'created_at' => now()->subDays(40),
        ]);

        $fresh = Link::create([
            'slug' => 'prune-fresh-1',
            'destination_url' => 'https://example.com/fresh',
            'domain_id' => $this->publicDomain->id,
            'user_id' => null,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'creator_ip_encrypted' => '127.0.0.1',
            'is_active' => true,
        ]);
        $freshClick = Click::create([
            'link_id' => $fresh->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'ip_encrypted' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->artisan('privacy:prune-ips')
            ->expectsOutputToContain('Pruned encrypted IPs: 1 click(s), 1 link(s)')
            ->assertSuccessful();

        $this->assertNull($old->fresh()->getAttributes()['creator_ip_encrypted']);
        $this->assertNotNull($old->fresh()->creator_ip_hash);

        $this->assertNull($oldClick->fresh()->getAttributes()['ip_encrypted']);
        $this->assertNotNull($oldClick->fresh()->ip_hash);

        $this->assertNotNull($fresh->fresh()->getAttributes()['creator_ip_encrypted']);
        $this->assertNotNull($freshClick->fresh()->getAttributes()['ip_encrypted']);
    }
}
