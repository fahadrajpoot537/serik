<?php

namespace Tests\Unit;

use App\Services\HomeValuation\GroqValuationExplainer;
use App\Services\HomeValuation\HomeValuationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HomeValuationServiceTest extends TestCase
{
    private function subject(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'address' => '100 Test Street, Mississauga, ON L5B 1A1',
            'beds' => 3,
            'baths' => 2,
            'sqft' => 1500,
            'type' => 'Detached',
            'latitude' => 43.5890,
            'longitude' => -79.6441,
        ], $overrides);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function soldComps(): array
    {
        return [
            ['address' => '101 Test St', 'sold_price' => 900000, 'asking_price' => null, 'sold_date' => '2025-11-01', 'distance_km' => 0.4, 'beds' => 3, 'baths' => 2, 'sqft' => 1480, 'type' => 'Detached', 'ppsf' => 608, 'weight' => 0.9, 'mls' => 'W****01'],
            ['address' => '102 Test St', 'sold_price' => 920000, 'asking_price' => null, 'sold_date' => '2025-10-15', 'distance_km' => 0.6, 'beds' => 3, 'baths' => 2, 'sqft' => 1520, 'type' => 'Detached', 'ppsf' => 605, 'weight' => 0.85, 'mls' => 'W****02'],
            ['address' => '103 Test St', 'sold_price' => 880000, 'asking_price' => null, 'sold_date' => '2025-09-20', 'distance_km' => 0.8, 'beds' => 3, 'baths' => 3, 'sqft' => 1460, 'type' => 'Detached', 'ppsf' => 603, 'weight' => 0.8, 'mls' => 'W****03'],
            ['address' => '104 Test St', 'sold_price' => 910000, 'asking_price' => null, 'sold_date' => '2025-08-12', 'distance_km' => 1.0, 'beds' => 4, 'baths' => 2, 'sqft' => 1550, 'type' => 'Detached', 'ppsf' => 587, 'weight' => 0.7, 'mls' => 'W****04'],
        ];
    }

    public function test_sold_comps_produce_deterministic_estimate_and_range(): void
    {
        $service = new HomeValuationService(new GroqValuationExplainer);
        $a = $service->calculateFromSyntheticSoldComps($this->subject(), $this->soldComps(), [
            'radius_km' => 1.0,
            'months' => 12,
            'expanded' => false,
        ]);
        $b = $service->calculateFromSyntheticSoldComps($this->subject(), $this->soldComps(), [
            'radius_km' => 1.0,
            'months' => 12,
            'expanded' => false,
        ]);

        $this->assertTrue($a['ok']);
        $this->assertSame('sold_comparables', $a['basis']);
        $this->assertSame($a['estimate'], $b['estimate']);
        $this->assertSame($a['low'], $b['low']);
        $this->assertSame($a['high'], $b['high']);
        $this->assertGreaterThan(0, $a['estimate']);
        $this->assertLessThanOrEqual($a['high'], $a['estimate']); // estimate <= high
        $this->assertGreaterThanOrEqual($a['low'], $a['estimate']); // estimate >= low
        $this->assertSame(4, $a['comps_count']);
        $this->assertContains($a['confidence'], ['high', 'medium', 'low']);
        $this->assertStringContainsString('sold', strtolower($a['methodology']));
        $this->assertStringNotContainsString('asking prices are confirmed sold', strtolower($a['methodology']));
    }

    public function test_outlier_prices_do_not_dominate_range_when_clustered_comps_exist(): void
    {
        $service = new HomeValuationService(new GroqValuationExplainer);
        $comps = $this->soldComps();
        $comps[] = [
            'address' => '999 Outlier',
            'sold_price' => 2500000,
            'asking_price' => null,
            'sold_date' => '2025-07-01',
            'distance_km' => 0.5,
            'beds' => 3,
            'baths' => 2,
            'sqft' => 1500,
            'type' => 'Detached',
            'ppsf' => 1667,
            'weight' => 0.95,
            'mls' => 'W****99',
        ];

        // rejectOutliers runs in scoreAndSelectComps; synthetic path skips that.
        // Ensure calculator still returns a finite estimate without treating outlier as sole truth.
        $result = $service->calculateFromSyntheticSoldComps($this->subject(), $comps, [
            'radius_km' => 2.0,
            'months' => 12,
            'expanded' => false,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertLessThan(2_000_000, $result['estimate']);
    }

    public function test_missing_sqft_falls_back_to_sold_price_median_method(): void
    {
        $service = new HomeValuationService(new GroqValuationExplainer);
        $comps = array_map(static function (array $c): array {
            $c['ppsf'] = null;
            $c['sqft'] = null;

            return $c;
        }, $this->soldComps());

        $result = $service->calculateFromSyntheticSoldComps($this->subject(['sqft' => 0]), $comps);

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('sold', strtolower($result['methodology']));
    }

    public function test_recent_subject_sale_anchors_estimate_near_close_price(): void
    {
        $service = new HomeValuationService(new GroqValuationExplainer);

        // Distant smaller comps with high PPSF would otherwise inflate the mid.
        $comps = [
            ['address' => 'Near Match', 'sold_price' => 647000, 'asking_price' => null, 'sold_date' => '2026-05-29', 'distance_km' => 0.4, 'beds' => 3, 'baths' => 3, 'sqft' => 1750, 'type' => 'Att/Row/Townhouse', 'ppsf' => 370, 'weight' => 0.82, 'mls' => 'X****01'],
            ['address' => 'Small Far', 'sold_price' => 583277, 'asking_price' => null, 'sold_date' => '2026-05-29', 'distance_km' => 3.5, 'beds' => 3, 'baths' => 3, 'sqft' => 1300, 'type' => 'Att/Row/Townhouse', 'ppsf' => 449, 'weight' => 0.51, 'mls' => 'X****02'],
            ['address' => 'Small Far 2', 'sold_price' => 630000, 'asking_price' => null, 'sold_date' => '2026-03-04', 'distance_km' => 3.7, 'beds' => 2, 'baths' => 2, 'sqft' => 1300, 'type' => 'Att/Row/Townhouse', 'ppsf' => 485, 'weight' => 0.48, 'mls' => 'X****03'],
            ['address' => 'Far Same Size', 'sold_price' => 715500, 'asking_price' => null, 'sold_date' => '2026-02-12', 'distance_km' => 3.8, 'beds' => 2, 'baths' => 3, 'sqft' => 1750, 'type' => 'Att/Row/Townhouse', 'ppsf' => 409, 'weight' => 0.54, 'mls' => 'X****04'],
        ];

        $result = $service->calculateFromSyntheticSoldComps($this->subject([
            'address' => '456 Buckthorn Drive, Kingston, Ontario',
            'sqft' => 1750,
            'type' => 'Att/Row/Townhouse',
            'close_price' => 601372,
            'close_date' => now()->subMonths(4)->toDateTimeString(),
            'mls_status' => 'Sold',
            'transaction_type' => 'For Sale',
        ]), $comps, [
            'radius_km' => 6.0,
            'months' => 12,
            'expanded' => true,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertLessThan(680000, $result['estimate']);
        $this->assertGreaterThan(540000, $result['estimate']);
        $this->assertNotNull($result['subject_sale_anchor'] ?? null);
        $this->assertStringContainsString('anchor', strtolower($result['methodology']));
    }

    public function test_active_list_price_soft_anchors_mid_estimate(): void
    {
        $service = new HomeValuationService(new GroqValuationExplainer);
        $comps = [
            ['address' => 'Near A', 'sold_price' => 830000, 'asking_price' => null, 'sold_date' => '2026-05-27', 'distance_km' => 1.1, 'beds' => 2, 'baths' => 2, 'sqft' => 1300, 'type' => 'Detached', 'ppsf' => 638, 'weight' => 0.7, 'mls' => 'X****1'],
            ['address' => 'Near B', 'sold_price' => 710000, 'asking_price' => null, 'sold_date' => '2026-06-10', 'distance_km' => 1.5, 'beds' => 3, 'baths' => 2, 'sqft' => 1750, 'type' => 'Detached', 'ppsf' => 406, 'weight' => 0.55, 'mls' => 'X****2'],
            ['address' => 'Near C', 'sold_price' => 728000, 'asking_price' => null, 'sold_date' => '2026-07-16', 'distance_km' => 2.0, 'beds' => 3, 'baths' => 2, 'sqft' => 1750, 'type' => 'Detached', 'ppsf' => 416, 'weight' => 0.5, 'mls' => 'X****3'],
            ['address' => 'Far Small', 'sold_price' => 655000, 'asking_price' => null, 'sold_date' => '2026-03-26', 'distance_km' => 2.6, 'beds' => 2, 'baths' => 1, 'sqft' => 900, 'type' => 'Detached', 'ppsf' => 728, 'weight' => 0.4, 'mls' => 'X****4'],
            ['address' => 'Far Small 2', 'sold_price' => 615000, 'asking_price' => null, 'sold_date' => '2026-03-30', 'distance_km' => 2.5, 'beds' => 2, 'baths' => 1, 'sqft' => 900, 'type' => 'Detached', 'ppsf' => 683, 'weight' => 0.42, 'mls' => 'X****5'],
        ];

        $result = $service->calculateFromSyntheticSoldComps($this->subject([
            'address' => '47 Broadway Avenue, Hamilton, Ontario',
            'sqft' => 1300,
            'type' => 'Detached',
            'list_price' => 649900,
            'mls_status' => 'Price Change',
            'close_price' => 0,
            'close_date' => null,
        ]), $comps, [
            'radius_km' => 2.0,
            'months' => 12,
            'expanded' => false,
        ]);

        $this->assertTrue($result['ok']);
        // Soft list anchor should pull mid below a pure high-comp blend (~$800k+).
        $this->assertLessThan(760000, $result['estimate']);
        $this->assertGreaterThan(600000, $result['estimate']);
        $this->assertLessThan(900000, $result['high']);
    }

    public function test_evaluate_rejects_short_address_without_db(): void
    {
        $service = new HomeValuationService(new GroqValuationExplainer);
        $result = $service->evaluate(['address' => 'ab', 'include_ai' => false]);

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_input', $result['status']);
    }

    public function test_groq_skipped_when_api_key_missing(): void
    {
        config(['home_valuation.groq.api_key' => '']);
        $explainer = new GroqValuationExplainer;
        $out = $explainer->explain([
            'status' => 'valued',
            'basis' => 'sold_comparables',
            'estimate' => 900000,
            'low' => 850000,
            'high' => 950000,
            'confidence' => 'medium',
            'confidence_reason' => '4 comps',
            'comps_count' => 4,
            'comparables' => $this->soldComps(),
        ]);

        $this->assertSame('skipped_no_key', $out['status']);
        $this->assertNotEmpty($out['text']);
        $this->assertStringContainsString('900,000', $out['text']);
    }

    public function test_groq_rate_limit_falls_back_safely(): void
    {
        config([
            'home_valuation.groq.api_key' => 'test-key',
            'home_valuation.groq.endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
        ]);
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'rate'], 429),
        ]);

        $explainer = new GroqValuationExplainer;
        $out = $explainer->explain([
            'status' => 'valued',
            'basis' => 'sold_comparables',
            'estimate' => 900000,
            'low' => 850000,
            'high' => 950000,
            'confidence' => 'medium',
            'confidence_reason' => '4 comps',
            'comps_count' => 4,
            'comparables' => [],
        ]);

        $this->assertSame('rate_limited', $out['status']);
        $this->assertStringContainsString('900,000', $out['text']);
    }

    public function test_groq_timeout_falls_back(): void
    {
        config([
            'home_valuation.groq.api_key' => 'test-key',
            'home_valuation.groq.endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'home_valuation.groq.timeout_seconds' => 1,
        ]);
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $explainer = new GroqValuationExplainer;
        $out = $explainer->explain([
            'status' => 'valued',
            'basis' => 'sold_comparables',
            'estimate' => 800000,
            'low' => 760000,
            'high' => 840000,
            'confidence' => 'low',
            'confidence_reason' => 'limited',
            'comps_count' => 3,
            'comparables' => [],
        ]);

        $this->assertSame('timeout_or_error', $out['status']);
        $this->assertStringContainsString('800,000', $out['text']);
    }

    public function test_groq_rejects_fabricated_dollar_amounts(): void
    {
        config([
            'home_valuation.groq.api_key' => 'test-key',
            'home_valuation.groq.endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
        ]);
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'This home is worth about $9,999,999 based on secret sales.',
                    ],
                ]],
            ], 200),
        ]);

        $explainer = new GroqValuationExplainer;
        $out = $explainer->explain([
            'status' => 'valued',
            'basis' => 'sold_comparables',
            'estimate' => 900000,
            'low' => 850000,
            'high' => 950000,
            'confidence' => 'medium',
            'confidence_reason' => '4 comps',
            'comps_count' => 4,
            'comparables' => $this->soldComps(),
        ]);

        $this->assertSame('rejected_fabrication', $out['status']);
        $this->assertStringNotContainsString('9,999,999', $out['text']);
    }

    public function test_groq_accepts_verified_amounts(): void
    {
        config([
            'home_valuation.groq.api_key' => 'test-key',
            'home_valuation.groq.endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
        ]);
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Estimated value about $900,000 with a range of $850,000–$950,000 from 4 sold comps.',
                    ],
                ]],
            ], 200),
        ]);

        $explainer = new GroqValuationExplainer;
        $out = $explainer->explain([
            'status' => 'valued',
            'basis' => 'sold_comparables',
            'estimate' => 900000,
            'low' => 850000,
            'high' => 950000,
            'confidence' => 'medium',
            'confidence_reason' => '4 comps',
            'comps_count' => 4,
            'comparables' => $this->soldComps(),
        ]);

        $this->assertSame('ok', $out['status']);
        $this->assertStringContainsString('900,000', $out['text']);
    }
}
