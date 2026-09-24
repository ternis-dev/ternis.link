<?php

namespace Tests\Unit;

use App\Exceptions\JunkUrlException;
use App\Services\JunkUrlDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JunkUrlDetectorTest extends TestCase
{
    private JunkUrlDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new JunkUrlDetector();
    }

    /**
     * Every destination from the observed scanner run must be flagged.
     *
     * @return iterable<string, array{string}>
     */
    public static function scannerPayloads(): iterable
    {
        yield 'phpinfo save' => ['https://phpinfo.php.save'];
        yield 'info bak' => ['https://info.php.bak'];
        yield 'phpinfo tilde' => ['https://phpinfo.php~'];
        yield 'phpinfo old' => ['https://phpinfo.php.old'];
        yield 'phpinfo bak' => ['https://phpinfo.php.bak'];
        yield 'server status' => ['https://server-status.php'];
        yield 'server info' => ['https://server-info.php'];
        yield 'old phpinfo' => ['https://old_phpinfo.php'];
        yield 'underscore phpinfo' => ['https://_phpinfo.php'];
        yield 'phpversion' => ['https://phpversion.php'];
        yield 'php-info' => ['https://php-info.php'];
        yield 'debug' => ['https://debug.php'];
        yield 'p' => ['https://p.php'];
        yield 'test' => ['https://test.php'];
        yield 'pinfo' => ['https://pinfo.php'];
        yield 'pi' => ['https://pi.php'];
        yield 'php' => ['https://php.php'];
        yield 'info' => ['https://info.php'];
        yield 'i' => ['https://i.php'];
        yield 'env backup' => ['https://.env.backup1'];
    }

    #[DataProvider('scannerPayloads')]
    public function test_flags_observed_scanner_payloads(string $url): void
    {
        $this->assertTrue($this->detector->isJunk($url), "Expected junk: {$url}");
        $this->assertNotEmpty($this->detector->reasons($url));
    }

    /**
     * Real-world URLs that must keep working.
     *
     * @return iterable<string, array{string}>
     */
    public static function legitimateUrls(): iterable
    {
        yield 'apex' => ['https://example.com'];
        yield 'deep path' => ['https://example.com/blog/some-article?utm_source=x'];
        yield 'short domain' => ['https://x.com'];
        yield 'shortener' => ['https://t.co/abc123'];
        yield 'subdomain' => ['https://shop.example.co.uk/products/1'];
        yield 'ip host' => ['http://192.168.1.1/dashboard'];
        yield 'localhost' => ['http://localhost:8000/callback'];
        yield 'article about phpinfo' => ['https://example.com/blog/how-to-disable-phpinfo'];
        yield 'unix home path' => ['https://example.com/~jane/resume'];
        yield 'query probe words' => ['https://example.com/search?q=phpinfo+tutorial'];
        yield 'test tld' => ['https://staging-env.myapp.test/login'];
        yield 'uppercase' => ['https://Example.COM/Pricing'];
    }

    #[DataProvider('legitimateUrls')]
    public function test_passes_legitimate_urls(string $url): void
    {
        $this->assertFalse($this->detector->isJunk($url), "Expected clean: {$url}");
        $this->assertSame([], $this->detector->reasons($url));
    }

    public function test_flags_probe_paths_and_backup_suffixes(): void
    {
        foreach (['https://example.com/xmlrpc.php', 'https://example.com/.env', 'https://example.com/wp-login.php'] as $url) {
            $this->assertTrue($this->detector->isJunk($url), "Expected junk: {$url}");
        }

        foreach (['https://example.com/wp-config.php.bak', 'https://example.com/export.sql~'] as $url) {
            $this->assertTrue($this->detector->isJunk($url), "Expected junk: {$url}");
        }
    }

    public function test_reject_throws_keyed_validation_exception(): void
    {
        try {
            $this->detector->rejectIfJunk('https://phpinfo.php');
            $this->fail('Expected JunkUrlException.');
        } catch (JunkUrlException $e) {
            $this->assertArrayHasKey('destination_url', $e->errors());
            $this->assertNotEmpty($e->reasons);
        }

        // Clean URLs pass silently.
        $this->detector->rejectIfJunk('https://example.com/real-page');
        $this->assertTrue(true);
    }
}
