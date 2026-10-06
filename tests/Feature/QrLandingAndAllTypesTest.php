<?php

namespace Tests\Feature;

use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrLandingAndAllTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_qr_landing_page_renders_successfully(): void
    {
        $response = $this->get('http://qr.href.nz/');

        $response->assertOk();
        $response->assertSee('qr.href.nz');
        $response->assertSee('Any QR Code');
        $response->assertSee('Payload Type');
        $response->assertSee('Download SVG');
        $response->assertSee('Download PNG');
    }

    public function test_qr_new_is_a_compact_generator_form(): void
    {
        $response = $this->get('http://qr.href.nz/new');

        $response->assertOk();
        $response->assertSee('Payload Type');
        $response->assertDontSee('Why use qr.href.nz?');
    }

    public function test_url_qr_generates_svg_by_default(): void
    {
        $response = $this->get('http://qr.t-api.de/url/https://example.com/test');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_url_qr_with_png_format(): void
    {
        $response = $this->get('http://qr.t-api.de/url/https://example.com/test?format=png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_url_qr_with_suffix_extension(): void
    {
        $response = $this->get('http://qr.t-api.de/url/https://example.com/test.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_text_qr(): void
    {
        $response = $this->get('http://qr.t-api.de/text/HelloWorld');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');

        $pngResponse = $this->get('http://qr.t-api.de/text/HelloWorld.png');
        $pngResponse->assertOk();
        $pngResponse->assertHeader('Content-Type', 'image/png');
    }

    public function test_wifi_qr(): void
    {
        $response = $this->get('http://qr.t-api.de/wifi?ssid=OfficeNet&password=Secret123&encryption=WPA');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');

        $pathResponse = $this->get('http://qr.t-api.de/wifi/GuestNet?password=Pass');
        $pathResponse->assertOk();
    }

    public function test_vcard_and_contact_qr(): void
    {
        $response = $this->get('http://qr.t-api.de/vcard?first_name=John&last_name=Doe&phone=+123456789&email=john@example.com');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');

        $contactResponse = $this->get('http://qr.t-api.de/contact?first_name=Jane&last_name=Smith');
        $contactResponse->assertOk();
    }

    public function test_email_qr(): void
    {
        $response = $this->get('http://qr.t-api.de/email/support@example.com?subject=Help&body=NeedAssistance');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_phone_and_tel_qr(): void
    {
        $response = $this->get('http://qr.t-api.de/phone/+1234567890');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');

        $telResponse = $this->get('http://qr.t-api.de/tel/+1234567890.png');
        $telResponse->assertOk();
        $telResponse->assertHeader('Content-Type', 'image/png');
    }

    public function test_sms_and_whatsapp_qr(): void
    {
        $smsResponse = $this->get('http://qr.t-api.de/sms/+1234567890?message=Hello');
        $smsResponse->assertOk();

        $waResponse = $this->get('http://qr.t-api.de/whatsapp/436601234567?message=HelloWA');
        $waResponse->assertOk();
    }

    public function test_geo_qr(): void
    {
        $response = $this->get('http://qr.t-api.de/geo/48.2082,16.3738?label=Vienna');
        $response->assertOk();
    }

    public function test_event_and_calendar_qr(): void
    {
        $eventResponse = $this->get('http://qr.t-api.de/event?title=Keynote&location=HallA');
        $eventResponse->assertOk();

        $calResponse = $this->get('http://qr.t-api.de/calendar?title=Conference');
        $calResponse->assertOk();
    }

    public function test_crypto_and_raw_qr(): void
    {
        $cryptoResponse = $this->get('http://qr.t-api.de/crypto/1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa?currency=btc&amount=0.5');
        $cryptoResponse->assertOk();

        $rawResponse = $this->get('http://qr.t-api.de/raw/CUSTOM_PAYLOAD_STRING');
        $rawResponse->assertOk();
    }

    public function test_custom_styling_and_download_disposition(): void
    {
        $response = $this->get('http://qr.t-api.de/url/https://example.com?color=10b981&bg=0f172a&size=500&margin=20&error_correction=H&download=1');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="qr-url.svg"');
    }

    public function test_json_format_returns_structured_data(): void
    {
        $response = $this->getJson('http://qr.t-api.de/url/https://example.com?format=json');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'type' => 'url',
            'payload' => 'https://example.com',
        ]);
        $this->assertNotEmpty($response->json('data_uri'));
        $this->assertNotEmpty($response->json('png_data_uri'));
    }

    public function test_api_v1_qr_post_and_get(): void
    {
        // 1. GET /v1/qr for url (backward compatibility)
        $getUrl = $this->get('http://links.t-api.de/v1/qr?url=https%3A%2F%2Fexample.com');
        $getUrl->assertOk();
        $getUrl->assertHeader('Content-Type', 'image/svg+xml');

        // 2. GET /v1/qr/{type}
        $getWifi = $this->get('http://links.t-api.de/v1/qr/wifi?ssid=ApiWifi&password=secret&format=png');
        $getWifi->assertOk();
        $getWifi->assertHeader('Content-Type', 'image/png');

        // 3. POST /v1/qr with JSON response
        $postResponse = $this->postJson('http://links.t-api.de/v1/qr', [
            'type' => 'vcard',
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '+4312345678',
            'format' => 'json',
            'color' => '059669',
            'error_correction' => 'H',
        ]);

        $postResponse->assertOk();
        $postResponse->assertJson([
            'success' => true,
            'type' => 'vcard',
        ]);
        $this->assertStringContainsString('Alice', $postResponse->json('payload'));
        $this->assertStringContainsString('Wonderland', $postResponse->json('payload'));
    }

    public function test_qr_studio_layout_survives_narrow_screens(): void
    {
        $response = $this->get('http://qr.href.nz/');

        $response->assertOk();
        // Scrollable header nav, fluid preview image, wrapping API URLs.
        $response->assertSee('overflow-x-auto', escape: false);
        $response->assertSee('max-w-60', escape: false);
        $response->assertSee('break-all', escape: false);
    }
}
