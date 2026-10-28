<?php

namespace Tests\Unit;

use App\Services\GoHighLevel\GoHighLevelCircuitBreaker;
use App\Services\GoHighLevel\GoHighLevelHttpClient;
use App\Services\GoHighLevel\GoHighLevelPermanentException;
use App\Services\GoHighLevel\GoHighLevelShowingFieldMapper;
use App\Services\GoHighLevel\GoHighLevelShowingObjectMapper;
use App\Services\GoHighLevel\GoHighLevelShowingObjectRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class GoHighLevelShowingPhoneAndCircuitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.gohighlevel.enabled' => true,
            'services.gohighlevel.api_token' => 'test-token',
            'services.gohighlevel.location_id' => 'loc-test',
            'services.gohighlevel.base_url' => 'https://services.leadconnectorhq.com',
            'services.gohighlevel.api_version' => '2021-07-28',
            'gohighlevel.mls_sync.circuit_breaker_enabled' => true,
            'gohighlevel.mls_sync.circuit_failure_threshold' => 3,
            'gohighlevel.mls_sync.circuit_cooldown_seconds' => 60,
            'gohighlevel.mls_sync.http_retries' => 3,
            'gohighlevel.mls_sync.http_retry_sleep_ms' => 1,
            'gohighlevel.mls_sync.omit_showings_properties' => [],
            'gohighlevel.mls_sync.optional_showings_properties' => [
                'listing_brokerage_phone',
                'listing_brokerage',
                'commission',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_normalize_phone_accepts_nanp_and_rejects_extensions(): void
    {
        $mapper = app(GoHighLevelShowingFieldMapper::class);

        $this->assertSame('+14165551212', $mapper->normalizePhone('(416) 555-1212'));
        $this->assertSame('+14165551212', $mapper->normalizePhone('1-416-555-1212'));
        $this->assertSame('+14165551212', $mapper->normalizePhone('+1 416 555 1212'));
        $this->assertNull($mapper->normalizePhone('416-555-1212 x99'));
        $this->assertNull($mapper->normalizePhone('call office'));
        $this->assertNull($mapper->normalizePhone('555-1212'));
        $this->assertNull($mapper->normalizePhone(null));
    }

    public function test_finalize_preserves_valid_fields_and_omits_invalid_optional_phone(): void
    {
        $defs = [
            'mls_number' => ['dataType' => 'TEXT', 'fieldKey' => 'custom_objects.showings.mls_number'],
            'address' => ['dataType' => 'TEXT', 'fieldKey' => 'custom_objects.showings.address'],
            'price' => ['dataType' => 'MONETORY', 'fieldKey' => 'custom_objects.showings.price'],
            'listing_brokerage' => ['dataType' => 'TEXT', 'fieldKey' => 'custom_objects.showings.listing_brokerage'],
            'listing_brokerage_phone' => ['dataType' => 'PHONE', 'fieldKey' => 'custom_objects.showings.listing_brokerage_phone'],
            'commission' => ['dataType' => 'TEXT', 'fieldKey' => 'custom_objects.showings.commission'],
        ];

        $mapper = app(GoHighLevelShowingObjectMapper::class);
        $omitted = [];
        $out = $mapper->finalizeOutboundProperties([
            'mls_number' => 'W12345678',
            'address' => '123 Main St',
            'price' => ['currency' => 'default', 'value' => 500000.0],
            'listing_brokerage' => 'Example Realty',
            'listing_brokerage_phone' => '416-555-1212 x204',
            'commission' => '2.5%',
            'not_a_real_field' => 'drop-me',
        ], $defs, $omitted);

        $this->assertSame('W12345678', $out['mls_number']);
        $this->assertSame('123 Main St', $out['address']);
        $this->assertSame(['currency' => 'default', 'value' => 500000.0], $out['price']);
        $this->assertSame('Example Realty', $out['listing_brokerage']);
        $this->assertSame('2.5%', $out['commission']);
        $this->assertArrayNotHasKey('listing_brokerage_phone', $out);
        $this->assertArrayNotHasKey('not_a_real_field', $out);
        $this->assertTrue(collect($omitted)->contains(fn ($row) => str_starts_with($row, 'listing_brokerage_phone:')));
    }

    public function test_finalize_keeps_valid_e164_listing_brokerage_phone(): void
    {
        $defs = [
            'mls_number' => ['dataType' => 'TEXT'],
            'listing_brokerage_phone' => ['dataType' => 'PHONE'],
        ];
        $mapper = app(GoHighLevelShowingObjectMapper::class);
        $omitted = [];
        $out = $mapper->finalizeOutboundProperties([
            'mls_number' => 'C100',
            'listing_brokerage_phone' => '(647) 578-9400',
        ], $defs, $omitted);

        $this->assertSame('+16475789400', $out['listing_brokerage_phone']);
        $this->assertSame([], $omitted);
    }

    public function test_finalize_honors_config_omit_list(): void
    {
        config(['gohighlevel.mls_sync.omit_showings_properties' => ['listing_brokerage_phone']]);
        $defs = [
            'mls_number' => ['dataType' => 'TEXT'],
            'listing_brokerage_phone' => ['dataType' => 'PHONE'],
            'address' => ['dataType' => 'TEXT'],
        ];
        $mapper = app(GoHighLevelShowingObjectMapper::class);
        $omitted = [];
        $out = $mapper->finalizeOutboundProperties([
            'mls_number' => 'C100',
            'address' => '1 Queen St',
            'listing_brokerage_phone' => '+16475789400',
        ], $defs, $omitted);

        $this->assertArrayHasKey('address', $out);
        $this->assertArrayNotHasKey('listing_brokerage_phone', $out);
        $this->assertContains('listing_brokerage_phone:config_omit', $omitted);
    }

    public function test_http_400_validation_is_not_retried_and_does_not_open_circuit(): void
    {
        Http::fake([
            'services.leadconnectorhq.com/*' => Http::response(
                ['message' => "We couldn't validate the mapped field: listing_brokerage_phone."],
                400
            ),
        ]);

        $client = app(GoHighLevelHttpClient::class);

        try {
            $client->put('/objects/custom_objects.showings/records/rec1', [
                'properties' => ['listing_brokerage_phone' => '+14165551212'],
            ]);
            $this->fail('Expected GoHighLevelPermanentException');
        } catch (GoHighLevelPermanentException $e) {
            $this->assertSame(400, $e->httpStatus);
            $this->assertSame('listing_brokerage_phone', $e->invalidField);
        }

        Http::assertSentCount(1);
        $snap = GoHighLevelCircuitBreaker::snapshot();
        $this->assertSame('closed', $snap['status']);
        $this->assertSame(0, $snap['failures']);
    }

    public function test_transient_http_503_is_retried_and_trips_circuit_after_exhausted_retries(): void
    {
        config([
            'gohighlevel.mls_sync.http_retries' => 2,
            // max(2, …) is enforced inside the circuit breaker.
            'gohighlevel.mls_sync.circuit_failure_threshold' => 2,
        ]);

        Http::fake([
            'services.leadconnectorhq.com/*' => Http::response(['message' => 'unavailable'], 503),
        ]);

        $client = app(GoHighLevelHttpClient::class);

        // First exhausted request records one circuit failure; second opens the breaker.
        for ($i = 0; $i < 2; $i++) {
            try {
                $client->get('/contacts/');
                $this->fail('Expected exception after retries');
            } catch (\Throwable $e) {
                $this->assertTrue(
                    str_contains($e->getMessage(), '503')
                    || str_contains($e->getMessage(), 'failed after retries')
                    || str_contains($e->getMessage(), 'unavailable')
                    || str_contains($e->getMessage(), 'circuit open'),
                    $e->getMessage()
                );
            }
        }

        Http::assertSentCount(4); // 2 attempts × 2 exhausted calls
        $snap = GoHighLevelCircuitBreaker::snapshot();
        $this->assertSame('open', $snap['status']);
    }

    public function test_circuit_stays_closed_after_permanent_validation_failures(): void
    {
        Http::fake([
            'services.leadconnectorhq.com/*' => Http::sequence()
                ->push(['message' => "We couldn't validate the mapped field: listing_brokerage_phone."], 400)
                ->push(['message' => "We couldn't validate the mapped field: listing_brokerage_phone."], 400)
                ->push(['message' => "We couldn't validate the mapped field: listing_brokerage_phone."], 400)
                ->push(['message' => "We couldn't validate the mapped field: listing_brokerage_phone."], 400)
                ->push(['message' => "We couldn't validate the mapped field: listing_brokerage_phone."], 400),
        ]);

        $client = app(GoHighLevelHttpClient::class);
        for ($i = 0; $i < 5; $i++) {
            try {
                $client->put('/objects/custom_objects.showings/records/r' . $i, [
                    'properties' => ['listing_brokerage_phone' => '+14165551212'],
                ]);
            } catch (GoHighLevelPermanentException) {
                // expected
            }
        }

        $snap = GoHighLevelCircuitBreaker::snapshot();
        $this->assertSame('closed', $snap['status']);
        $this->assertSame(0, $snap['failures']);
        $this->assertTrue(GoHighLevelCircuitBreaker::allow());
    }

    public function test_permanent_exception_parses_mapped_field_from_message(): void
    {
        $e = GoHighLevelPermanentException::fromApiMessage(
            'GoHighLevel API error HTTP 400: We couldn\'t validate the mapped field: listing_brokerage_phone.',
            400
        );
        $this->assertSame('listing_brokerage_phone', $e->invalidField);
        $this->assertSame(400, $e->httpStatus);
    }

    public function test_write_fallback_strips_optional_invalid_phone_and_retries(): void
    {
        $repo = Mockery::mock(GoHighLevelShowingObjectRepository::class);
        $http = Mockery::mock(GoHighLevelHttpClient::class);
        $objectMapper = app(GoHighLevelShowingObjectMapper::class);

        $http->shouldReceive('lastStatus')->andReturn(200);
        $repo->shouldReceive('updateRecord')
            ->once()
            ->with('rec-1', Mockery::on(function (array $props) {
                return isset($props['listing_brokerage_phone']);
            }))
            ->andThrow(GoHighLevelPermanentException::fromApiMessage(
                'GoHighLevel API error HTTP 400: We couldn\'t validate the mapped field: listing_brokerage_phone.',
                400
            ));
        $repo->shouldReceive('updateRecord')
            ->once()
            ->with('rec-1', Mockery::on(function (array $props) {
                return ! array_key_exists('listing_brokerage_phone', $props)
                    && ($props['mls_number'] ?? null) === 'W999'
                    && ($props['address'] ?? null) === '9 King St';
            }))
            ->andReturn(['id' => 'rec-1']);

        $sync = new \App\Services\GoHighLevel\GoHighLevelShowingSyncService($http, $objectMapper, $repo);
        $ref = new \ReflectionClass($sync);
        $method = $ref->getMethod('writeRecordWithOptionalFieldFallback');
        $method->setAccessible(true);

        [$record, $recordId, $status, $properties] = $method->invoke(
            $sync,
            'rec-1',
            '',
            [
                'mls_number' => 'W999',
                'address' => '9 King St',
                'listing_brokerage_phone' => '+14165551212',
            ],
            [
                'mls_number' => ['dataType' => 'TEXT'],
                'address' => ['dataType' => 'TEXT'],
                'listing_brokerage_phone' => ['dataType' => 'PHONE'],
            ]
        );

        $this->assertSame('rec-1', $recordId);
        $this->assertSame(200, $status);
        $this->assertSame('W999', $properties['mls_number']);
        $this->assertArrayNotHasKey('listing_brokerage_phone', $properties);
        $this->assertSame('rec-1', $record['id']);
    }
}
