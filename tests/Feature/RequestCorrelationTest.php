<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class RequestCorrelationTest extends TestCase
{
    public function test_accepts_and_returns_safe_request_id(): void
    {
        $response = $this->get('/health/live', [
            'X-Request-ID' => 'corr-test-12345',
        ]);

        $response->assertOk()->assertHeader('X-Request-ID', 'corr-test-12345');
    }

    public function test_generates_request_id_when_header_missing(): void
    {
        $response = $this->get('/health/live');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_http_500_keeps_request_id_and_hides_exception_when_debug_off(): void
    {
        Config::set('app.debug', false);

        Route::get('/__serik-probe-500', function () {
            throw new RuntimeException('secret-probe-exception');
        });

        $response = $this->get('/__serik-probe-500', [
            'X-Request-ID' => 'probe-id-12345',
        ]);

        $response->assertStatus(500);
        $response->assertHeader('X-Request-ID', 'probe-id-12345');
        $response->assertDontSee('secret-probe-exception');
    }
}
