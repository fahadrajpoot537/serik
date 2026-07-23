<?php

namespace Tests\Unit;

use App\Support\TrebImageStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrebImageStoreTest extends TestCase
{
    private TrebImageStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->store = app(TrebImageStore::class);
    }

    public function test_skips_pdf_remote_asset_and_logs_warning(): void
    {
        Log::spy();

        $url = 'https://trreb-image.ampre.ca/example/listing-photo.pdf';
        Http::fake([
            $url => Http::response('%PDF-1.4 fake pdf body', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $result = $this->store->persistFromRemoteUrl('E12497250', $url);

        $this->assertNull($result);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('TrebImageStore: skipped non-image asset', [
                'listing_key' => 'E12497250',
                'url' => $url,
                'content_type' => 'application/pdf',
            ]);
    }

    public function test_skips_html_remote_asset(): void
    {
        Log::spy();

        $url = 'https://trreb-image.ampre.ca/example/error-page';
        Http::fake([
            $url => Http::response('<html><body>Not found</body></html>', 200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]),
        ]);

        $result = $this->store->persistFromRemoteUrl('W13562228', $url);

        $this->assertNull($result);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('TrebImageStore: skipped non-image asset', \Mockery::on(function (array $context) use ($url): bool {
                return ($context['listing_key'] ?? '') === 'W13562228'
                    && ($context['url'] ?? '') === $url
                    && ($context['content_type'] ?? '') === 'text/html';
            }));
    }

    public function test_skips_empty_remote_response(): void
    {
        Log::spy();

        $url = 'https://trreb-image.ampre.ca/example/empty';
        Http::fake([
            $url => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = $this->store->persistFromRemoteUrl('X13451852', $url);

        $this->assertNull($result);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('TrebImageStore: skipped non-image asset', [
                'listing_key' => 'X13451852',
                'url' => $url,
                'content_type' => 'empty',
            ]);
    }

    public function test_skips_invalid_binary_even_when_header_claims_image(): void
    {
        Log::spy();

        $url = 'https://trreb-image.ampre.ca/example/not-an-image';
        Http::fake([
            $url => Http::response('not-a-real-image-binary', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = $this->store->persistFromRemoteUrl('W13228592', $url);

        $this->assertNull($result);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('TrebImageStore: skipped non-image asset', \Mockery::on(function (array $context) use ($url): bool {
                return ($context['listing_key'] ?? '') === 'W13228592'
                    && ($context['url'] ?? '') === $url
                    && isset($context['content_type']);
            }));
    }

    public function test_persists_valid_jpeg_remote_asset(): void
    {
        $url = 'https://trreb-image.ampre.ca/example/valid.jpg';
        Http::fake([
            $url => Http::response($this->tinyJpegBinary(), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = $this->store->persistFromRemoteUrl('C13595856', $url);

        $this->assertSame('properties/treb/C13595856/cover.webp', $result);
        Storage::disk('public')->assertExists('properties/treb/C13595856/cover.webp');
    }

    public function test_gallery_continues_after_pdf_skip(): void
    {
        Log::spy();

        $pdfUrl = 'https://trreb-image.ampre.ca/example/photo.pdf';
        $jpegUrl = 'https://trreb-image.ampre.ca/example/photo.jpg';

        Http::fake([
            $pdfUrl => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
            $jpegUrl => Http::response($this->tinyJpegBinary(), 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $stored = $this->store->persistGallery('X13509986', [$pdfUrl, $jpegUrl]);

        $this->assertCount(1, $stored);
        $this->assertSame('properties/treb/X13509986/01.webp', $stored[0]);
        Storage::disk('public')->assertExists('properties/treb/X13509986/01.webp');
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('TrebImageStore: skipped non-image asset', \Mockery::type('array'));
    }

    private function tinyJpegBinary(): string
    {
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagejpeg($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }
}
