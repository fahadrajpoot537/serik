<?php

namespace Tests\Unit;

use App\Support\AddressNormalizer;
use Tests\TestCase;

class AddressSearchRankingTest extends TestCase
{
    private const INTENDED = '110 Queen Isabella Crescent, Vaughan, ON L6A 3J8';

    private const DECOY_QUEEN_ST = '110 Queen Street, Vaughan, ON L4H 0A1';

    private const DECOY_OTHER_CITY = '110 Queen Street, Toronto, ON M5H 2N2';

    private const DECOY_ISABELLA_ST = '110 Isabella Street, Vaughan, ON L6A 1A1';

    private const DECOY_NO_NUMBER = '52 Queen Isabella Crescent, Vaughan, ON L6A 3J8';

    /**
     * @return list<string>
     */
    private function regressionQueries(): array
    {
        return [
            '110 Queen Isabella Crescent, Vaughan',
            '110 queen isabella crescent vaughan',
            '110 Queen Isabella Cres, Vaughan',
            '110 Queen Isabella Cr., Vaughan',
            '110  Queen Isabella  Crescent, Vaughan',
            '110 Queen Isabella Crescent Vaughan',
            '110 Queen Isabella Cresent, Vaughan',
            '110 Queen Isabela Crescent, Vaughan',
            '110 Queen Isabella Crescent, VAUGHAN',
            '110 Queen Isabella Crescent',
            '110 Queen Isabella, Vaughan',
        ];
    }

    public function test_regression_query_parses_full_street_name_not_just_queen(): void
    {
        $parsed = AddressNormalizer::parseQuery('110 Queen Isabella Crescent, Vaughan');

        $this->assertNotNull($parsed);
        $this->assertSame('110', $parsed['street_number']);
        $this->assertSame('Queen Isabella', $parsed['street_name']);
        $this->assertSame('Crescent', $parsed['street_suffix']);
        $this->assertSame('crescent', $parsed['street_suffix_normalized']);
        $this->assertSame('Vaughan', $parsed['municipality']);
        $this->assertSame(['queen', 'isabella'], $parsed['significant_tokens']);
        $this->assertStringContainsString('Isabella', $parsed['street_part']);
        $this->assertSame('110 Queen Isabella Crescent Vaughan', AddressNormalizer::meiliQuery($parsed));
    }

    public function test_suffix_aliases_and_spacing_normalize(): void
    {
        foreach (['Cres', 'Cr', 'Cr.', 'Crescent', 'Cresent'] as $suffix) {
            $parsed = AddressNormalizer::parseQuery('110 Queen Isabella ' . $suffix . ', Vaughan');
            $this->assertNotNull($parsed, $suffix);
            $this->assertSame('crescent', $parsed['street_suffix_normalized'], $suffix);
            $this->assertSame(['queen', 'isabella'], $parsed['significant_tokens'], $suffix);
        }
    }

    public function test_intended_address_outranks_queen_street_decoys_for_every_variation(): void
    {
        foreach ($this->regressionQueries() as $query) {
            $parsed = AddressNormalizer::parseQuery($query);
            $this->assertNotNull($parsed, $query);

            $intended = AddressNormalizer::scoreAddress(self::INTENDED, $parsed, 'New');
            $queenSt = AddressNormalizer::scoreAddress(self::DECOY_QUEEN_ST, $parsed, 'New');
            $otherCity = AddressNormalizer::scoreAddress(self::DECOY_OTHER_CITY, $parsed, 'New');
            $isabellaSt = AddressNormalizer::scoreAddress(self::DECOY_ISABELLA_ST, $parsed, 'New');
            $noNumber = AddressNormalizer::scoreAddress(self::DECOY_NO_NUMBER, $parsed, 'New');

            $this->assertGreaterThan(0, $intended, $query);
            $this->assertSame(0, $queenSt, $query);
            $this->assertSame(0, $otherCity, $query);
            $this->assertSame(0, $isabellaSt, $query);
            $this->assertSame(0, $noNumber, $query);
            $this->assertTrue(AddressNormalizer::addressMatches(self::INTENDED, $parsed), $query);
            $this->assertFalse(AddressNormalizer::addressMatches(self::DECOY_QUEEN_ST, $parsed), $query);
        }
    }

    public function test_queen_street_query_still_finds_queen_street(): void
    {
        $parsed = AddressNormalizer::parseQuery('110 Queen Street, Vaughan');

        $this->assertNotNull($parsed);
        $this->assertSame('Queen', $parsed['street_name']);
        $this->assertSame('street', $parsed['street_suffix_normalized']);
        $this->assertSame(['queen'], $parsed['significant_tokens']);

        $queenSt = AddressNormalizer::scoreAddress(self::DECOY_QUEEN_ST, $parsed, 'New');
        $intended = AddressNormalizer::scoreAddress(self::INTENDED, $parsed, 'New');

        $this->assertGreaterThan(0, $queenSt);
        $this->assertTrue(AddressNormalizer::addressMatches(self::DECOY_QUEEN_ST, $parsed));
        $this->assertGreaterThan($intended, $queenSt);
    }

    public function test_sort_rows_keeps_complete_address_first(): void
    {
        $parsed = AddressNormalizer::parseQuery('110 Queen Isabella Crescent, Vaughan');
        $rows = AddressNormalizer::sortRows([
            ['UnparsedAddress' => self::DECOY_QUEEN_ST, 'ListingKey' => 'W111', 'MlsStatus' => 'New'],
            ['UnparsedAddress' => self::INTENDED, 'ListingKey' => 'N999', 'MlsStatus' => 'Expired'],
            ['UnparsedAddress' => self::DECOY_OTHER_CITY, 'ListingKey' => 'C222', 'MlsStatus' => 'New'],
        ], $parsed);

        $this->assertSame('N999', $rows[0]['ListingKey']);
        $this->assertSame(self::INTENDED, $rows[0]['UnparsedAddress']);
    }

    public function test_non_address_modes_are_not_classified_as_address_queries(): void
    {
        $this->assertNull(AddressNormalizer::parseQuery('N3605177'));
        $this->assertNull(AddressNormalizer::parseQuery('L6A 3J8'));
        $this->assertNull(AddressNormalizer::parseQuery('L6A3J8'));
        $this->assertNull(AddressNormalizer::parseQuery('Vaughan'));
        $this->assertNull(AddressNormalizer::parseQuery('Queen'));
        $this->assertNull(AddressNormalizer::parseQuery('Maple'));
    }

    public function test_unit_prefix_is_preserved_and_distinguishes_units(): void
    {
        $parsed = AddressNormalizer::parseQuery('12 110 Queen Isabella Crescent, Vaughan');

        $this->assertNotNull($parsed);
        $this->assertSame('110', $parsed['street_number']);
        $this->assertSame('12', $parsed['unit_number']);
        $this->assertTrue(AddressNormalizer::addressMatches('12 - 110 Queen Isabella Crescent, Vaughan, ON', $parsed));
        $this->assertFalse(AddressNormalizer::addressMatches('13 - 110 Queen Isabella Crescent, Vaughan, ON', $parsed));
    }

    public function test_injection_and_overlong_input_are_handled_safely(): void
    {
        $this->assertNull(AddressNormalizer::parseQuery("' OR 1=1 --"));
        $this->assertNull(AddressNormalizer::parseQuery('"; DROP TABLE re_properties;'));
        $parsed = AddressNormalizer::parseQuery('110 Queen Isabella Crescent, Vaughan" OR "1"="1');
        $this->assertNotNull($parsed);
        $this->assertSame('110', $parsed['street_number']);
        $this->assertSame(['queen', 'isabella'], $parsed['significant_tokens']);
    }

    public function test_search_pipeline_uses_central_normalizer_and_does_not_scan_with_leading_like(): void
    {
        $controller = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php'));
        $live = file_get_contents(base_path('platform/plugins/real-estate/src/Services/LiveTrebPropertyFallbackService.php'));
        $search = file_get_contents(base_path('platform/plugins/real-estate/src/Services/PropertySearchService.php'));
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));
        $map = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'));

        $this->assertStringContainsString('AddressNormalizer::parseQuery', $controller);
        $this->assertStringContainsString('filterAndRankAddressRows', $controller);
        $this->assertStringContainsString("smart_search_v2:", $controller);
        $this->assertStringContainsString('searchIds(strtoupper($keyword)', $controller);
        $this->assertStringContainsString('Keep Meili hits when the strict ranker empties them', $controller);
        $this->assertStringContainsString('applyRequiredTokensFulltext', $controller);
        $this->assertStringContainsString('AddressNormalizer::parseQuery', $live);
        $this->assertStringContainsString('matchingStrategy', $search);
        $this->assertStringContainsString('headerSearchRequestId', $header);
        $this->assertStringContainsString('data.forEach(item =>', $header);
        $this->assertStringContainsString('searchRequestId', $map);
        $this->assertStringNotContainsString('keep at most 2 name tokens', $controller);
        $this->assertStringNotContainsString("name', 'like', '%'", $controller);
    }

    public function test_guest_sold_blur_and_delisted_status_contracts_remain(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));
        $controller = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php'));

        $this->assertStringContainsString('SOLD_STATUSES', $header);
        $this->assertStringContainsString('guestBlurClass', $header);
        $this->assertStringContainsString('skipTxFilter', $header);
        $this->assertStringContainsString('auth=', $controller);
        $this->assertStringContainsString('MlsStatus::delistedQueryValues', $controller);
    }
}
