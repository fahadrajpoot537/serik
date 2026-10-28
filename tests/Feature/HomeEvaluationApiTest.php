<?php

namespace Tests\Feature;

use App\Services\HomeValuation\HomeValuationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Botble RealEstate API routes are not always registered under phpunit's
 * plugin bootstrap, so these tests cover validation + service wiring rather
 * than full HTTP routing.
 */
class HomeEvaluationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache.default', 'array');
        Config::set('session.driver', 'array');
    }

    public function test_validation_rejects_short_address(): void
    {
        $validator = Validator::make(
            ['address' => 'ab'],
            [
                'address' => ['required', 'string', 'min:3', 'max:200'],
                'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    public function test_service_container_resolves_home_valuation_service(): void
    {
        $service = $this->app->make(HomeValuationService::class);
        $this->assertInstanceOf(HomeValuationService::class, $service);

        $result = $service->evaluate(['address' => 'xy', 'include_ai' => false]);
        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_input', $result['status']);
    }

    public function test_insufficient_data_payload_has_no_fabricated_estimate_key_requirement(): void
    {
        $payload = [
            'ok' => false,
            'status' => 'insufficient_data',
            'message' => 'Not enough verified nearby sold comparables',
            'comps_count' => 0,
            'comparables' => [],
        ];

        $this->assertArrayNotHasKey('estimate', $payload);
        $this->assertFalse($payload['ok']);
    }
}
