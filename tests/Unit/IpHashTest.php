<?php

namespace Tests\Unit;

use App\Support\IpHash;
use Tests\TestCase;

class IpHashTest extends TestCase
{
    public function test_empty_pepper_matches_legacy_sha256(): void
    {
        config(['app.ip_hash_pepper' => '']);

        $this->assertSame(hash('sha256', '203.0.113.7'), IpHash::make('203.0.113.7'));
    }

    public function test_blank_input_hashes_to_null(): void
    {
        $this->assertNull(IpHash::make(null));
        $this->assertNull(IpHash::make(''));
        $this->assertNull(IpHash::make('   '));
    }

    public function test_pepper_changes_output_deterministically(): void
    {
        config(['app.ip_hash_pepper' => 'test-pepper-value']);

        $first = IpHash::make('203.0.113.7');
        $second = IpHash::make('203.0.113.7');

        $this->assertIsString($first);
        $this->assertSame($first, $second);
        $this->assertSame(hash_hmac('sha256', '203.0.113.7', 'test-pepper-value'), $first);
        $this->assertNotSame(hash('sha256', '203.0.113.7'), $first);
    }
}
