<?php

namespace Theme\homzen\Supports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrebPropertyHelper
{
    /**
     * On web page renders, only use cached TREB data — never block on remote AMP calls.
     */
    public static function shouldSkipRemoteAmpFetch(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $request = request();

        if ($request->is('api/*')) {
            return false;
        }

        if ($request->boolean('warm_treb') || $request->boolean('refresh_treb')) {
            return false;
        }

        return true;
    }

    /**
     * Human-friendly relative "Listed" label instead of an exact date.
     * Returns e.g. "Listed today", "Listed this week", "Listed this month",
     * "Listed this year", or "Listed in 2023" for older listings.
     *
     * @param  \Carbon\Carbon|\DateTimeInterface|string|null  $date
     */
    public static function relativeListedLabel($date, string $prefix = 'Listed'): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            $listed = \Carbon\Carbon::parse($date);
        } catch (\Throwable) {
            return '';
        }

        // Guard against obviously invalid dates coming from legacy rows.
        $year = (int) $listed->format('Y');
        if ($year < 2000 || $year > ((int) date('Y') + 1)) {
            return '';
        }

        $now = \Carbon\Carbon::now();

        if ($listed->isSameDay($now)) {
            return trim($prefix . ' today');
        }

        // "This week" = within the last 7 days.
        if ($listed->greaterThanOrEqualTo($now->copy()->subDays(7)->startOfDay())) {
            return trim($prefix . ' this week');
        }

        if ($listed->isSameMonth($now)) {
            return trim($prefix . ' this month');
        }

        // Older than this month: show the month + year, e.g. "Listed June 2026".
        return trim($prefix . ' ' . $listed->format('F Y'));
    }

    /**
     * Allow a single AMP fetch on property detail pages (not map index).
     */
    public static function shouldAllowPropertyDetailAmpFetch(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        // Property pages should render from cached/local data only.
        // Live AMP calls here significantly hurt page speed and Time to Interactive.
        return false;
    }

    /**
     * Whether remote AMP calls are allowed for the current request.
     */
    public static function canFetchRemoteAmp(): bool
    {
        if (app()->bound('serik.live_treb_fallback') && app('serik.live_treb_fallback')) {
            return true;
        }

        return ! self::shouldSkipRemoteAmpFetch() || self::shouldAllowPropertyDetailAmpFetch();
    }

    public static function isPropertyDetailPageFetch(): bool
    {
        return self::shouldAllowPropertyDetailAmpFetch() && self::shouldSkipRemoteAmpFetch();
    }

    public static function streetSuffixWords(): array
    {
        return [
            'street', 'st', 'road', 'rd', 'avenue', 'ave', 'boulevard', 'blvd',
            'drive', 'dr', 'court', 'ct', 'lane', 'ln', 'way', 'crescent', 'cres',
            'circle', 'cir', 'place', 'pl', 'terrace', 'ter', 'trail', 'trl',
            'parkway', 'pkwy', 'highway', 'hwy', 'grove', 'gate', 'gardens',
            'square', 'sq', 'close', 'mews', 'row', 'path', 'passage',
        ];
    }

    public static function isStreetSuffixWord(?string $word): bool
    {
        return in_array(strtolower(trim((string) $word)), self::streetSuffixWords(), true);
    }

    public static function isUnitToken(?string $token): bool
    {
        $token = trim((string) $token);
        if ($token === '' || self::isStreetSuffixWord($token)) {
            return false;
        }

        // Condo/townhouse units: 805, C7, C-7, A4, PH1, TH12
        return (bool) preg_match('/^(?:[A-Za-z]{1,3}-?\d+[A-Za-z]?|\d{1,5}[A-Za-z]?|PH\d+|TH\d+)$/i', $token);
    }

    public static function formatUnitLabel(?string $unit): string
    {
        $unit = trim((string) $unit);
        if ($unit === '' || self::isStreetSuffixWord($unit)) {
            return '';
        }

        if (preg_match('/^([A-Za-z])-?(\d+)$/', $unit, $matches)) {
            return strtoupper($matches[1]) . '-' . $matches[2];
        }

        return $unit;
    }

    public static function formatDisplayAddress(array $record): string
    {
        $record = self::enrichRecordAddress($record);
        $unit = self::formatUnitLabel($record['UnitNumber'] ?? null);
        $street = self::formatStreetLine($record);

        if ($unit !== '' && $street !== '') {
            return $unit . ' - ' . $street;
        }

        if ($street !== '') {
            return $street;
        }

        $unparsed = (string) ($record['UnparsedAddress'] ?? '');

        return $unparsed !== ''
            ? self::guessDisplayAddressFromUnparsed($unparsed)
            : '';
    }

    public static function guessDisplayAddressFromUnparsed(string $unparsed): string
    {
        $unparsed = trim($unparsed);
        if ($unparsed === '') {
            return '';
        }

        // Fix previously corrupted names: "Avenue - 1035 Truman"
        if (preg_match('/^(' . implode('|', self::streetSuffixWords()) . ')\s*-\s*(\d+)\s+(.+)$/i', $unparsed, $matches)) {
            $suffix = ucwords(strtolower($matches[1]));
            $street = trim($matches[2] . ' ' . $matches[3] . ' ' . $suffix);

            return self::extractStreetLine($street);
        }

        $parsed = self::parseUnparsedAddress($unparsed);
        if (! empty($parsed['StreetNumber']) && ! empty($parsed['StreetName'])) {
            return self::formatDisplayAddress($parsed + ['UnparsedAddress' => $unparsed]);
        }

        return self::extractStreetLine($unparsed);
    }

    public static function formatStreetLine(array $record): string
    {
        $parts = array_filter([
            trim((string) ($record['StreetNumber'] ?? '')),
            trim((string) ($record['StreetDirPrefix'] ?? '')),
            trim((string) ($record['StreetName'] ?? '')),
            trim((string) ($record['StreetSuffix'] ?? '')),
            trim((string) ($record['StreetDirSuffix'] ?? '')),
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return self::extractStreetLine((string) ($record['UnparsedAddress'] ?? ''));
    }

    public static function extractStreetLine(string $address): string
    {
        $address = trim($address);
        if ($address === '') {
            return '';
        }

        $parts = explode(',', $address);

        return trim($parts[0] ?? $address);
    }

    public static function formatCityLabel(?string $city): string
    {
        $city = trim((string) $city);
        if ($city === '') {
            return '';
        }

        return trim((string) preg_replace('/\s+C\d+$/i', '', $city));
    }

    public static function formatRegionLabel(?string $region): string
    {
        $region = trim((string) $region);
        if ($region === '') {
            return '';
        }

        // "1003 - CP College Park" -> "College Park"
        $region = preg_replace('/^\d+\s*-\s*/', '', $region) ?? $region;
        $region = preg_replace('/^[A-Z]{1,3}\s+/', '', $region) ?? $region;

        return trim($region);
    }

    public static function formatLocationLine(array $record): string
    {
        $city = self::formatCityLabel($record['City'] ?? null);
        $region = self::formatRegionLabel($record['CityRegion'] ?? null);

        if ($city && $region) {
            return $city . ' - ' . $region;
        }

        return $city ?: $region;
    }

    public static function formatRelativeTime(?string $value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->diffForHumans();
        } catch (\Throwable) {
            return self::formatDateValue($value) ?? '-';
        }
    }

    public static function formatSizeLabel(array $record, ?array $local = null): string
    {
        if (! empty($record['SquareFootSource']) && preg_match('/(\d[\d,]*)/', (string) $record['SquareFootSource'], $matches)) {
            return number_format((int) str_replace(',', '', $matches[1])) . ' sq. ft.';
        }

        $range = $record['LivingAreaRange']
            ?? self::normalizeSquareStorage($record['square'] ?? null)
            ?? self::normalizeSquareStorage($local['square'] ?? null);

        if (is_string($range) && preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($range), $matches)) {
            return $matches[1] . '-' . $matches[2] . ' feet²';
        }

        $sqft = self::parseSquareFeet($range);

        if ($sqft > 0) {
            return number_format($sqft) . ' sq. ft.';
        }

        return is_string($range) && $range !== '' ? $range : '-';
    }

    public static function formatLotSize(array $record): string
    {
        $width = $record['LotWidth'] ?? $record['FrontageLength'] ?? null;
        $depth = $record['LotDepth'] ?? null;

        if ($width && $depth) {
            $w = (int) round((float) $width);
            $d = (int) round((float) $depth);

            return $w . ' x ' . $d . ' feet';
        }

        return '-';
    }

    public static function formatParkingLabel(array $record, ?array $local = null): string
    {
        $garage = $record['CoveredSpaces'] ?? ($local['CoveredSpaces'] ?? null);
        $total = $record['ParkingTotal'] ?? null;
        $type = $record['GarageType'] ?? null;
        $parts = [];

        if ($type && $garage) {
            $garageLabel = (int) $garage === 1 ? 'garage' : 'garages';
            $parts[] = trim($type . ' ' . $garage . ' ' . $garageLabel);
        } elseif ($garage) {
            $parts[] = (int) $garage . ((int) $garage === 1 ? ' garage' : ' garages');
        }

        if ($total) {
            $parts[] = 'total ' . (int) $total . ' parkings';
        }

        return $parts !== [] ? implode(', ', $parts) : '-';
    }

    public static function formatListValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = array_filter(array_map(fn ($item) => is_scalar($item) ? (string) $item : '', $value));

            return $value !== [] ? implode(', ', $value) : '-';
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }

    public static function daysOnMarketValue(array $record, ?array $local = null): string
    {
        if (isset($record['DaysOnMarket']) && is_numeric($record['DaysOnMarket'])) {
            return max(0, (int) $record['DaysOnMarket']) . ' days';
        }

        $listingDate = $record['ListingContractDate'] ?? ($local['listing_contract_date'] ?? $local['created_at'] ?? null);
        $status = strtolower((string) ($record['MlsStatus'] ?? ($local['MlsStatus'] ?? '')));
        $standard = strtolower((string) ($record['StandardStatus'] ?? ''));

        $isActive = $standard === 'active'
            || in_array($status, ['new', 'active', 'ext', 'price change', 'active under contract'], true);

        if (! $listingDate) {
            return '-';
        }

        try {
            $start = \Carbon\Carbon::parse($listingDate)->startOfDay();
            $end = $isActive
                ? now()->startOfDay()
                : \Carbon\Carbon::parse(
                    $record['PurchaseContractDate']
                    ?? $record['CloseDate']
                    ?? $record['TerminatedDate']
                    ?? $record['ExpirationDate']
                    ?? $record['UnavailableDate']
                    ?? now()
                )->startOfDay();

            return max(0, (int) $start->diffInDays($end)) . ' days';
        } catch (\Throwable) {
            return '-';
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public static function recordFromLocal(array $local, string $listingKey): array
    {
        $mainBeds = (int) ($local['number_bedroom'] ?? 0);
        $belowBeds = (int) ($local['BedroomsBelowGrade'] ?? 0);
        $basement = $local['Basement'] ?? null;

        if (is_string($basement) && str_starts_with(trim($basement), '[')) {
            $decoded = json_decode($basement, true);
            if (is_array($decoded)) {
                $basement = implode(', ', array_filter($decoded));
            }
        }

        $bedroomsAbove = $mainBeds;
        $bedroomsTotal = $mainBeds + $belowBeds;

        $floor = (int) ($local['number_floor'] ?? 0);
        $architecturalStyle = $local['ArchitecturalStyle'] ?? null;

        if (empty($architecturalStyle) && $floor > 0) {
            $architecturalStyle = $floor === 1 ? '1 Floor' : $floor . '-Storey';
        }

        $record = [
            'ListingKey' => $listingKey,
            'UnparsedAddress' => $local['name'] ?? null,
            'ListPrice' => $local['price'] ?? null,
            'LivingAreaRange' => $local['square'] ?? null,
            'MlsStatus' => $local['MlsStatus'] ?? null,
            'TransactionType' => $local['TransactionType'] ?? null,
            'PropertySubType' => $local['PropertySubType'] ?? null,
            'ListOfficeName' => $local['broker'] ?? null,
            'ListingContractDate' => $local['listing_contract_date'] ?? $local['created_at'] ?? null,
            'ModificationTimestamp' => $local['listing_modified_at'] ?? $local['updated_at'] ?? null,
            'OriginalEntryTimestamp' => $local['created_at'] ?? null,
            'ParkingSpaces' => $local['ParkingSpaces'] ?? null,
            'CoveredSpaces' => $local['CoveredSpaces'] ?? null,
            'ParkingTotal' => isset($local['CoveredSpaces'], $local['ParkingSpaces'])
                ? (int) $local['CoveredSpaces'] + (int) $local['ParkingSpaces']
                : ($local['ParkingSpaces'] ?? null),
            'BedroomsAboveGrade' => $bedroomsAbove > 0 ? $bedroomsAbove : null,
            'BedroomsBelowGrade' => $belowBeds > 0 ? $belowBeds : null,
            'BedroomsTotal' => $bedroomsTotal > 0 ? $bedroomsTotal : null,
            'BathroomsTotalInteger' => $local['number_bathroom'] ?? null,
            'Basement' => $basement,
            'ArchitecturalStyle' => $architecturalStyle,
            'StoriesTotal' => $floor > 0 ? $floor : null,
            'PublicRemarks' => ! empty($local['content'])
                ? strip_tags((string) $local['content'])
                : null,
        ];

        return array_filter(
            $record,
            fn ($value) => $value !== null && $value !== '' && $value !== []
        );
    }

    public static function parseUnparsedAddress(string $unparsed): array
    {
        $unparsed = trim($unparsed);
        if ($unparsed === '') {
            return [];
        }

        // Repair corrupted stored names: "Avenue - 1035 Truman"
        if (preg_match('/^(' . implode('|', self::streetSuffixWords()) . ')\s*-\s*(\d+)\s+(.+)$/i', $unparsed, $matches)) {
            $unparsed = trim($matches[2] . ' ' . $matches[3] . ' ' . $matches[1]);
        }

        $line = trim(explode(',', $unparsed)[0]);

        // Display format used by formatDisplayAddress: "101 - 123 Main Street"
        // (unit first). Parse this before the street-number-first path.
        if (preg_match(
            '/^([A-Za-z]{0,3}-?\d+[A-Za-z]?|PH\d+|TH\d+)\s*-\s*(\d+[A-Za-z]?)\s+(.+)$/i',
            $line,
            $unitFirst
        ) && self::isUnitToken($unitFirst[1])) {
            $rest = trim($unitFirst[3]);
            $tokens = preg_split('/\s+/', $rest) ?: [];
            $result = [
                'UnitNumber' => trim($unitFirst[1]),
                'StreetNumber' => trim($unitFirst[2]),
            ];

            if ($tokens !== [] && preg_match('/^(North|South|East|West|N|S|E|W)$/i', (string) end($tokens))) {
                $dir = array_pop($tokens);
                $result['StreetDirSuffix'] = strtoupper(substr((string) $dir, 0, 1));
            }

            if ($tokens !== [] && self::isStreetSuffixWord((string) end($tokens))) {
                $result['StreetSuffix'] = array_pop($tokens);
            }

            if ($tokens !== []) {
                $result['StreetName'] = implode(' ', $tokens);
            }

            return array_filter($result, fn ($value) => $value !== null && $value !== '');
        }

        $tokens = preg_split('/\s+/', $line) ?: [];
        if (count($tokens) < 2 || ! preg_match('/^\d+[A-Za-z]?$/', $tokens[0])) {
            return [];
        }

        $result = [
            'StreetNumber' => array_shift($tokens),
        ];

        // Optional unit at end (805, C7) — never street suffix words
        if ($tokens !== [] && self::isUnitToken(end($tokens))) {
            $result['UnitNumber'] = array_pop($tokens);
        }

        // Optional direction at end (W, East)
        if ($tokens !== [] && preg_match('/^(North|South|East|West|N|S|E|W)$/i', (string) end($tokens))) {
            $dir = array_pop($tokens);
            $result['StreetDirSuffix'] = strtoupper(substr((string) $dir, 0, 1));
        }

        // Optional street suffix (Avenue, Road)
        if ($tokens !== [] && self::isStreetSuffixWord((string) end($tokens))) {
            $result['StreetSuffix'] = array_pop($tokens);
        }

        if ($tokens !== []) {
            $result['StreetName'] = implode(' ', $tokens);
        }

        return array_filter($result, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    public static function parseLocationPartsFromAddress(string $unparsed): array
    {
        $unparsed = trim($unparsed);
        if ($unparsed === '') {
            return [];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $unparsed))));
        if (count($parts) < 2) {
            return [];
        }

        $result = [];
        $provincePostal = $parts[count($parts) - 1] ?? '';

        if (preg_match('/^(ON|BC|AB|QC|MB|SK|NS|NB|NL|PE|YT|NT|NU)\s+([A-Z]\d[A-Z]\s?\d[A-Z]\d)$/i', $provincePostal, $matches)) {
            $result['StateOrProvince'] = strtoupper($matches[1]);
            $result['PostalCode'] = strtoupper(preg_replace('/\s+/', ' ', trim($matches[2])));
        } elseif (preg_match('/^([A-Z]\d[A-Z]\s?\d[A-Z]\d)$/i', $provincePostal, $matches)) {
            $result['PostalCode'] = strtoupper(preg_replace('/\s+/', ' ', trim($matches[1])));
        }

        $cityPart = count($parts) >= 3 ? $parts[count($parts) - 2] : $parts[1];

        if (preg_match('/^(.+?)\s+(E\d+|W\d+|C\d+|N\d+|S\d+)$/i', $cityPart, $matches)) {
            $result['City'] = trim($matches[1]);
            $result['CityRegion'] = trim($matches[2]);
        } elseif ($cityPart !== '') {
            $result['City'] = $cityPart;
        }

        return array_filter($result, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public static function enrichRecordAddress(array $record): array
    {
        // Clear bad unit values like "Avenue"
        if (! empty($record['UnitNumber']) && self::isStreetSuffixWord((string) $record['UnitNumber'])) {
            if (empty($record['StreetSuffix'])) {
                $record['StreetSuffix'] = $record['UnitNumber'];
            }
            unset($record['UnitNumber']);
        }

        $source = (string) ($record['UnparsedAddress'] ?? $record['name'] ?? '');

        if (empty($record['StreetNumber']) || empty($record['StreetName']) || self::isStreetSuffixWord((string) ($record['StreetName'] ?? ''))) {
            $parsed = self::parseUnparsedAddress($source);

            foreach ($parsed as $key => $value) {
                if (empty($record[$key]) || ($key === 'UnitNumber' && self::isStreetSuffixWord((string) ($record[$key] ?? '')))) {
                    $record[$key] = $value;
                }
            }
        } elseif (empty($record['UnitNumber']) && $source !== '') {
            // Street already known but unit missing — still recover unit from
            // "101 - 123 Main St" / trailing unit tokens so Sold History stays unit-exact.
            $parsed = self::parseUnparsedAddress($source);
            if (! empty($parsed['UnitNumber']) && self::isUnitToken($parsed['UnitNumber'])) {
                $record['UnitNumber'] = $parsed['UnitNumber'];
            }
        }

        foreach (self::parseLocationPartsFromAddress($source) as $key => $value) {
            if ($key === 'CityRegion' && ! empty($record['CityRegion']) && ! preg_match('/^[A-Z]\d+$/i', (string) $record['CityRegion'])) {
                continue;
            }

            if (empty($record[$key])) {
                $record[$key] = $value;
            }
        }

        return $record;
    }

    public static function excludedCommercialSubTypes(): array
    {
        return [
            'Industrial',
            'Commercial Retail',
            'Office',
            'Store W Apt/Office',
            'Store w/Apt/Office',
            'Sale Of Business',
            'Business',
            'Land',
            'Farm',
            'Parking',
            'Locker',
            'Commercial',
        ];
    }

    public static function isCommercialSubType(?string $subtype): bool
    {
        $subtype = rtrim(trim((string) $subtype));
        if ($subtype === '') {
            return false;
        }

        return in_array($subtype, self::excludedCommercialSubTypes(), true);
    }

    /**
     * @param  array<int, string>  $conditions
     */
    public static function appendOntarioResidentialAmpConditions(array &$conditions): void
    {
        $conditions[] = "StateOrProvince eq 'ON'";

        foreach (self::excludedCommercialSubTypes() as $subtype) {
            $escaped = str_replace("'", "''", $subtype);
            $conditions[] = "PropertySubType ne '{$escaped}'";
        }
    }

    public static function refreshPropertyRecord(string $listingKey): ?array
    {
        $listingKey = strtoupper($listingKey);
        Cache::forget('treb_property_record_v5_' . $listingKey);
        Cache::forget('treb_property_record_raw_v1_' . $listingKey);
        Cache::forget('treb_listing_history_v5_' . $listingKey);
        Cache::forget('treb_price_changes_v4_' . $listingKey);
        Cache::forget('treb_price_changes_v5_' . $listingKey);
        Cache::forget('treb_map_popup_bundle_v1_' . $listingKey);
        Cache::forget('treb_map_popup_bundle_v2_' . $listingKey);
        Cache::forget('treb_property_rooms_v1_' . $listingKey);

        return self::fetchPropertyRecord($listingKey) ?: self::fetchPropertyRecordRaw($listingKey);
    }

    public static function ensureAmpRecord(string $listingKey, ?array $local = null): ?array
    {
        $propertyDetailFetch = self::isPropertyDetailPageFetch();

        if (! self::canFetchRemoteAmp()) {
            $record = self::fetchPropertyRecord($listingKey) ?: self::fetchPropertyRecordRaw($listingKey);

            if (! $record && $local) {
                $record = self::enrichRecordAddress(self::recordFromLocal($local, $listingKey));
            }

            return $record;
        }

        $record = self::fetchPropertyRecord($listingKey) ?: self::fetchPropertyRecordRaw($listingKey);

        if ($propertyDetailFetch) {
            if (! $record && $local) {
                $record = self::enrichRecordAddress(self::recordFromLocal($local, $listingKey));
            }

            return $record;
        }

        if ($record && empty($record['TaxAnnualAmount']) && empty($record['AssociationFee']) && empty($record['LotWidth'])) {
            $record = self::refreshPropertyRecord($listingKey) ?: $record;
        }

        if (! $record && $local) {
            $record = self::enrichRecordAddress(self::recordFromLocal($local, $listingKey));
        }

        // Listing may be missing from AMP Property feed; fill facts from same-address history.
        if ($record && (empty($record['TaxAnnualAmount']) || empty($record['LotWidth']) || empty($record['City']))) {
            $record = self::mergeAddressFallbackRecord($record, $local);
        }

        return $record;
    }

    /**
     * When current ListingKey is absent/incomplete in AMP, use the newest same-address
     * AMP record for static facts (tax, lot, basement) while keeping current listing identity.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>|null  $local
     * @return array<string, mixed>
     */
    public static function mergeAddressFallbackRecord(array $record, ?array $local = null): array
    {
        $record = self::enrichRecordAddress($record);
        $siblings = self::fetchUnitPropertyRecords($record);
        $usedBuildingFallback = false;

        if ($siblings === []) {
            // Building-wide siblings are only for static facts (tax/lot) — never
            // for unit identity. Sold history must not inherit another unit's UnitNumber.
            $siblings = self::fetchBuildingPropertyRecords($record);
            $usedBuildingFallback = $siblings !== [];
        }

        if ($siblings === []) {
            return $record;
        }

        // Prefer the most recent sibling that has tax/lot details.
        $fallback = null;
        foreach ($siblings as $sibling) {
            $full = self::fetchAmpPropertyForResync((string) ($sibling['ListingKey'] ?? ''))
                ?: self::fetchPropertyRecordRaw((string) ($sibling['ListingKey'] ?? ''));

            if (! $full) {
                $full = self::enrichRecordAddress($sibling);
            }

            if (! empty($full['TaxAnnualAmount']) || ! empty($full['LotWidth']) || ! empty($full['HeatType']) || ! empty($full['CityRegion'])) {
                $fallback = $full;
                break;
            }

            $fallback ??= $full;
        }

        if (! $fallback) {
            return $record;
        }

        // Static property facts from fallback address record.
        $staticFields = [
            'TaxAnnualAmount', 'TaxYear', 'ApproximateAge', 'YearBuilt', 'LotWidth', 'LotDepth',
            'FrontageLength', 'Basement',
            'ArchitecturalStyle', 'ConstructionMaterials', 'Cooling', 'HeatType', 'HeatSource',
            'WaterSource', 'Sewer', 'Zoning', 'CrossStreet', 'DirectionFaces', 'City', 'CityRegion',
            'StateOrProvince', 'PostalCode', 'StreetNumber', 'StreetName', 'StreetSuffix',
            'StreetDirPrefix', 'StreetDirSuffix', 'UnitNumber', 'UnparsedAddress',
            'SourceSystemName', 'OriginatingSystemName', 'FireplaceYN', 'DenFamilyroomYN',
            'WashroomsType1', 'WashroomsType1Pcs', 'WashroomsType2',
            'WashroomsType2Pcs', 'WashroomsType3', 'WashroomsType3Pcs', 'WashroomsType4',
            'WashroomsType4Pcs', 'WashroomsType5', 'WashroomsType5Pcs', 'AssociationFee',
            'AssociationFeeIncludes', 'Locker', 'SquareFootSource', 'PetsAllowed', 'GarageType',
            'ParkingTotal', 'ParkingSpaces', 'CoveredSpaces', 'KitchensTotal', 'RoomsTotal',
            'ExteriorFeatures', 'OtherStructures', 'Driveway', 'BuildingAreaTotal',
        ];

        // Never copy unit/identity fields from a different apartment in the building.
        if ($usedBuildingFallback) {
            $staticFields = array_values(array_diff($staticFields, [
                'UnitNumber',
                'UnparsedAddress',
            ]));
        }

        $merged = $record;
        foreach ($staticFields as $field) {
            $currentEmpty = ! array_key_exists($field, $merged) || $merged[$field] === null || $merged[$field] === '' || $merged[$field] === [];
            if ($currentEmpty && array_key_exists($field, $fallback)) {
                $merged[$field] = $fallback[$field];
            }
        }

        if ($local) {
            if (! empty($local['square'])) {
                $merged['LivingAreaRange'] = $local['square'];
            }
            if (! empty($local['PropertySubType'])) {
                $merged['PropertySubType'] = $local['PropertySubType'];
            }
            if (! empty($local['price']) && empty($merged['ListPrice'])) {
                $merged['ListPrice'] = $local['price'];
            }
            if (! empty($local['MlsStatus']) && empty($merged['MlsStatus'])) {
                $merged['MlsStatus'] = $local['MlsStatus'];
            }
            if (! empty($local['TransactionType']) && empty($merged['TransactionType'])) {
                $merged['TransactionType'] = $local['TransactionType'];
            }
            if (! empty($local['broker'])) {
                $merged['ListOfficeName'] = $local['broker'];
            }
            if (! empty($local['created_at'])) {
                $merged['ListingContractDate'] = $local['created_at'];
            }
            if (! empty($local['CoveredSpaces'])) {
                $merged['CoveredSpaces'] = $local['CoveredSpaces'];
            }
            if (! empty($local['ParkingSpaces'])) {
                $merged['ParkingSpaces'] = $local['ParkingSpaces'];
            }
        }

        // Never inherit another listing's timeline/status fields.
        unset(
            $merged['PurchaseContractDate'],
            $merged['CloseDate'],
            $merged['TerminatedDate'],
            $merged['ExpirationDate'],
            $merged['UnavailableDate'],
            $merged['DaysOnMarket'],
            $merged['PriorMlsStatus']
        );

        $merged['ListingKey'] = $record['ListingKey'] ?? ($local['external_id'] ?? $merged['ListingKey'] ?? null);

        $currentRegion = trim((string) ($merged['CityRegion'] ?? ''));
        $fallbackRegion = trim((string) ($fallback['CityRegion'] ?? ''));

        if ($fallbackRegion !== '' && ($currentRegion === '' || preg_match('/^[A-Z]\d+$/i', $currentRegion))) {
            $merged['CityRegion'] = $fallback['CityRegion'];
        }

        return self::enrichRecordAddress($merged);
    }

    public static function normalizeBuildingAddress(string $address): string
    {
        $address = trim($address);

        $address = preg_replace('/^#?\d+[a-zA-Z]?-\s*/', '', $address);
        $address = preg_replace('/,?\s*(unit|apt|apartment|suite|ste)\s*#?\s*[\w-]+/i', '', $address);
        $address = preg_replace('/\s+#\s*[\w-]+$/i', '', $address);

        return strtolower(trim($address));
    }

    /**
     * Group MLS listings by building address (HouseSigma-style).
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    public static function groupListingsByBuilding(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $groups = collect($records)->groupBy(function (array $item) {
            $address = $item['UnparsedAddress'] ?? $item['name'] ?? '';

            return self::normalizeBuildingAddress((string) $address);
        });

        return $groups->map(function (Collection $units, string $buildingKey) {
            $units = $units->values();
            $primary = $units->first();

            if ($units->count() === 1) {
                return array_merge($primary, [
                    'grouped' => false,
                    'unit_count' => 1,
                    'building_address' => $primary['UnparsedAddress'] ?? $primary['name'] ?? '',
                    'units' => $units->all(),
                ]);
            }

            $buildingAddress = self::formatBuildingLabel($buildingKey, $units);

            return array_merge($primary, [
                'grouped' => true,
                'unit_count' => $units->count(),
                'building_address' => $buildingAddress,
                'UnparsedAddress' => $buildingAddress . ' (' . $units->count() . ' units)',
                'units' => $units->sortBy(fn ($u) => $u['UnparsedAddress'] ?? '')->values()->all(),
            ]);
        })->values()->all();
    }

    protected static function formatBuildingLabel(string $buildingKey, Collection $units): string
    {
        $sample = (string) ($units->first()['UnparsedAddress'] ?? $units->first()['name'] ?? $buildingKey);

        $normalized = self::normalizeBuildingAddress($sample);

        if ($normalized !== '' && str_contains(strtolower($sample), $normalized)) {
            // Return the sample with unit portion stripped for display
            $stripped = preg_replace('/^#?\d+[a-zA-Z]?-\s*/', '', $sample);
            $stripped = preg_replace('/,?\s*(unit|apt|apartment|suite|ste)\s*#?\s*[\w-]+/i', '', $stripped);
            $stripped = preg_replace('/\s+#\s*[\w-]+$/i', '', $stripped);

            return trim($stripped) ?: ucwords($buildingKey);
        }

        return ucwords($buildingKey);
    }

    /**
     * @return array<int, string>
     */
    public static function getPropertyImages(?string $listingKey, ?string $imageVal = null, bool $allowRemote = true): array
    {
        if (! empty($listingKey)) {
            $normalizedKey = strtoupper(trim($listingKey));
            $cacheKeys = [
                'treb_images_v3_' . $normalizedKey,
                'treb_property_images_' . $normalizedKey,
                'treb_property_images_' . $listingKey,
            ];

            foreach ($cacheKeys as $cacheKey) {
                $cached = Cache::get($cacheKey);
                if (is_array($cached) && count($cached) > 0) {
                    return $cached;
                }
            }

            if (! $allowRemote || ! self::canFetchRemoteAmp()) {
                if (! empty($imageVal) && str_starts_with($imageVal, 'http')) {
                    return [$imageVal];
                }

                return [];
            }

            // Single lightweight AMP Media call — always fetch when cache is cold.
            $images = self::fetchMediaImagesFromAmp($normalizedKey);

            if ($images === [] && $normalizedKey !== $listingKey) {
                $images = self::fetchMediaImagesFromAmp($listingKey);
            }

            if ($images !== []) {
                Cache::put('treb_images_v3_' . $normalizedKey, $images, 3600);
                Cache::put('treb_property_images_' . $normalizedKey, $images, 86400);

                return $images;
            }
        }

        if (! empty($imageVal) && str_starts_with($imageVal, 'http')) {
            return [$imageVal];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    protected static function fetchMediaImagesFromAmp(string $listingKey): array
    {
        $url = 'https://query.ampre.ca/odata/Media?'
            . '%24filter=ResourceRecordKey%20eq%20%27' . $listingKey . '%27'
            . '&%24top=50'
            . '&%24select=MediaURL,ImageSizeDescription,Order'
            . '&%24orderby=Order';

        $payload = self::ampGet($url);
        $items = $payload['value'] ?? [];

        if (empty($items)) {
            return [];
        }

        $sizePriority = [
            'LargestNoWatermark' => 0,
            'Large' => 1,
            'Medium' => 2,
            'Thumbnail' => 3,
        ];

        $byOrder = [];

        foreach ($items as $media) {
            if (empty($media['MediaURL'])) {
                continue;
            }

            $order = (int) ($media['Order'] ?? count($byOrder));
            $size = (string) ($media['ImageSizeDescription'] ?? '');
            $rank = $sizePriority[$size] ?? 4;

            if (! isset($byOrder[$order]) || $rank < $byOrder[$order]['rank']) {
                $byOrder[$order] = [
                    'url' => $media['MediaURL'],
                    'rank' => $rank,
                ];
            }
        }

        ksort($byOrder);

        return array_values(array_unique(array_column(array_values($byOrder), 'url')));
    }

    public static function getCoverImage(?string $listingKey, ?string $imageVal = null, ?string $localImage = null): string
    {
        $images = self::getPropertyImages($listingKey, $imageVal);

        if (! empty($images[0])) {
            return $images[0];
        }

        if (! empty($imageVal) && str_starts_with($imageVal, 'http')) {
            return $imageVal;
        }

        if (! empty($localImage)) {
            return $localImage;
        }

        return '';
    }

    public static function isSoldHistoryMlsStatus(?string $mlsStatus): bool
    {
        return in_array($mlsStatus, [
            'Sold',
            'Sold Conditional',
            'Sold Conditional Escape',
            'Leased',
            'Leased Conditional',
        ], true);
    }

    /**
     * Fast SEO URL for property cards on /properties (no per-card AMP/address parsing).
     */
    public static function listingSeoUrl(\Botble\RealEstate\Models\Property $property): string
    {
        static $subtypeMap = [
            'Detached' => 'detached-houses',
            'Detached Condo' => 'detached-houses',
            'Semi-Detached' => 'semi-detached-houses',
            'Link' => 'link-houses',
            'Att/Row/Townhouse' => 'townhouses',
            'Condo Townhouse' => 'townhouses',
            'Condo Apartment' => 'condos',
            'Co-op Apartment' => 'condos',
            'Co-Ownership Apartment' => 'condos',
            'Leasehold Condo' => 'condos',
            'Common Element Condo' => 'condos',
            'Duplex' => 'duplex',
            'Fourplex' => 'fourplex',
            'Multiplex' => 'multiplex',
            'Other' => 'houses',
        ];

        static $citySlugs = [
            'brampton', 'mississauga', 'vaughan', 'milton', 'oakville', 'niagarafalls', 'toronto',
            'kitchener', 'waterloo', 'cambridge', 'hamilton', 'ottawa', 'london', 'markham',
            'windsor', 'richmondhill', 'burlington', 'oshawa', 'barrie', 'guelph', 'kingston',
            'whitby', 'ajax', 'peterborough', 'sarnia', 'thunderbay', 'sudbury', 'northbay',
            'orillia', 'brantford', 'stcatharines', 'welland', 'pickering', 'clarington',
            'newmarket', 'aurora', 'orangeville', 'midland', 'collingwood', 'timmins', 'kenora',
            'elliotlake', 'brockville', 'cornwall', 'stratford', 'woodstock', 'leamington',
            'chatham', 'belleville', 'pembroke',
        ];

        $slug = strtolower((string) ($property->slug ?? ''));
        $city = 'ontario';

        foreach ($citySlugs as $citySlug) {
            if (str_contains($slug, '-' . $citySlug . '-')) {
                $city = $citySlug;
                break;
            }
        }

        $subtype = $subtypeMap[(string) $property->PropertySubType] ?? 'houses';

        return url("on/{$subtype}-for-sale-in-{$city}/map/{$property->slug}");
    }

    public static function formatListingActiveMonthYear(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($value);
            $year = (int) $date->format('Y');

            if ($year < 1990 || $year > ((int) date('Y') + 1)) {
                return null;
            }

            return $date->format('M Y');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{address: string, location: string, listed_ago: string, listed_active: ?string, beds: string, url: string}
     */
    public static function listingCardViewModel(\Botble\RealEstate\Models\Property $property): array
    {
        $name = (string) $property->name;
        $parts = array_map('trim', explode(',', $name, 3));
        $address = $parts[0] ?? $name;
        $location = '';

        if (isset($parts[1])) {
            $location = trim(preg_replace('/\s+(ON|Ontario)(\s+[A-Z]\d[A-Z]\s?\d[A-Z]\d)?$/i', '', $parts[1]));
        }

        if ($location === '' && ! empty($property->short_address)) {
            $location = (string) $property->short_address;
        }

        $bedMain = (int) ($property->number_bedroom ?? 0);
        $bedBelow = (int) ($property->BedroomsBelowGrade ?? 0);
        $beds = $bedMain > 0 ? $bedMain . ($bedBelow > 0 ? '+' . $bedBelow : '') : '';

        $listedAt = $property->listing_contract_date ?? $property->created_at;
        $listedActive = self::formatListingActiveMonthYear($listedAt);

        return [
            'address' => $address,
            'location' => $location,
            'listed_ago' => self::formatRelativeTime($listedAt ? (string) $listedAt : null),
            'listed_active' => $listedActive,
            'beds' => $beds,
            'url' => self::listingSeoUrl($property),
        ];
    }

    /**
     * Normalize condo unit tokens for strict history matching (805, #805, Unit 805).
     */
    public static function normalizeUnitToken(?string $unit): string
    {
        $unit = trim((string) $unit);
        $unit = ltrim($unit, '#');
        $unit = preg_replace('/^(unit|suite|apt|#)\s*/i', '', $unit) ?? $unit;

        return trim($unit);
    }

    /**
     * First scalar from AMP array / JSON-encoded array fields in detail views.
     */
    public static function ampFieldFirst(array $record, string $key): ?string
    {
        if (! array_key_exists($key, $record)) {
            return null;
        }

        $value = $record[$key];

        if (is_array($value)) {
            $first = $value[0] ?? null;

            return $first === null || $first === '' ? null : (string) $first;
        }

        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && isset($decoded[0]) && $decoded[0] !== '') {
                return (string) $decoded[0];
            }
        }

        $scalar = trim((string) $value);

        return $scalar === '' ? null : $scalar;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Botble\RealEstate\QueryBuilders\PropertyBuilder  $query
     */
    public static function applyResidentialScope($query)
    {
        $excluded = self::excludedCommercialSubTypes();

        return $query->where(function ($q) use ($excluded): void {
            $q->whereNull('PropertySubType')
                ->orWhereNotIn('PropertySubType', $excluded);
        });
    }

    public static function soldStatusLabel(?string $mlsStatus): string
    {
        return match (true) {
            $mlsStatus === 'Leased' => __('Leased'),
            str_contains((string) $mlsStatus, 'Sold') => __('Sold'),
            default => __('Sold'),
        };
    }

    public static function soldStatusBadgeHtml(?string $mlsStatus): string
    {
        $label = self::soldStatusLabel($mlsStatus);

        return '<span class="flag-tag primary status-sold">' . e($label) . '</span>';
    }

    public static function eventLabel(?string $mlsStatus, ?string $transactionType = null): string
    {
        if ($mlsStatus === 'New' && $transactionType) {
            return $transactionType;
        }

        if ($mlsStatus) {
            return $mlsStatus;
        }

        return $transactionType ?: 'Active';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function fetchPropertyRecord(string $listingKey): ?array
    {
        if ($listingKey === '') {
            return null;
        }

        $listingKey = strtoupper($listingKey);
        $cacheKey = 'treb_property_record_v5_' . $listingKey;

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        if (! self::canFetchRemoteAmp()) {
            return null;
        }

        return Cache::remember($cacheKey, 3600, function () use ($listingKey) {
            $select = self::propertyDetailSelectFields();
            $url = 'https://query.ampre.ca/odata/Property?'
                . "\$filter=ListingKey eq '{$listingKey}'"
                . "&\$top=1"
                . "&\$select={$select}";

            $payload = self::ampGet($url);
            $record = $payload['value'][0] ?? null;

            if ($record) {
                return self::enrichRecordAddress($record);
            }

            return self::fetchPropertyRecordRaw($listingKey);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function fetchPropertyRecordRaw(string $listingKey): ?array
    {
        $listingKey = strtoupper($listingKey);
        $cacheKey = 'treb_property_record_raw_v1_' . $listingKey;

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        if (! self::canFetchRemoteAmp()) {
            return null;
        }

        return Cache::remember($cacheKey, 3600, function () use ($listingKey) {
            $url = 'https://query.ampre.ca/odata/Property?'
                . "\$filter=ListingKey eq '{$listingKey}'"
                . '&$top=1';

            $payload = self::ampGet($url);
            $record = $payload['value'][0] ?? null;

            return $record ? self::enrichRecordAddress($record) : null;
        });
    }

    public static function propertyDetailSelectFields(): string
    {
        return implode(',', [
            'ListingKey', 'UnparsedAddress', 'UnitNumber', 'StreetNumber', 'StreetName', 'StreetSuffix',
            'StreetDirPrefix', 'StreetDirSuffix', 'City', 'CityRegion', 'StateOrProvince', 'PostalCode',
            'PropertySubType', 'ListPrice', 'ClosePrice', 'OriginalListPrice', 'PreviousListPrice',
            'MlsStatus', 'TransactionType', 'StandardStatus', 'PriorMlsStatus', 'ListingContractDate',
            'PurchaseContractDate', 'CloseDate', 'TerminatedDate', 'ExpirationDate', 'UnavailableDate',
            'ModificationTimestamp', 'OriginalEntryTimestamp', 'PriceChangeTimestamp',
            'TaxAnnualAmount', 'TaxYear', 'AssociationFee', 'AssociationFeeIncludes', 'YearBuilt',
            'ApproximateAge', 'LivingAreaRange', 'SquareFootSource', 'BuildingAreaTotal',
            'ParkingTotal', 'ParkingSpaces', 'GarageType', 'ListOfficeName', 'OriginatingSystemName',
            'SourceSystemName', 'PublicRemarks', 'BedroomsTotal', 'BedroomsAboveGrade', 'BathroomsTotalInteger', 'Locker',
            'BedroomsBelowGrade', 'Basement', 'DaysOnMarket', 'CumulativeDaysOnMarket', 'ArchitecturalStyle',
            'ConstructionMaterials', 'Cooling', 'HeatType', 'HeatSource', 'PetsAllowed', 'CrossStreet',
            'CoveredSpaces', 'LotWidth', 'LotDepth', 'FrontageLength', 'DirectionFaces', 'RoomsTotal',
            'KitchensTotal', 'FireplaceYN', 'WaterSource', 'Sewer', 'Zoning', 'DenFamilyroomYN', 'PropertyType',
            'OtherStructures', 'ExteriorFeatures',
            'WashroomsType1', 'WashroomsType1Pcs', 'WashroomsType1Level',
            'WashroomsType2', 'WashroomsType2Pcs', 'WashroomsType2Level',
            'WashroomsType3', 'WashroomsType3Pcs', 'WashroomsType3Level',
            'WashroomsType4', 'WashroomsType4Pcs', 'WashroomsType4Level',
            'WashroomsType5', 'WashroomsType5Pcs', 'WashroomsType5Level',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $local
     * @return array<int, array<string, mixed>>
     */
    public static function fetchListingHistory(string $listingKey, ?array $local = null): array
    {
        $cacheKey = 'treb_listing_history_v5_' . strtoupper($listingKey);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        if (! self::canFetchRemoteAmp() || self::isPropertyDetailPageFetch()) {
            $record = self::ensureAmpRecord($listingKey, $local);

            if (! $record) {
                return [];
            }

            $record = self::enrichRecordAddress($record);
            $rows = self::fetchLocalDbHistory($record, $listingKey);

            $hasCurrent = false;
            foreach ($rows as $row) {
                if (strcasecmp((string) ($row['listing_id'] ?? ''), strtoupper($listingKey)) === 0) {
                    $hasCurrent = true;
                    break;
                }
            }

            if (! $hasCurrent) {
                array_unshift($rows, self::propertyToHistoryRow($record));
            }

            if ($rows === []) {
                $rows[] = self::propertyToHistoryRow($record);
            }

            $rows = self::filterListingHistoryForViewer(
                $rows,
                auth('account')->check() || auth()->check()
            );

            if ($rows !== []) {
                Cache::put($cacheKey, $rows, 1800);
            }

            return $rows;
        }

        return Cache::remember($cacheKey, 1800, function () use ($listingKey, $local) {
            $record = self::ensureAmpRecord($listingKey, $local);

            if (! $record) {
                return [];
            }

            $record = self::enrichRecordAddress($record);
            $rows = self::fetchUnitListingRows($record);

            // Prefer AMP history; only use local DB when AMP returns nothing
            if ($rows === []) {
                $rows = self::fetchLocalDbHistory($record, $listingKey);
            }

            // Current listing may be missing from AMP Property feed — still show it.
            $hasCurrent = false;
            foreach ($rows as $row) {
                if (strcasecmp((string) ($row['listing_id'] ?? ''), $listingKey) === 0) {
                    $hasCurrent = true;
                    break;
                }
            }
            if (! $hasCurrent) {
                array_unshift($rows, self::propertyToHistoryRow($record));
            }

            if ($rows === []) {
                $rows[] = self::propertyToHistoryRow($record);
            }

            $unique = [];
            foreach ($rows as $row) {
                $key = strtoupper((string) ($row['listing_id'] ?? '')) . '|' . ($row['date_start'] ?? '') . '|' . ($row['price'] ?? '');
                $unique[$key] = $row;
            }

            usort($unique, function ($a, $b) {
                return strcmp($b['date_start'] ?? '', $a['date_start'] ?? '');
            });

            $rows = array_values($unique);

            return self::filterListingHistoryForViewer(
                $rows,
                auth('account')->check() || auth()->check()
            );
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected static function dedupeHistoryRows(array $rows): array
    {
        $byListing = [];
        $anonymous = [];

        foreach ($rows as $row) {
            $listingId = strtoupper(trim((string) ($row['listing_id'] ?? '')));

            if ($listingId === '') {
                $anonymous[] = $row;
                continue;
            }

            if (! isset($byListing[$listingId])) {
                $byListing[$listingId] = $row;
                continue;
            }

            $byListing[$listingId] = self::mergeHistoryRows($byListing[$listingId], $row);
        }

        return array_merge(array_values($byListing), $anonymous);
    }

    /**
     * @param  array<string, mixed>  $primary
     * @param  array<string, mixed>  $secondary
     * @return array<string, mixed>
     */
    protected static function mergeHistoryRows(array $primary, array $secondary): array
    {
        $score = static function (array $row): int {
            $points = 0;

            if (! empty($row['date_start'])) {
                $points += 4;
            }

            if (! empty($row['price'])) {
                $points += 2;
            }

            if (! empty($row['date_end'])) {
                $points += 1;
            }

            return $points;
        };

        $winner = $score($primary) >= $score($secondary) ? $primary : $secondary;
        $loser = $winner === $primary ? $secondary : $primary;

        return array_merge($loser, $winner);
    }

    public static function isProtectedHistoryEvent(?string $event): bool
    {
        $event = strtolower(trim((string) $event));

        foreach (['sold', 'leased', 'expired', 'terminated', 'suspended'] as $needle) {
            if ($event !== '' && str_contains($event, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected static function historyPlaceholderLabel(?string $event): string
    {
        $event = strtolower((string) $event);

        if (str_contains($event, 'leas') || str_contains($event, 'rent')) {
            return 'Leased';
        }

        if (str_contains($event, 'terminated')) {
            return 'Terminated';
        }

        if (str_contains($event, 'expired')) {
            return 'Expired';
        }

        return 'Sold';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function filterListingHistoryForViewer(array $rows, bool $authenticated): array
    {
        if ($authenticated || $rows === []) {
            return $rows;
        }

        $visible = [];
        $hidden = [];

        foreach ($rows as $row) {
            if (! empty($row['locked'])) {
                continue;
            }

            if (self::isProtectedHistoryEvent($row['event'] ?? null)) {
                $hidden[] = $row;
                continue;
            }

            $visible[] = $row;
        }

        foreach ($hidden as $row) {
            $visible[] = [
                'locked' => true,
                'date_start' => null,
                'date_end' => null,
                'price' => null,
                'event' => '(Sign in required) ' . self::historyPlaceholderLabel($row['event'] ?? null),
                'listing_id' => null,
            ];
        }

        usort($visible, function (array $a, array $b): int {
            $aLocked = ! empty($a['locked']);
            $bLocked = ! empty($b['locked']);

            if ($aLocked !== $bLocked) {
                return $aLocked <=> $bLocked;
            }

            return strcmp($b['date_start'] ?? '', $a['date_start'] ?? '');
        });

        return $visible;
    }

    public static function hasDisplayValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === '-') {
            return false;
        }

        if ($value === 'N/A' || $value === 'N' || $value === 'No') {
            return false;
        }

        return true;
    }

    public static function hasDetailFieldValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === '-' || $value === []) {
            return false;
        }

        return true;
    }

    public static function formatYesNo(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['y', 'yes', '1', 'true'], true)) {
            return 'Yes';
        }

        if (in_array($normalized, ['n', 'no', '0', 'false'], true)) {
            return 'No';
        }

        return (string) $value;
    }

    public static function formatBedroomTotal(array $record, ?array $local = null): string
    {
        $total = (int) ($record['BedroomsTotal'] ?? 0);

        if ($total <= 0) {
            $above = (int) ($record['BedroomsAboveGrade'] ?? ($local['number_bedroom'] ?? 0));
            $below = (int) ($record['BedroomsBelowGrade'] ?? ($local['BedroomsBelowGrade'] ?? 0));
            $total = $above + $below;
        }

        return $total > 0 ? (string) $total : '-';
    }

    /**
     * @return array<int, string>
     */
    public static function buildBathroomDetailsList(array $record): array
    {
        $details = [];

        for ($i = 1; $i <= 5; $i++) {
            $count = $record['WashroomsType' . $i] ?? null;
            $pcs = $record['WashroomsType' . $i . 'Pcs'] ?? null;
            $level = $record['WashroomsType' . $i . 'Level'] ?? null;

            if (! $count && ! $pcs) {
                continue;
            }

            $line = trim(($count ?: '1') . ($pcs ? ', ' . $pcs . 'pc' : ''));

            if ($level) {
                $line .= ' ' . $level . ' floor';
            }

            $details[] = $line;
        }

        return $details;
    }

    public static function propertyDaysOnMarketValue(array $record, ?array $local = null): string
    {
        if (isset($record['CumulativeDaysOnMarket']) && is_numeric($record['CumulativeDaysOnMarket'])) {
            return max(0, (int) $record['CumulativeDaysOnMarket']) . ' days';
        }

        $listingDate = $record['OriginalEntryTimestamp'] ?? $record['ListingContractDate'] ?? ($local['created_at'] ?? null);

        if (! $listingDate) {
            return '-';
        }

        try {
            $start = \Carbon\Carbon::parse($listingDate)->startOfDay();

            return max(0, (int) $start->diffInDays(now()->startOfDay())) . ' days';
        } catch (\Throwable) {
            return '-';
        }
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchUnitPropertyRecords(array $record): array
    {
        $record = self::enrichRecordAddress($record);
        $filters = [];

        $streetNumber = trim((string) ($record['StreetNumber'] ?? ''));
        $unitNumber = trim((string) ($record['UnitNumber'] ?? ''));
        if (! self::isUnitToken($unitNumber)) {
            $unitNumber = '';
        }

        if ($streetNumber !== '') {
            $filters[] = "StreetNumber eq '" . str_replace("'", "''", $streetNumber) . "'";
        }

        if (! empty($record['StreetName']) && ! self::isStreetSuffixWord((string) $record['StreetName'])) {
            $filters[] = "StreetName eq '" . str_replace("'", "''", (string) $record['StreetName']) . "'";
        }

        // Condo/townhouse: exact unit. Freehold: no unit filter (filter client-side).
        if ($unitNumber !== '') {
            $filters[] = "UnitNumber eq '" . str_replace("'", "''", $unitNumber) . "'";
        }

        if (count($filters) < 2) {
            $line = self::formatDisplayAddress($record);
            if ($line !== '') {
                $filters = ["contains(UnparsedAddress,'" . str_replace("'", "''", $line) . "')"];
            }
        }

        if ($filters === []) {
            return [];
        }

        $filter = implode(' and ', $filters);

        $select = implode(',', [
            'ListingKey', 'ListPrice', 'OriginalListPrice', 'PreviousListPrice', 'PriceChangeTimestamp',
            'MlsStatus', 'TransactionType', 'PriorMlsStatus', 'ListingContractDate',
            'PurchaseContractDate', 'CloseDate', 'TerminatedDate', 'ExpirationDate', 'UnavailableDate',
            'ModificationTimestamp', 'UnparsedAddress', 'UnitNumber', 'StreetNumber', 'StreetName',
            'StreetSuffix', 'StreetDirPrefix', 'StreetDirSuffix',
        ]);

        $url = 'https://query.ampre.ca/odata/Property?'
            . "\$filter={$filter}"
            . '&$orderby=ListingContractDate desc'
            . '&$top=50'
            . "&\$select={$select}";

        $valuesByKey = [];
        foreach (['historical', 'live'] as $profile) {
            $payload = self::ampGetFresh($url, 12, 2, $profile);
            foreach ($payload['value'] ?? [] as $item) {
                $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));
                if ($key !== '') {
                    $valuesByKey[$key] = $item;
                }
            }
        }

        if ($valuesByKey === []) {
            $rawUrl = 'https://query.ampre.ca/odata/Property?'
                . "\$filter={$filter}"
                . '&$orderby=ListingContractDate desc'
                . '&$top=50';
            foreach (['historical', 'live'] as $profile) {
                $rawPayload = self::ampGetFresh($rawUrl, 12, 2, $profile);
                foreach ($rawPayload['value'] ?? [] as $item) {
                    $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));
                    if ($key !== '') {
                        $valuesByKey[$key] = $item;
                    }
                }
            }
        }

        $values = array_values($valuesByKey);

        return array_values(array_filter($values, function (array $item) use ($unitNumber, $streetNumber, $record) {
            $itemNumber = trim((string) ($item['StreetNumber'] ?? ''));
            if ($streetNumber !== '' && $itemNumber !== '' && $itemNumber !== $streetNumber) {
                return false;
            }

            $itemName = strtolower(trim((string) ($item['StreetName'] ?? '')));
            $streetName = strtolower(trim((string) ($record['StreetName'] ?? '')));
            if ($streetName !== '' && $itemName !== '' && $itemName !== $streetName) {
                return false;
            }

            $itemUnit = trim((string) ($item['UnitNumber'] ?? ''));
            if ($unitNumber !== '') {
                return strcasecmp(self::normalizeUnitToken($itemUnit), self::normalizeUnitToken($unitNumber)) === 0;
            }

            // Freehold: only listings without a real unit number
            return $itemUnit === '' || ! self::isUnitToken($itemUnit);
        }));
    }

    /**
     * Same street/building AMP records without restricting to a single unit.
     *
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchBuildingPropertyRecords(array $record): array
    {
        $record = self::enrichRecordAddress($record);
        $streetNumber = trim((string) ($record['StreetNumber'] ?? ''));
        $streetName = trim((string) ($record['StreetName'] ?? ''));

        if ($streetNumber === '' || $streetName === '' || self::isStreetSuffixWord($streetName)) {
            return [];
        }

        $filter = "StreetNumber eq '" . str_replace("'", "''", $streetNumber) . "'"
            . " and StreetName eq '" . str_replace("'", "''", $streetName) . "'";

        $url = 'https://query.ampre.ca/odata/Property?'
            . '$filter=' . rawurlencode($filter)
            . '&$orderby=ListingContractDate desc'
            . '&$top=20'
            . '&$select=' . rawurlencode(self::propertyDetailSelectFields());

        $payload = self::ampGetFresh($url, 12, 2);
        $values = $payload['value'] ?? [];

        if ($values === []) {
            return [];
        }

        return array_values(array_filter($values, function (array $item) use ($streetNumber, $streetName) {
            return trim((string) ($item['StreetNumber'] ?? '')) === $streetNumber
                && strcasecmp(trim((string) ($item['StreetName'] ?? '')), $streetName) === 0;
        }));
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchLocalDbHistory(array $record, string $listingKey): array
    {
        $record = self::enrichRecordAddress($record);
        $streetNumber = trim((string) ($record['StreetNumber'] ?? ''));
        $streetName = trim((string) ($record['StreetName'] ?? ''));
        $unitNumber = self::isUnitToken($record['UnitNumber'] ?? null) ? trim((string) $record['UnitNumber']) : '';

        if ($streetNumber === '' || $streetName === '') {
            return [];
        }

        // Brief cache: same address+unit is hit by detail + map popup.
        $cacheKey = 'serik_addr_hist_v6_' . md5(strtolower($streetNumber . '|' . $streetName . '|' . $unitNumber));

        return Cache::remember($cacheKey, 300, function () use ($streetNumber, $streetName, $unitNumber) {
            $filterRows = function ($candidates) use ($streetNumber, $streetName, $unitNumber) {
                $rows = [];
                foreach ($candidates as $item) {
                    $parsed = self::enrichRecordAddress([
                        'UnparsedAddress' => $item->name,
                        'name' => $item->name,
                    ]);

                    $itemNumber = trim((string) ($parsed['StreetNumber'] ?? ''));
                    $itemName = strtolower(trim((string) ($parsed['StreetName'] ?? '')));
                    $itemUnit = trim((string) ($parsed['UnitNumber'] ?? ''));

                    if ($itemNumber !== $streetNumber || $itemName !== strtolower($streetName)) {
                        continue;
                    }

                    if ($unitNumber !== '') {
                        if (strcasecmp(self::normalizeUnitToken($itemUnit), self::normalizeUnitToken($unitNumber)) !== 0) {
                            continue;
                        }
                    } elseif ($itemUnit !== '' && self::isUnitToken($itemUnit)) {
                        continue;
                    }

                    $rows[] = self::dbPropertyToHistoryRow($item, $parsed);
                }

                return $rows;
            };

            $cols = \Botble\RealEstate\Supports\PropertyFulltextSearch::HISTORY_COLUMNS;
            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
            $merged = [];

            $absorb = static function ($candidates) use (&$merged, $filterRows): void {
                foreach ($filterRows($candidates) as $row) {
                    $id = strtoupper(trim((string) ($row['listing_id'] ?? '')));
                    if ($id === '') {
                        $merged[] = $row;
                        continue;
                    }
                    $merged[$id] = $row;
                }
            };

            // 1) Meilisearch — fast, but may lag right after fresh AMP imports.
            $meiliIds = $search->searchStreetCandidateIds($streetNumber, $streetName, 80, [
                'unit' => $unitNumber !== '' ? $unitNumber : null,
            ]);
            if (is_array($meiliIds) && $meiliIds !== []) {
                $absorb($search->hydrateIds($meiliIds, $cols));
            }

            // When a unit is known, never widen to the whole building — that merged
            // other condos' sold history into the wrong detail page.

            // 2) MySQL FULLTEXT — always merge (catches Meili lag + purged AMP siblings
            // that only exist locally after seed/import).
            $phrase = \Botble\RealEstate\Supports\PropertyFulltextSearch::sanitizePhrase(
                $streetNumber . ' ' . $streetName
            );
            if ($phrase !== '' && \Botble\RealEstate\Supports\PropertyFulltextSearch::fulltextAvailable()) {
                $ft = \Botble\RealEstate\Supports\PropertyFulltextSearch::applyFulltext(
                    \Botble\RealEstate\Supports\PropertyFulltextSearch::baseQuery($cols),
                    $phrase
                )->orderByDesc('created_at')->limit(80)->get();
                $absorb($ft);
            }

            // Do NOT fall back to leading name LIKE here — under sync/geocode load that
            // scanned ~180k rows and blew the 60s PHP limit ("Something is broken").

            return array_values($merged);
        });
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    protected static function dbPropertyToHistoryRow(object $item, array $parsed): array
    {
        $mlsStatus = (string) ($item->MlsStatus ?? '');
        $isActive = in_array($mlsStatus, ['New', 'Active', 'Ext', 'Price Change', 'Active Under Contract'], true);

        $dateEnd = null;
        if (! $isActive) {
            $dateEnd = $item->close_date
                ?? $item->purchase_contract_date
                ?? $item->listing_modified_at
                ?? $item->expire_date
                ?? null;
        }

        $price = $item->price ?? null;
        if (self::isSoldHistoryMlsStatus($mlsStatus) && isset($item->ClosePrice) && (float) $item->ClosePrice > 0) {
            $price = $item->ClosePrice;
        }

        return [
            'date_start' => self::formatDateValue($item->listing_contract_date ?? $item->created_at ?? null),
            'date_end' => self::formatDateValue($dateEnd),
            'price' => $price,
            'event' => self::eventLabel($mlsStatus ?: null, $item->TransactionType ?? null),
            'listing_id' => $item->external_id ?? $item->id,
            'address' => self::formatDisplayAddress($parsed),
            'unit_number' => self::isUnitToken($parsed['UnitNumber'] ?? null) ? trim((string) $parsed['UnitNumber']) : '',
            'street_number' => trim((string) ($parsed['StreetNumber'] ?? '')),
            'street_name' => trim((string) ($parsed['StreetName'] ?? '')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    public static function extractPriceChangesFromRecords(array $records): array
    {
        $changes = [];

        foreach ($records as $item) {
            $current = $item['ListPrice'] ?? null;
            $previous = $item['PreviousListPrice'] ?? null;
            $original = $item['OriginalListPrice'] ?? null;

            if ($previous !== null && $current !== null && (float) $previous !== (float) $current) {
                $changes[] = [
                    'date' => self::formatDateValue($item['PriceChangeTimestamp'] ?? $item['ModificationTimestamp'] ?? null),
                    'old_price' => $previous,
                    'new_price' => $current,
                    'event' => 'Price Change',
                    'listing_id' => $item['ListingKey'] ?? null,
                ];
            }

            if (
                $original !== null
                && $current !== null
                && (float) $original !== (float) $current
                && (float) $original !== (float) ($previous ?? $original)
            ) {
                $changes[] = [
                    'date' => self::formatDateValue($item['ListingContractDate'] ?? null),
                    'old_price' => $original,
                    'new_price' => $current,
                    'event' => ($item['PriorMlsStatus'] ?? '') === 'Price Change' ? 'Price Change' : 'Listed',
                    'listing_id' => $item['ListingKey'] ?? null,
                ];
            }
        }

        $unique = [];
        foreach ($changes as $change) {
            $key = ($change['listing_id'] ?? '') . '|' . ($change['date'] ?? '') . '|' . ($change['new_price'] ?? '');
            $unique[$key] = $change;
        }

        usort($unique, fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return array_values($unique);
    }

    /**
     * Build price-change rows from listing history when AMP price fields are missing.
     *
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, array<string, mixed>>
     */
    public static function extractPriceChangesFromHistory(array $history): array
    {
        if ($history === []) {
            return [];
        }

        $sorted = $history;
        usort($sorted, function ($a, $b) {
            return strcmp($a['date_start'] ?? '', $b['date_start'] ?? '');
        });

        $changes = [];

        for ($i = 1, $count = count($sorted); $i < $count; $i++) {
            $prev = $sorted[$i - 1];
            $curr = $sorted[$i];
            $oldPrice = $prev['price'] ?? null;
            $newPrice = $curr['price'] ?? null;

            if ($oldPrice === null || $newPrice === null) {
                continue;
            }

            if ((float) $oldPrice === (float) $newPrice) {
                continue;
            }

            $event = (string) ($curr['event'] ?? 'Price Change');
            if (stripos($event, 'price') === false) {
                $event = (float) $newPrice > (float) $oldPrice ? 'Price Increase' : 'Price Change';
            }

            $changes[] = [
                'date' => $curr['date_start'] ?? '',
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'event' => $event,
                'listing_id' => $curr['listing_id'] ?? null,
            ];
        }

        $unique = [];
        foreach ($changes as $change) {
            $key = ($change['listing_id'] ?? '') . '|' . ($change['date'] ?? '') . '|' . ($change['new_price'] ?? '');
            $unique[$key] = $change;
        }

        usort($unique, fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return array_values($unique);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function localPropertyArray(string $listingKey): ?array
    {
        $property = DB::table('re_properties')
            ->where('external_id', strtoupper($listingKey))
            ->first();

        if (! $property) {
            return null;
        }

        return self::dbRowToLocalArray($property);
    }

    /**
     * @return array<string, mixed>
     */
    public static function dbRowToLocalArray(object $property): array
    {
        return [
            'property_id' => $property->id,
            'name' => $property->name,
            'price' => $property->price,
            'square' => $property->square,
            'MlsStatus' => $property->MlsStatus,
            'TransactionType' => $property->TransactionType,
            'PropertySubType' => $property->PropertySubType,
            'broker' => $property->broker,
            'external_id' => $property->external_id,
            'created_at' => $property->created_at,
            'updated_at' => $property->updated_at,
            'listing_contract_date' => $property->listing_contract_date ?? null,
            'listing_modified_at' => $property->listing_modified_at ?? null,
            'image_val' => $property->image_val ?? null,
            'content' => $property->content ?? null,
            'ParkingSpaces' => $property->ParkingSpaces ?? null,
            'CoveredSpaces' => $property->CoveredSpaces ?? null,
            'number_bedroom' => $property->number_bedroom ?? null,
            'number_bathroom' => $property->number_bathroom ?? null,
            'BedroomsBelowGrade' => $property->BedroomsBelowGrade ?? null,
            'number_floor' => $property->number_floor ?? null,
            'Basement' => $property->Basement ?? null,
            'zip_code' => $property->zip_code ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolveFactRecordForDetail(string $listingKey, ?array $local = null, ?array $preferredAmp = null): array
    {
        $listingKey = strtoupper(trim($listingKey));
        $local ??= self::localPropertyArray($listingKey) ?? [];

        // Local-first: browsing must NEVER block on AMP or meta_boxes IO.
        // Order: caller AMP -> warm Cache snapshot (O(1)) -> local DB row.
        // Live AMP / meta_boxes hydrate only when we have nothing local.
        if ($preferredAmp) {
            $record = $preferredAmp;
        } else {
            $cached = Cache::get('treb_property_record_raw_v1_' . $listingKey);
            if (is_array($cached) && $cached !== []) {
                $record = self::enrichRecordAddress($cached);
            } elseif ($local !== []) {
                $record = self::recordFromLocal($local, $listingKey);
            } else {
                $record = self::loadStoredAmpSnapshot($listingKey)
                    ?: self::fetchAmpPropertyForResync($listingKey)
                    ?: self::fetchPropertyRecord($listingKey)
                    ?: self::fetchPropertyRecordRaw($listingKey);
            }
        }

        if (! $record) {
            return [];
        }

        $record = self::enrichRecordAddress($record);
        $record['ListingKey'] = $record['ListingKey'] ?? $listingKey;

        // Never call mergeAddressFallbackRecord here — it fans out to live AMP
        // sibling lookups (~1.8s). Detail pages already have local + warm-cache
        // facts; richer sibling enrichment belongs to background sync.
        if ($local) {
            foreach ([
                'City', 'CityRegion', 'PostalCode', 'StreetNumber', 'StreetName',
                'StreetSuffix', 'UnitNumber', 'UnparsedAddress', 'TaxAnnualAmount',
                'LotWidth', 'LotDepth', 'ArchitecturalStyle', 'Basement',
            ] as $field) {
                $currentEmpty = ! array_key_exists($field, $record) || $record[$field] === null || $record[$field] === '' || $record[$field] === [];
                if ($currentEmpty && ! empty($local[$field])) {
                    $record[$field] = $local[$field];
                }
            }
        }

        return $record;
    }

    public static function persistAmpSnapshot(string $listingKey, array $ampRecord): void
    {
        $listingKey = strtoupper(trim($listingKey));
        $ampRecord = self::enrichRecordAddress($ampRecord);

        if ($listingKey === '' || $ampRecord === []) {
            return;
        }

        Cache::put('treb_property_record_raw_v1_' . $listingKey, $ampRecord, 86400 * 14);
        Cache::put('treb_property_record_v5_' . $listingKey, $ampRecord, 86400 * 14);

        $property = DB::table('re_properties')->where('external_id', $listingKey)->first();

        if (! $property) {
            return;
        }

        $json = json_encode($ampRecord, JSON_UNESCAPED_UNICODE);
        $referenceType = 'Botble\\RealEstate\\Models\\Property';
        $existing = DB::table('meta_boxes')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $property->id)
            ->where('meta_key', 'amp_snapshot')
            ->first();

        if ($existing) {
            DB::table('meta_boxes')->where('id', $existing->id)->update(['meta_value' => $json]);
        } else {
            DB::table('meta_boxes')->insert([
                'reference_type' => $referenceType,
                'reference_id' => $property->id,
                'meta_key' => 'amp_snapshot',
                'meta_value' => $json,
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function loadStoredAmpSnapshot(string $listingKey): ?array
    {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return null;
        }

        $cached = Cache::get('treb_property_record_raw_v1_' . $listingKey);
        if (is_array($cached) && $cached !== []) {
            return self::enrichRecordAddress($cached);
        }

        $property = DB::table('re_properties')->where('external_id', $listingKey)->first();

        if (! $property) {
            return null;
        }

        $row = DB::table('meta_boxes')
            ->where('reference_type', 'Botble\\RealEstate\\Models\\Property')
            ->where('reference_id', $property->id)
            ->where('meta_key', 'amp_snapshot')
            ->first();

        if (! $row || empty($row->meta_value)) {
            return null;
        }

        $decoded = json_decode((string) $row->meta_value, true);

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        Cache::put('treb_property_record_raw_v1_' . $listingKey, $decoded, 86400 * 14);
        Cache::put('treb_property_record_v5_' . $listingKey, $decoded, 86400 * 14);

        return self::enrichRecordAddress($decoded);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchUnitListingRows(array $record): array
    {
        $rows = [];

        foreach (self::fetchUnitPropertyRecords($record) as $item) {
            $rows[] = self::propertyToHistoryRow($item);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected static function propertyToHistoryRow(array $item): array
    {
        $item = self::enrichRecordAddress($item);
        $mlsStatus = strtolower((string) ($item['MlsStatus'] ?? ''));
        $standardStatus = strtolower((string) ($item['StandardStatus'] ?? ''));
        $isActive = $standardStatus === 'active'
            || in_array($mlsStatus, ['new', 'active', 'ext', 'price change', 'active under contract'], true);

        $dateEnd = null;
        if (! $isActive) {
            $dateEnd = $item['PurchaseContractDate']
                ?? $item['CloseDate']
                ?? $item['TerminatedDate']
                ?? $item['ExpirationDate']
                ?? $item['UnavailableDate']
                ?? null;
        }

        $event = self::eventLabel($item['MlsStatus'] ?? null, $item['TransactionType'] ?? null);

        if (($item['PriorMlsStatus'] ?? '') === 'Price Change' && ($item['MlsStatus'] ?? '') === 'New') {
            $event = $item['TransactionType'] ?? 'For Sale';
        }

        $unit = self::isUnitToken($item['UnitNumber'] ?? null) ? trim((string) $item['UnitNumber']) : '';

        return [
            'date_start' => self::formatDateValue($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null),
            'date_end' => self::formatDateValue($dateEnd),
            'price' => $item['ListPrice'] ?? $item['ClosePrice'] ?? null,
            'event' => $event,
            'listing_id' => $item['ListingKey'] ?? null,
            'address' => self::formatDisplayAddress($item),
            'unit_number' => $unit,
            'street_number' => trim((string) ($item['StreetNumber'] ?? '')),
            'street_name' => trim((string) ($item['StreetName'] ?? '')),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fetchPriceChanges(string $listingKey): array
    {
        $listingKey = strtoupper($listingKey);
        $cacheKey = 'treb_price_changes_v5_' . $listingKey;
        $local = self::localPropertyArray($listingKey);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        if (self::shouldSkipRemoteAmpFetch()) {
            self::schedulePriceChangesWarm($listingKey, $local, $cacheKey);

            return [];
        }

        try {
            return Cache::remember($cacheKey, 1800, function () use ($listingKey, $local): array {
                $record = self::fetchPropertyRecord($listingKey) ?: self::fetchPropertyRecordRaw($listingKey);

                if ($record) {
                    $record = self::enrichRecordAddress($record);
                    $candidates = self::fetchUnitPropertyRecords($record);

                    if ($candidates === []) {
                        $candidates = [$record];
                    }

                    $changes = self::extractPriceChangesFromRecords($candidates);
                    if ($changes !== []) {
                        return $changes;
                    }
                }

                $history = self::fetchListingHistory($listingKey, $local);

                return self::extractPriceChangesFromHistory($history);
            });
        } catch (\Throwable $e) {
            try {
                report($e);
            } catch (\Throwable) {
            }

            return [];
        }
    }

    protected static function schedulePriceChangesWarm(string $listingKey, ?array $local, string $cacheKey): void
    {
        app()->terminating(function () use ($listingKey, $local, $cacheKey): void {
            if (Cache::has($cacheKey)) {
                return;
            }

            try {
                Cache::remember($cacheKey, 1800, function () use ($listingKey, $local): array {
                    $record = self::fetchPropertyRecord($listingKey) ?: self::fetchPropertyRecordRaw($listingKey);

                    if ($record) {
                        $record = self::enrichRecordAddress($record);
                        $candidates = self::fetchUnitPropertyRecords($record);

                        if ($candidates === []) {
                            $candidates = [$record];
                        }

                        $changes = self::extractPriceChangesFromRecords($candidates);
                        if ($changes !== []) {
                            return $changes;
                        }
                    }

                    $history = self::fetchListingHistory($listingKey, $local);

                    return self::extractPriceChangesFromHistory($history);
                });
            } catch (\Throwable $e) {
                try {
                    report($e);
                } catch (\Throwable) {
                }
            }
        });
    }

    /**
     * Single cached payload for map popup (one HTTP round-trip, shared history fetch).
     *
     * @param  array<string, mixed>|null  $local
     * @return array<string, mixed>
     */
    public static function fetchMapPopupBundle(string $listingKey, ?array $local = null): array
    {
        $listingKey = strtoupper($listingKey);
        $cacheKey = 'treb_map_popup_bundle_v2_' . $listingKey;

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $local ??= self::localPropertyArray($listingKey);

        $historyCacheKey = 'treb_listing_history_detail_v8_' . $listingKey . '_guest';
        $history = Cache::get($historyCacheKey);
        if (! is_array($history)) {
            $history = self::fetchListingHistoryForDetail($listingKey, $local);
        }

        $record = self::resolveFactRecordForDetail($listingKey, $local);

        $priceChanges = self::extractPriceChangesFromHistory($history);
        if ($priceChanges === []) {
            $priceCached = Cache::get('treb_price_changes_v5_' . $listingKey);
            if (is_array($priceCached)) {
                $priceChanges = $priceCached;
            }
        }

        $imageVal = $local['image_val'] ?? $local['image'] ?? null;
        $images = self::getPropertyImages($listingKey, is_string($imageVal) ? $imageVal : null, false);
        if ($images === [] && is_string($imageVal) && str_starts_with($imageVal, 'http')) {
            $images = [$imageVal];
        }

        $description = '';
        if (! empty($local['content'])) {
            $description = strip_tags((string) $local['content']);
        } elseif (! empty($record['PublicRemarks'])) {
            $description = (string) $record['PublicRemarks'];
        }

        if ($record) {
            $record['display_address'] = self::formatDisplayAddress($record);
            $record['display_location'] = self::formatLocationLine($record);
        }

        $bundle = [
            'success' => true,
            'data' => $record,
            'key_facts' => $record ? self::buildKeyFacts($record, $local) : [],
            'property_details' => $record ? self::buildPropertyDetails($record, $local) : [],
            'description' => $description,
            'listing_history' => self::filterListingHistoryForViewer(
                $history,
                auth('account')->check() || auth()->check()
            ),
            'price_changes' => $priceChanges,
            'rooms' => self::fetchPropertyRoomsForDetail($listingKey),
            'images' => $images,
            'property_id' => $local['property_id'] ?? null,
            'is_locked' => false,
        ];

        Cache::put($cacheKey, $bundle, 3600);

        return $bundle;
    }

    /**
     * Local DB + AMP cache only — no live TREB calls (map popup fast path).
     *
     * @param  array<string, mixed>|null  $local
     * @return array<int, array<string, mixed>>
     */
    public static function fetchListingHistoryFast(string $listingKey, ?array $local = null): array
    {
        $listingKey = strtoupper($listingKey);
        $record = self::resolveMapPopupRecordFast($listingKey, $local);

        if (! $record) {
            return [];
        }

        $rows = self::fetchLocalDbHistory($record, $listingKey);

        $hasCurrent = false;
        foreach ($rows as $row) {
            if (strcasecmp((string) ($row['listing_id'] ?? ''), $listingKey) === 0) {
                $hasCurrent = true;
                break;
            }
        }
        if (! $hasCurrent) {
            array_unshift($rows, self::propertyToHistoryRow($record));
        }

        if ($rows !== []) {
            Cache::put('treb_listing_history_v5_' . $listingKey, $rows, 3600);
        }

        return self::filterListingHistoryForViewer(
            $rows,
            auth('account')->check() || auth()->check()
        );
    }

    /**
     * Cache/local record only — skips slow address-wide AMP lookups.
     *
     * @param  array<string, mixed>|null  $local
     * @return array<string, mixed>|null
     */
    public static function resolveMapPopupRecordFast(string $listingKey, ?array $local = null): ?array
    {
        $record = self::fetchPropertyRecord($listingKey) ?: self::fetchPropertyRecordRaw($listingKey);

        if (! $record && $local) {
            $record = self::enrichRecordAddress(self::recordFromLocal($local, $listingKey));
        }

        return $record;
    }

    /**
     * Fast AMP record for map popup — cache/local only, no live refresh.
     *
     * @param  array<string, mixed>|null  $local
     * @return array<string, mixed>|null
     */
    private static function resolveMapPopupRecord(string $listingKey, ?array $local = null): ?array
    {
        return self::resolveMapPopupRecordFast($listingKey, $local);
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>|null  $local
     * @return array<string, mixed>
     */
    public static function buildKeyFacts(array $record, ?array $local = null): array
    {
        $record = self::enrichRecordAddress($record);
        $normalizedSquare = self::normalizeSquareStorage($record['LivingAreaRange'] ?? ($local['square'] ?? null));
        $listingDate = $record['ListingContractDate'] ?? ($local['created_at'] ?? null);
        $style = self::formatListValue($record['ArchitecturalStyle'] ?? null);
        $propertyType = $record['PropertySubType'] ?? ($local['PropertySubType'] ?? '-');
        if ($style !== '-' && $propertyType !== '-') {
            $propertyType .= ', ' . $style;
        }

        $dataSource = $record['SourceSystemName'] ?? $record['OriginatingSystemName'] ?? null;
        if (! $dataSource) {
            $dataSource = 'PROPTX';
        }

        // Relative "Listed on" value for the facts table (label already says
        // "Listed on"), e.g. Today / This week / This month / June 2026.
        $listedRelative = self::relativeListedLabel($listingDate, '');
        $listedRelative = $listedRelative !== '' ? ucfirst($listedRelative) : '-';

        return [
            'tax' => isset($record['TaxAnnualAmount'])
                ? '$' . number_format((float) $record['TaxAnnualAmount']) . ' / ' . ($record['TaxYear'] ?? '-')
                : '-',
            'property_type' => $propertyType,
            'building_age' => $record['ApproximateAge'] ?? $record['YearBuilt'] ?? '-',
            'size' => self::formatSizeLabel(array_merge($record, [
                'LivingAreaRange' => $normalizedSquare,
                'square' => $normalizedSquare,
            ]), $local),
            'lot_size' => self::formatLotSize($record),
            'parking' => self::formatParkingLabel($record, $local),
            'basement' => self::formatListValue($record['Basement'] ?? null),
            'listing_number' => $record['ListingKey'] ?? ($local['external_id'] ?? '-'),
            'data_source' => $dataSource,
            'brokerage' => $record['ListOfficeName'] ?? ($local['broker'] ?? '-'),
            'days_on_market' => self::daysOnMarketValue($record, $local),
            'property_days_on_market' => isset($record['CumulativeDaysOnMarket']) && is_numeric($record['CumulativeDaysOnMarket'])
                ? max(0, (int) $record['CumulativeDaysOnMarket']) . ' days'
                : '-',
            'status_change' => self::formatRelativeTime($record['ModificationTimestamp'] ?? null),
            'listed_on' => $listedRelative,
            'updated_on' => self::formatDateValue($record['ModificationTimestamp'] ?? null) ?? '-',
            'bedrooms' => self::formatBedroomLabel($record, $local),
            'bathrooms' => $record['BathroomsTotalInteger'] ?? ($local['number_bathroom'] ?? '-'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildPropertyDetails(array $record, ?array $local = null): array
    {
        $record = self::enrichRecordAddress($record);
        $bathDetails = self::buildBathroomDetailsList($record);
        $structures = self::formatListValue($record['OtherStructures'] ?? null);
        $exterior = self::formatListValue($record['ExteriorFeatures'] ?? null);
        $propertyFeatures = $structures !== '-' && $exterior !== '-'
            ? $structures . ', ' . $exterior
            : ($structures !== '-' ? $structures : $exterior);
        $water = self::formatListValue($record['WaterSource'] ?? null);
        $petsAllowed = self::formatListValue($record['PetsAllowed'] ?? null);

        return [
            'property_type' => $record['PropertySubType'] ?? ($local['PropertySubType'] ?? '-'),
            'style' => self::formatArchitecturalStyle($record, $local),
            'fronting_on' => $record['DirectionFaces'] ?? '-',
            'community' => self::formatRegionLabel($record['CityRegion'] ?? null) ?: '-',
            'municipality' => self::formatCityLabel($record['City'] ?? null) ?: '-',
            'bedrooms' => self::formatBedroomTotal($record, $local),
            'bathrooms' => $record['BathroomsTotalInteger'] ?? ($local['number_bathroom'] ?? '-'),
            'bathrooms_details' => $bathDetails,
            'bathrooms_detail' => $bathDetails !== [] ? implode(' · ', $bathDetails) : '-',
            'basement' => self::formatListValue($record['Basement'] ?? null),
            'basement_type' => self::formatListValue($record['Basement'] ?? null),
            'kitchens' => $record['KitchensTotal'] ?? '-',
            'rooms' => $record['RoomsTotal'] ?? '-',
            'family_room' => self::formatYesNo($record['DenFamilyroomYN'] ?? null),
            'fireplace' => self::formatYesNo($record['FireplaceYN'] ?? null),
            'water' => $water,
            'cooling' => self::formatListValue($record['Cooling'] ?? null),
            'heating_type' => self::formatListValue($record['HeatType'] ?? null),
            'heating_fuel' => self::formatListValue($record['HeatSource'] ?? null),
            'size' => self::formatSizeLabel($record, $local),
            'structures' => $structures,
            'construction' => self::formatListValue($record['ConstructionMaterials'] ?? null),
            'driveway' => self::formatListValue($record['Driveway'] ?? null),
            'garage_type' => $record['GarageType'] ?? '-',
            'garage' => $record['CoveredSpaces'] ?? ($local['CoveredSpaces'] ?? '-'),
            'parking_places' => $record['ParkingSpaces'] ?? ($local['ParkingSpaces'] ?? '-'),
            'parking_total' => $record['ParkingTotal'] ?? '-',
            'property_features' => $propertyFeatures,
            'pets_allowed' => $petsAllowed,
            'sewer' => self::formatListValue($record['Sewer'] ?? null),
            'frontage' => isset($record['LotWidth']) ? rtrim(rtrim(number_format((float) $record['LotWidth'], 2), '0'), '.') : ($record['FrontageLength'] ?? '-'),
            'depth' => isset($record['LotDepth']) ? (int) round((float) $record['LotDepth']) : '-',
            'lot_size' => self::formatLotSize($record),
            'lot_size_code' => self::formatLotSize($record) !== '-' ? 'Feet' : '-',
            'cross_street' => $record['CrossStreet'] ?? '-',
            'listed_line' => ! empty($record['ListPrice']) && ! empty($record['ListingContractDate'])
                ? 'Property listed for $' . number_format((float) $record['ListPrice']) . ' on ' . (self::formatDateValue($record['ListingContractDate']) ?? '-')
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $local
     * @param  array<string, mixed>|null  $ampRecord
     * @return array<int, array<string, mixed>>
     */
    public static function fetchListingHistoryForDetail(string $listingKey, ?array $local = null, ?array $ampRecord = null): array
    {
        $listingKey = strtoupper(trim($listingKey));
        $authenticated = auth('account')->check() || auth()->check();
        // v8: unit-exact history (no building-wide sibling bleed).
        $cacheKey = 'treb_listing_history_detail_v9_' . $listingKey . ($authenticated ? '_auth' : '_guest');

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        if (self::shouldSkipRemoteAmpFetch()) {
            $record = $ampRecord ?: self::resolveFactRecordForDetail($listingKey, $local);
            if ($record === []) {
                self::scheduleListingHistoryWarm($listingKey, $local, $ampRecord, $cacheKey);

                return [];
            }

            $stub = self::filterListingHistoryForViewer(
                [self::propertyToHistoryRow(self::enrichRecordAddress($record))],
                $authenticated
            );

            self::scheduleListingHistoryWarm($listingKey, $local, $ampRecord ?: $record, $cacheKey);

            return $stub;
        }

        return self::computeListingHistoryForDetail($listingKey, $local, $ampRecord, $authenticated, $cacheKey);
    }

    protected static function scheduleListingHistoryWarm(
        string $listingKey,
        ?array $local,
        ?array $ampRecord,
        string $cacheKey
    ): void {
        app()->terminating(function () use ($listingKey, $local, $ampRecord, $cacheKey): void {
            if (Cache::has($cacheKey)) {
                return;
            }

            try {
                $authenticated = auth('account')->check() || auth()->check();
                self::computeListingHistoryForDetail($listingKey, $local, $ampRecord, $authenticated, $cacheKey);
            } catch (\Throwable $e) {
                try {
                    report($e);
                } catch (\Throwable) {
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>|null  $local
     * @param  array<string, mixed>|null  $ampRecord
     * @return array<int, array<string, mixed>>
     */
    protected static function computeListingHistoryForDetail(
        string $listingKey,
        ?array $local,
        ?array $ampRecord,
        bool $authenticated,
        string $cacheKey
    ): array {
        try {
            $record = $ampRecord
                ?: self::resolveFactRecordForDetail($listingKey, $local);

            if (! $record) {
                return [];
            }

            $record = self::enrichRecordAddress($record);
            $rows = [];

            if (self::canFetchRemoteAmp()) {
                foreach (self::fetchUnitPropertyRecords($record) as $item) {
                    $rows[] = self::propertyToHistoryRow($item);
                }
            }

            foreach (self::fetchLocalDbHistory($record, $listingKey) as $row) {
                $rows[] = $row;
            }

            $hasCurrent = false;
            foreach ($rows as $row) {
                if (strcasecmp((string) ($row['listing_id'] ?? ''), $listingKey) === 0) {
                    $hasCurrent = true;
                    break;
                }
            }

            if (! $hasCurrent) {
                array_unshift($rows, self::propertyToHistoryRow($record));
            }

            $rows = self::filterHistoryRowsToSameUnit($rows, $record);
            $rows = self::dedupeHistoryRows($rows);

            usort($rows, fn ($a, $b) => strcmp($b['date_start'] ?? '', $a['date_start'] ?? ''));

            $rows = self::filterListingHistoryForViewer($rows, $authenticated);

            if ($rows !== []) {
                Cache::put($cacheKey, $rows, 1800);
            }

            return $rows;
        } catch (\Throwable $e) {
            try {
                report($e);
            } catch (\Throwable) {
            }

            return [];
        }
    }

    /**
     * Keep only history rows for the same unit (condo) or freehold (no unit).
     * Defense-in-depth after AMP/DB fetches.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, mixed>>
     */
    protected static function filterHistoryRowsToSameUnit(array $rows, array $record): array
    {
        $record = self::enrichRecordAddress($record);
        $unitNumber = self::isUnitToken($record['UnitNumber'] ?? null)
            ? self::normalizeUnitToken((string) $record['UnitNumber'])
            : '';
        $streetNumber = trim((string) ($record['StreetNumber'] ?? ''));
        $streetName = strtolower(trim((string) ($record['StreetName'] ?? '')));

        return array_values(array_filter($rows, function (array $row) use ($unitNumber, $streetNumber, $streetName) {
            $itemUnit = trim((string) ($row['unit_number'] ?? ''));
            $itemNumber = trim((string) ($row['street_number'] ?? ''));
            $itemName = strtolower(trim((string) ($row['street_name'] ?? '')));

            // Fallback: parse display address when structured fields missing (old cache).
            if ($itemUnit === '' && $itemNumber === '' && ! empty($row['address'])) {
                $parsed = self::enrichRecordAddress([
                    'UnparsedAddress' => (string) $row['address'],
                ]);
                $itemUnit = self::isUnitToken($parsed['UnitNumber'] ?? null)
                    ? self::normalizeUnitToken((string) $parsed['UnitNumber'])
                    : '';
                $itemNumber = trim((string) ($parsed['StreetNumber'] ?? ''));
                $itemName = strtolower(trim((string) ($parsed['StreetName'] ?? '')));
            } elseif ($itemUnit !== '' && ! self::isUnitToken($itemUnit)) {
                $itemUnit = '';
            }

            if ($streetNumber !== '' && $itemNumber !== '' && $itemNumber !== $streetNumber) {
                return false;
            }

            if ($streetName !== '' && $itemName !== '' && $itemName !== $streetName) {
                return false;
            }

            if ($unitNumber !== '') {
                return strcasecmp(self::normalizeUnitToken($itemUnit), $unitNumber) === 0;
            }

            // Detached / freehold: exclude condo/apartment unit rows.
            return $itemUnit === '' || ! self::isUnitToken($itemUnit);
        }));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fetchPropertyRoomsForDetail(string $listingKey): array
    {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return [];
        }

        $cacheKey = 'treb_property_rooms_detail_v2_' . $listingKey;

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        if (self::shouldSkipRemoteAmpFetch()) {
            return [];
        }

        $urls = [
            'https://query.ampre.ca/odata/PropertyRooms?'
                . '$filter=' . rawurlencode("ListingKey eq '{$listingKey}'")
                . '&$orderby=Order asc&$top=40',
            'https://query.ampre.ca/odata/PropertyRooms?'
                . '$filter=' . rawurlencode("ResourceRecordKey eq '{$listingKey}'")
                . '&$orderby=Order asc&$top=40',
        ];

        foreach ($urls as $url) {
            try {
                $payload = self::ampGetFresh($url, 12, 2);
            } catch (\Throwable $e) {
                try {
                    report($e);
                } catch (\Throwable) {
                }

                continue;
            }

            $rooms = $payload['value'] ?? [];

            if ($rooms !== []) {
                $mapped = array_map([self::class, 'mapAmpRoomRow'], $rooms);
                Cache::put($cacheKey, $mapped, 3600);

                return $mapped;
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mapAmpRoomRow(array $room): array
    {
        $features = array_values(array_filter([
            $room['RoomFeature1'] ?? null,
            $room['RoomFeature2'] ?? null,
            $room['RoomFeature3'] ?? null,
        ]));

        if (! empty($room['RoomFeatures']) && is_array($room['RoomFeatures'])) {
            $features = array_values(array_filter(array_merge($features, $room['RoomFeatures'])));
        }

        $featureText = $features !== [] ? implode(',', $features) : '-';

        return [
            'name' => $room['RoomDescription'] ?? $room['RoomType'] ?? 'Room',
            'size' => '-',
            'level' => $room['RoomLevel'] ?? $room['Level'] ?? '-',
            'features' => $featureText,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fetchPropertyRooms(string $listingKey): array
    {
        if ($listingKey === '') {
            return [];
        }

        $detailRooms = self::fetchPropertyRoomsForDetail($listingKey);

        if ($detailRooms !== []) {
            return $detailRooms;
        }

        $cacheKey = 'treb_property_rooms_v1_' . strtoupper($listingKey);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        if (! self::canFetchRemoteAmp()) {
            return [];
        }

        return Cache::remember($cacheKey, 3600, function () use ($listingKey) {
            $urls = [
                'https://query.ampre.ca/odata/PropertyRooms?'
                    . "\$filter=ListingKey eq '{$listingKey}'"
                    . '&$orderby=Order asc&$top=40',
                'https://query.ampre.ca/odata/PropertyRooms?'
                    . "\$filter=ResourceRecordKey eq '{$listingKey}'"
                    . '&$orderby=Order asc&$top=40',
            ];

            foreach ($urls as $url) {
                $payload = self::ampGet($url);
                $rooms = $payload['value'] ?? [];
                if ($rooms !== []) {
                    return array_map([self::class, 'mapAmpRoomRow'], $rooms);
                }
            }

            return [];
        });
    }

    public static function normalizeSquareStorage(mixed $square): ?string
    {
        if ($square === null || $square === '') {
            return null;
        }

        if (is_string($square) && preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($square))) {
            return trim($square);
        }

        if (is_numeric($square)) {
            $number = (int) $square;
            $digits = (string) abs($number);

            if ($number >= 100000 && strlen($digits) % 2 === 0) {
                $half = (int) (strlen($digits) / 2);
                $low = (int) substr($digits, 0, $half);
                $high = (int) substr($digits, $half);

                if ($high > $low && ($high - $low) <= 5000) {
                    return $low . '-' . $high;
                }
            }

            return (string) $number;
        }

        return is_string($square) ? trim($square) : null;
    }

    public static function parseSquareFeet(mixed $value): float
    {
        if (is_numeric($value)) {
            $normalized = self::normalizeSquareStorage($value);
            if ($normalized && preg_match('/^(\d+)\s*-\s*(\d+)$/', $normalized, $matches)) {
                return (float) $matches[2];
            }

            return (float) $value;
        }

        if (! is_string($value) || $value === '') {
            return 0;
        }

        $value = trim($value);

        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $value, $matches)) {
            return (float) $matches[2];
        }

        if (str_contains($value, '-')) {
            $parts = explode('-', $value);
            $value = trim(end($parts));
        }

        $digits = preg_replace('/[^0-9.]/', '', $value);

        if ($digits === '') {
            return 0;
        }

        $number = (float) $digits;

        return $number > 10000 ? 0 : $number;
    }

    public static function formatDateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return is_string($value) ? $value : null;
        }
    }

    public static function formatBedroomLabel(array $record, ?array $local = null): string
    {
        $below = (int) ($record['BedroomsBelowGrade'] ?? ($local['BedroomsBelowGrade'] ?? 0));
        $above = (int) ($record['BedroomsAboveGrade'] ?? 0);

        if ($above > 0) {
            return $below > 0 ? $above . '+' . $below : (string) $above;
        }

        $total = (int) ($record['BedroomsTotal'] ?? ($local['number_bedroom'] ?? 0));

        if ($below > 0 && $total >= $below) {
            $main = $total - $below;

            return $main . '+' . $below;
        }

        if ($total > 0) {
            return (string) $total;
        }

        return '-';
    }

    public static function formatArchitecturalStyle(array $record, ?array $local = null): string
    {
        $style = self::formatListValue($record['ArchitecturalStyle'] ?? null);

        if ($style !== '-') {
            return $style;
        }

        $floor = (int) ($record['StoriesTotal'] ?? $local['number_floor'] ?? 0);

        if ($floor <= 0) {
            return '-';
        }

        return $floor === 1 ? '1 Floor' : $floor . '-Storey';
    }

    /**
     * AMP vendor tokens.
     *
     * Profiles (two TREB keys — proven split):
     * - live: TRREB_AUTH — current/new active inventory
     * - historical: TRREB_AUTH1 — older archive (2000+) that AUTH does not expose
     * - all: AUTH then AUTH1 (fallback for single-listing lookups)
     *
     * @return list<string>
     */
    public static function ampTokens(string $profile = 'all'): array
    {
        $auth = self::normalizeAmpToken(
            config('treb.auth') ?: self::readEnvFileValue('TRREB_AUTH')
        );
        $auth1 = self::normalizeAmpToken(
            config('treb.auth1') ?: self::readEnvFileValue('TRREB_AUTH1')
        );

        $tokens = match ($profile) {
            'live' => array_values(array_filter([$auth, $auth1])),
            'historical' => array_values(array_filter([$auth1, $auth])),
            default => array_values(array_unique(array_filter([$auth, $auth1]))),
        };

        return $tokens;
    }

    protected static function normalizeAmpToken(?string $token): ?string
    {
        if (! is_string($token)) {
            return null;
        }

        $token = trim($token, " \t\n\r\0\x0B\"'");

        return $token !== '' ? $token : null;
    }

    protected static function readEnvFileValue(string $key): ?string
    {
        static $envValues = null;

        if ($envValues === null) {
            $envValues = [];
            $path = base_path('.env');

            if (is_readable($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $line = trim($line);

                    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                        continue;
                    }

                    [$envKey, $envValue] = explode('=', $line, 2);
                    $envValues[trim($envKey)] = trim($envValue, " \t\n\r\0\x0B\"'");
                }
            }
        }

        return $envValues[$key] ?? null;
    }

    /**
     * Exponential backoff delay (seconds) for AMP retries — attempt is 1-based.
     */
    public static function ampBackoffSeconds(int $attempt, int $baseSeconds = 2, int $maxSeconds = 120): int
    {
        $attempt = max(1, $attempt);

        return min($maxSeconds, $baseSeconds * (2 ** ($attempt - 1)));
    }

    /**
     * AMPRE OData rule: when $expand=Media is present, $top may not exceed 100.
     * Larger $top returns HTTP 400 ("$top limited to 100 if $expand is specified")
     * and aborts the whole Property page (SyncLiveJob then imports 0 rows).
     */
    public static function clampAmpODataTopForMediaExpand(string $url): string
    {
        if ($url === '' || ! preg_match('/(?:\?|&|\$)expand=Media\b/i', $url)) {
            return $url;
        }

        return (string) preg_replace_callback(
            '/(?:\$|%24)top=(\d+)/i',
            static function (array $m) use ($url): string {
                $requested = (int) $m[1];
                if ($requested <= 100) {
                    return $m[0];
                }

                self::safeLog('warning', 'AMP URL clamped: $expand=Media requires $top<=100', [
                    'from' => $requested,
                    'to' => 100,
                    'url' => substr($url, 0, 300),
                ]);

                return str_replace((string) $requested, '100', $m[0]);
            },
            $url,
            1
        );
    }

    /**
     * @param  'live'|'historical'|'all'  $tokenProfile
     * @return array{ok:bool,data:?array,status:?int,url:string,body:?string,error:?string,token_profile:?string}
     */
    public static function ampRequest(
        string $url,
        int $timeout = 12,
        int $maxAttempts = 3,
        ?string $context = null,
        ?string $listingKey = null,
        string $tokenProfile = 'all'
    ): array {
        $url = self::clampAmpODataTopForMediaExpand($url);

        $result = [
            'ok' => false,
            'data' => null,
            'status' => null,
            'url' => $url,
            'body' => null,
            'error' => null,
            'token_profile' => $tokenProfile,
        ];

        // Never block HTML page renders on remote AMP (Media / Property / Rooms).
        // API + console + warm_treb still allowed via canFetchRemoteAmp().
        if (! self::canFetchRemoteAmp()) {
            $result['error'] = 'remote AMP disabled for page render';

            return $result;
        }

        $tokens = self::ampTokens($tokenProfile);

        if ($tokens === []) {
            $result['error'] = 'no AMP auth tokens configured';

            self::logAmpRequestFailure($context ?? 'ampRequest', $listingKey, $result);

            return $result;
        }

        $retryableStatuses = [408, 425, 429, 500, 502, 503, 504];

        foreach ($tokens as $tokenIndex => $token) {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                if ($attempt > 1) {
                    sleep(self::ampBackoffSeconds($attempt - 1));
                }

                try {
                    $response = Http::timeout($timeout)
                        ->connectTimeout(min(8, $timeout))
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Accept' => 'application/json',
                            'OData-Version' => '4.0',
                            'OData-MaxVersion' => '4.0',
                        ])
                        ->get($url);

                    $status = $response->status();
                    $body = $response->body();
                    $result['status'] = $status;
                    $result['body'] = self::truncateAmpBody($body);

                    if ($response->successful()) {
                        $json = $response->json();

                        if (! is_array($json)) {
                            $result['error'] = 'response is not valid JSON';

                            // try next token
                            break;
                        }

                        if (! empty($json['error']['message'])) {
                            $result['error'] = (string) $json['error']['message'];

                            // Auth/permission errors — try next token
                            break;
                        }

                        // AUTH can return HTTP 200 + empty value[] for archive years while
                        // AUTH1 still has rows (and vice-versa). Always try the next token.
                        $isCollection = array_key_exists('value', $json) && is_array($json['value']);
                        $isEmptyCollection = $isCollection && $json['value'] === [];
                        $hasMoreTokens = $tokenIndex < (count($tokens) - 1);

                        if ($isEmptyCollection && $hasMoreTokens) {
                            $result['error'] = 'empty collection — trying next AMP token';
                            break; // next token
                        }

                        return [
                            'ok' => true,
                            'data' => $json,
                            'status' => $status,
                            'url' => $url,
                            'body' => null,
                            'error' => null,
                            'token_profile' => $tokenProfile,
                            'token_index' => $tokenIndex,
                        ];
                    }

                    $json = $response->json();
                    $odataMessage = is_array($json) ? ($json['error']['message'] ?? null) : null;
                    $result['error'] = is_string($odataMessage) && $odataMessage !== ''
                        ? $odataMessage
                        : 'HTTP ' . $status;

                    if ($attempt < $maxAttempts && in_array($status, $retryableStatuses, true)) {
                        continue;
                    }

                    // Non-retryable for this token — try next token
                    break;
                } catch (\Throwable $e) {
                    $result['status'] = null;
                    $result['error'] = $e->getMessage();
                    $result['body'] = null;

                    if ($attempt < $maxAttempts) {
                        continue;
                    }

                    break; // next token
                }
            }
        }

        self::logAmpRequestFailure($context ?? 'ampRequest', $listingKey, $result);

        return $result;
    }

    protected static function truncateAmpBody(?string $body, int $limit = 2000): ?string
    {
        if ($body === null || $body === '') {
            return $body;
        }

        if (strlen($body) <= $limit) {
            return $body;
        }

        return substr($body, 0, $limit) . '…';
    }

    /**
     * @param  array{ok:bool,data:?array,status:?int,url:string,body:?string,error:?string}  $result
     */
    protected static function logAmpRequestFailure(string $context, ?string $listingKey, array $result): void
    {
        self::safeLog('warning', 'AMP request failed', [
            'context' => $context,
            'listing_key' => $listingKey,
            'url' => $result['url'] ?? null,
            'status' => $result['status'] ?? null,
            'error' => $result['error'] ?? null,
            'body' => $result['body'] ?? null,
        ]);
    }

    /**
     * Never let a locked/unwritable laravel.log take down property detail pages.
     * Live IIS has returned UnexpectedValueException: Permission denied on Log::*.
     */
    protected static function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            match ($level) {
                'info' => Log::info($message, $context),
                'error' => Log::error($message, $context),
                'debug' => Log::debug($message, $context),
                default => Log::warning($message, $context),
            };
        } catch (\Throwable) {
            // ignore — page render must continue
        }
    }

    protected static function isAmpSelectFieldError(array $result): bool
    {
        $haystack = strtolower((string) ($result['error'] ?? '') . ' ' . (string) ($result['body'] ?? ''));

        return str_contains($haystack, 'not defined in type')
            || str_contains($haystack, 'could not find a property named');
    }

    public static function ampGet(string $url, int $timeout = 5, string $tokenProfile = 'all'): ?array
    {
        $result = self::ampRequest($url, $timeout, 2, null, null, $tokenProfile);

        return $result['ok'] ? $result['data'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function fetchAmpBackfillRecord(string $listingKey): ?array
    {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return null;
        }

        $url = 'https://query.ampre.ca/odata/Property?'
            . '$filter=' . rawurlencode("ListingKey eq '{$listingKey}'")
            . '&$select=ListingKey,ListPrice,CoveredSpaces,ParkingSpaces,BedroomsAboveGrade,BedroomsBelowGrade,BedroomsTotal,ArchitecturalStyle,KitchensTotal';

        $payload = self::ampGet($url, 5);

        return $payload['value'][0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildLegacyBackfillChanges(object $property, ?array $ampItem = null): array
    {
        $changes = [];

        if (is_array($ampItem)) {
            $listPrice = (float) ($ampItem['ListPrice'] ?? 0);
            if ($listPrice > 0 && (float) $property->price !== $listPrice) {
                $changes['price'] = $listPrice;
            }

            if (array_key_exists('CoveredSpaces', $ampItem) && (int) ($property->CoveredSpaces ?? -1) !== (int) $ampItem['CoveredSpaces']) {
                $changes['CoveredSpaces'] = (int) $ampItem['CoveredSpaces'];
            }

            if (array_key_exists('ParkingSpaces', $ampItem) && (int) ($property->ParkingSpaces ?? -1) !== (int) $ampItem['ParkingSpaces']) {
                $changes['ParkingSpaces'] = (int) $ampItem['ParkingSpaces'];
            }

            $mainBeds = (int) ($ampItem['BedroomsAboveGrade'] ?? 0);
            if ($mainBeds <= 0) {
                $total = (int) ($ampItem['BedroomsTotal'] ?? 0);
                $below = (int) ($ampItem['BedroomsBelowGrade'] ?? 0);
                $mainBeds = $total > 0 && $total >= $below ? $total - $below : $total;
            }

            if ($mainBeds > 0 && (int) $property->number_bedroom !== $mainBeds) {
                $changes['number_bedroom'] = $mainBeds;
            }

            $belowGrade = (int) ($ampItem['BedroomsBelowGrade'] ?? 0);
            if ($belowGrade >= 0 && (int) ($property->BedroomsBelowGrade ?? -1) !== $belowGrade) {
                $changes['BedroomsBelowGrade'] = $belowGrade;
            }

            $value = $ampItem['ArchitecturalStyle'] ?? null;
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if (is_string($value) && preg_match('/(\d+)/', $value, $matches)) {
                $floor = (int) $matches[1];
                if ($floor > 0 && (int) ($property->number_floor ?? 0) !== $floor) {
                    $changes['number_floor'] = $floor;
                }
            }
        } else {
            $below = (int) ($property->BedroomsBelowGrade ?? 0);
            $beds = (int) ($property->number_bedroom ?? 0);

            if ($below > 0 && $beds > $below) {
                $mainBeds = $beds - $below;
                if ($mainBeds > 0 && $mainBeds !== $beds) {
                    $changes['number_bedroom'] = $mainBeds;
                }
            }

            if (empty($property->CoveredSpaces) && ! empty($property->ParkingSpaces)) {
                $changes['CoveredSpaces'] = (int) $property->ParkingSpaces;
            }

            if (is_string($property->Basement ?? null) && str_starts_with(trim($property->Basement), '[')) {
                $decoded = json_decode($property->Basement, true);
                if (is_array($decoded)) {
                    $normalized = implode(', ', array_filter($decoded));
                    if ($normalized !== '' && $normalized !== $property->Basement) {
                        $changes['Basement'] = $normalized;
                    }
                }
            }
        }

        return $changes;
    }

    public static function clearPropertyRelatedCaches(string $listingKey): void
    {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return;
        }

        foreach ([
            'treb_property_record_v5_',
            'treb_property_record_raw_v1_',
            'treb_listing_history_v5_',
            'treb_price_changes_v4_',
            'treb_price_changes_v5_',
            'treb_map_popup_bundle_v1_',
            'treb_map_popup_bundle_v2_',
            'treb_property_rooms_detail_v2_',
            'treb_listing_history_detail_v6_',
            'treb_listing_history_detail_v7_',
        ] as $prefix) {
            Cache::forget($prefix . $listingKey);
        }
    }

    /**
     * Fresh AMP OData request with retries — never uses response cache.
     *
     * @return array<string, mixed>|null
     */
    public static function ampGetFresh(string $url, int $timeout = 12, int $retries = 3, string $tokenProfile = 'all'): ?array
    {
        $result = self::ampRequest($url, $timeout, max(1, $retries), null, null, $tokenProfile);

        return $result['ok'] ? $result['data'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function fetchAmpPropertyForResync(string $listingKey): ?array
    {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return null;
        }

        $filter = rawurlencode("ListingKey eq '{$listingKey}'");
        $select = rawurlencode(self::propertyDetailSelectFields());

        $attempts = [
            'detail' => "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$top=1&\$select={$select}",
            'full' => "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$top=1",
        ];

        foreach ($attempts as $mode => $url) {
            $response = self::ampRequest($url, 30, 3, 'fetchAmpPropertyForResync', $listingKey, 'all');

            if (! $response['ok']) {
                if ($mode === 'detail' && self::isAmpSelectFieldError($response)) {
                    self::safeLog('info', 'AMP resync retrying without $select after field error', [
                        'listing_key' => $listingKey,
                        'status' => $response['status'],
                        'error' => $response['error'],
                    ]);

                    continue;
                }

                return null;
            }

            $record = $response['data']['value'][0] ?? null;

            if (is_array($record) && $record !== []) {
                $record = self::enrichRecordAddress($record);
                self::persistAmpSnapshot($listingKey, $record);

                return $record;
            }
        }

        self::safeLog('info', 'AMP listing not found (empty OData value)', [
            'listing_key' => $listingKey,
            'filter' => "ListingKey eq '{$listingKey}'",
        ]);

        return null;
    }

    public static function extractMainBedroomsFromAmp(array $item): int
    {
        if (isset($item['BedroomsAboveGrade']) && is_numeric($item['BedroomsAboveGrade'])) {
            return max(0, (int) $item['BedroomsAboveGrade']);
        }

        $total = (int) ($item['BedroomsTotal'] ?? 0);
        $below = max(0, (int) ($item['BedroomsBelowGrade'] ?? 0));

        if ($total > 0 && $total >= $below) {
            return $total - $below;
        }

        return max(0, $total);
    }

    public static function extractNumberFloorFromAmp(array $item): int
    {
        $candidates = [
            is_array($item['ArchitecturalStyle'] ?? null)
                ? implode(' ', $item['ArchitecturalStyle'])
                : ($item['ArchitecturalStyle'] ?? null),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return max(0, (int) $candidate);
            }

            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $value = trim($candidate);

            if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*storey/i', $value, $matches)) {
                return max(0, (int) round((float) $matches[1]));
            }

            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:storey|story|floor)/i', $value, $matches)) {
                return max(0, (int) round((float) $matches[1]));
            }
        }

        return 0;
    }

    public static function parseAmpDateForResync(?string $value): ?\Carbon\Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        $year = (int) $parsed->format('Y');

        if ($year < 2000 || $year > ((int) date('Y') + 1)) {
            return null;
        }

        return $parsed;
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildAmpResyncChanges(object $property, array $item): array
    {
        $changes = [];

        $assignString = function (string $column, mixed $value) use (&$changes, $property): void {
            if ($value === null || $value === '') {
                return;
            }

            $new = is_array($value) ? implode(', ', array_filter($value)) : trim((string) $value);

            if ($new === '') {
                return;
            }

            $old = trim((string) ($property->{$column} ?? ''));

            if ($old !== $new) {
                $changes[$column] = $new;
            }
        };

        $assignInt = function (string $column, mixed $value) use (&$changes, $property): void {
            if ($value === null || $value === '') {
                return;
            }

            $new = (int) $value;

            if ((int) ($property->{$column} ?? -999999) !== $new) {
                $changes[$column] = $new;
            }
        };

        $assignFloat = function (string $column, mixed $value) use (&$changes, $property): void {
            if ($value === null || $value === '') {
                return;
            }

            $new = (float) $value;

            if ($new <= 0) {
                return;
            }

            if (abs((float) ($property->{$column} ?? 0) - $new) > 0.001) {
                $changes[$column] = $new;
            }
        };

        $assignDate = function (string $column, ?string $ampValue) use (&$changes, $property): void {
            $parsed = self::parseAmpDateForResync($ampValue);

            if ($parsed === null) {
                return;
            }

            $new = $parsed->format('Y-m-d H:i:s');
            $old = $property->{$column} ?? null;
            $oldFormatted = $old ? \Carbon\Carbon::parse($old)->format('Y-m-d H:i:s') : null;

            if ($oldFormatted !== $new) {
                $changes[$column] = $parsed;
            }
        };

        $assignString('name', $item['UnparsedAddress'] ?? null);
        $assignString('location', $item['UnparsedAddress'] ?? null);
        $assignString('PropertySubType', $item['PropertySubType'] ?? null);
        $assignString('broker', $item['ListOfficeName'] ?? null);
        $assignString('zip_code', $item['PostalCode'] ?? null);
        $assignString('TransactionType', $item['TransactionType'] ?? null);
        $assignString('MlsStatus', $item['MlsStatus'] ?? null);
        $assignString('private_notes', $item['PrivateRemarks'] ?? null);

        if (! empty($item['PublicRemarks'])) {
            $assignString('description', $item['PublicRemarks']);
            $content = trim((string) $item['PublicRemarks'])
                . (empty($item['PrivateRemarks']) ? '' : '<br>' . $item['PrivateRemarks']);
            $assignString('content', $content);
        }

        $mainBeds = self::extractMainBedroomsFromAmp($item);
        if ($mainBeds > 0) {
            $assignInt('number_bedroom', $mainBeds);
        }

        if (isset($item['BathroomsTotalInteger']) && $item['BathroomsTotalInteger'] !== '') {
            $assignInt('number_bathroom', $item['BathroomsTotalInteger']);
        }

        if (isset($item['BedroomsBelowGrade']) && $item['BedroomsBelowGrade'] !== '') {
            $assignInt('BedroomsBelowGrade', $item['BedroomsBelowGrade']);
        }

        $floor = self::extractNumberFloorFromAmp($item);
        if ($floor > 0) {
            $assignInt('number_floor', $floor);
        }

        if (isset($item['CoveredSpaces']) && $item['CoveredSpaces'] !== '') {
            $assignInt('CoveredSpaces', $item['CoveredSpaces']);
        }

        if (isset($item['ParkingSpaces']) && $item['ParkingSpaces'] !== '') {
            $assignInt('ParkingSpaces', $item['ParkingSpaces']);
        }

        if (isset($item['Basement'])) {
            $assignString('Basement', $item['Basement']);
        }

        $assignFloat('price', $item['ListPrice'] ?? null);

        if (isset($item['ClosePrice']) && $item['ClosePrice'] !== '' && (float) $item['ClosePrice'] > 0) {
            $assignFloat('ClosePrice', $item['ClosePrice']);
        }

        $square = is_array($item['LivingAreaRange'] ?? null)
            ? null
            : self::normalizeSquareStorage($item['LivingAreaRange'] ?? null);

        if ($square) {
            $assignString('square', $square);
        }

        if (! empty($item['StandardStatus'])) {
            $newStatus = ($item['StandardStatus'] === 'Active') ? 'selling' : 'draft';
            $oldStatus = (string) ($property->status ?? '');

            if ($oldStatus !== $newStatus) {
                $changes['status'] = $newStatus;
            }
        }

        $assignDate('listing_contract_date', $item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
        $assignDate('listing_modified_at', $item['ModificationTimestamp'] ?? $item['PriceChangeTimestamp'] ?? null);
        $assignDate('close_date', $item['CloseDate'] ?? null);
        $assignDate('purchase_contract_date', $item['PurchaseContractDate'] ?? null);
        $assignDate('expire_date', $item['ExpirationDate'] ?? null);

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, mixed>>
     */
    public static function fetchAmpListingsForAddressHistory(array $record): array
    {
        $record = self::enrichRecordAddress($record);
        $streetNumber = trim((string) ($record['StreetNumber'] ?? ''));
        $streetName = trim((string) ($record['StreetName'] ?? ''));
        $unitNumber = self::isUnitToken($record['UnitNumber'] ?? null) ? trim((string) $record['UnitNumber']) : '';
        $rollNumber = trim((string) ($record['RollNumber'] ?? ''));

        $select = implode(',', [
            'ListingKey', 'ListPrice', 'ClosePrice', 'OriginalListPrice', 'MlsStatus', 'TransactionType',
            'StandardStatus', 'ListingContractDate', 'PurchaseContractDate', 'CloseDate', 'TerminatedDate',
            'ExpirationDate', 'UnavailableDate', 'ModificationTimestamp', 'OriginalEntryTimestamp',
            'UnparsedAddress', 'UnitNumber', 'StreetNumber', 'StreetName', 'StreetSuffix', 'RollNumber', 'PostalCode',
            'PropertySubType', 'ListOfficeName', 'BedroomsTotal', 'BedroomsAboveGrade', 'BedroomsBelowGrade',
            'BathroomsTotalInteger', 'LivingAreaRange', 'PublicRemarks', 'PrivateRemarks',
            'ParkingSpaces', 'CoveredSpaces', 'Basement',
        ]);

        $filters = [];

        // Prefer structured street filter — AUTH1 (historical) still holds many sold/terminated.
        if ($streetNumber !== '' && $streetName !== '' && ! self::isStreetSuffixWord($streetName)) {
            $escapedStreet = str_replace("'", "''", $streetName);
            $base = "StreetNumber eq '{$streetNumber}' and StreetName eq '{$escapedStreet}'";
            // Condo/apt: pin UnitNumber in OData — otherwise $top=50 returns other
            // units and the exact unit's older sold/leased keys never appear.
            if ($unitNumber !== '') {
                $base .= " and UnitNumber eq '" . str_replace("'", "''", $unitNumber) . "'";
            }
            $filters[] = $base;
        }

        if ($rollNumber !== '') {
            $filters[] = "RollNumber eq '" . str_replace("'", "''", $rollNumber) . "'";
        }

        // Exact address contains — catches rows where StreetName is empty/odd.
        $addressLine = self::formatDisplayAddress($record);
        if ($addressLine !== '') {
            $filters[] = "contains(UnparsedAddress,'" . str_replace("'", "''", $addressLine) . "')";
        }
        if ($streetNumber !== '' && $streetName !== '') {
            $filters[] = "contains(UnparsedAddress,'" . str_replace("'", "''", $streetNumber . ' ' . $streetName) . "')";
        }

        $unique = [];

        foreach (array_values(array_unique($filters)) as $filter) {
            $url = 'https://query.ampre.ca/odata/Property?'
                . '$filter=' . rawurlencode($filter)
                . '&$top=50'
                . '&$orderby=ListingContractDate desc'
                . '&$select=' . rawurlencode($select);

            // Merge AUTH1 + AUTH — live token often returns only the newest row and
            // would short-circuit before historical archive rows are seen.
            foreach (['historical', 'live'] as $profile) {
                $payload = self::ampGetFresh($url, 20, 2, $profile);

                foreach ($payload['value'] ?? [] as $item) {
                    $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));
                    if ($key === '') {
                        continue;
                    }

                    $itemNumber = trim((string) ($item['StreetNumber'] ?? ''));
                    $itemName = strtolower(trim((string) ($item['StreetName'] ?? '')));
                    $itemUnit = trim((string) ($item['UnitNumber'] ?? ''));

                    if ($streetNumber !== '' && $itemNumber !== '' && $itemNumber !== $streetNumber) {
                        continue;
                    }
                    if ($streetName !== '' && $itemName !== '' && $itemName !== strtolower($streetName)) {
                        continue;
                    }
                    if ($unitNumber !== '') {
                        if (strcasecmp(self::normalizeUnitToken($itemUnit), self::normalizeUnitToken($unitNumber)) !== 0) {
                            continue;
                        }
                    } elseif ($itemUnit !== '' && self::isUnitToken($itemUnit)) {
                        continue;
                    }

                    $unique[$key] = $item;
                }
            }
        }

        return array_values($unique);
    }

    public static function importAmpHistoryListing(array $item, bool $dryRun = false): string
    {
        $listingKey = strtoupper(trim((string) ($item['ListingKey'] ?? '')));

        if ($listingKey === '') {
            return 'skipped';
        }

        $existing = DB::table('re_properties')->where('external_id', $listingKey)->first();
        $changes = self::buildAmpResyncChanges($existing ?? (object) ['external_id' => $listingKey], $item);

        if ($dryRun) {
            return $existing ? 'would_update' : 'would_import';
        }

        if ($existing) {
            if ($changes !== []) {
                DB::table('re_properties')->where('id', $existing->id)->update($changes);
            }

            self::persistAmpSnapshot($listingKey, $item);
            self::clearListingHistoryCaches($listingKey);

            return $changes !== [] ? 'updated' : 'skipped';
        }

        $mlsStatus = (string) ($item['MlsStatus'] ?? 'New');
        $isActive = in_array($mlsStatus, ['New', 'Active', 'Ext', 'Price Change', 'Active Under Contract'], true);
        $portalStatus = $isActive
            ? 'selling'
            : (self::isSoldHistoryMlsStatus($mlsStatus)
                ? (str_contains(strtolower($mlsStatus), 'lease') ? 'rented' : 'sold')
                : 'draft');

        $row = array_merge([
            'external_id' => $listingKey,
            'unique_id' => strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10)),
            'author_id' => 1,
            'author_type' => 'Botble\\ACL\\Models\\User',
            'currency_id' => 1,
            'status' => $portalStatus,
            'moderation_status' => 'approved',
            'period' => 'month',
            'project_id' => 0,
            'is_featured' => 0,
            'featured_priority' => 0,
            'auto_renew' => 1,
            'never_expired' => 0,
            'country_id' => 1,
            'views' => 0,
            'latitude' => 0,
            'longitude' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'name' => self::formatDisplayAddress($item) ?: ($item['UnparsedAddress'] ?? $listingKey),
            'location' => $item['UnparsedAddress'] ?? null,
            'PropertySubType' => $item['PropertySubType'] ?? null,
            'MlsStatus' => $mlsStatus,
            'TransactionType' => $item['TransactionType'] ?? null,
            'price' => $item['ListPrice'] ?? 0,
            'broker' => $item['ListOfficeName'] ?? null,
            'zip_code' => $item['PostalCode'] ?? null,
            'number_bedroom' => self::extractMainBedroomsFromAmp($item),
            'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? 0),
            'number_floor' => self::extractNumberFloorFromAmp($item),
            // MySQL strict: NOT NULL columns without defaults must be set.
            'BedroomsBelowGrade' => max(0, (int) ($item['BedroomsBelowGrade'] ?? 0)),
            'ParkingSpaces' => max(0, (int) ($item['ParkingSpaces'] ?? 0)),
            'CoveredSpaces' => max(0, (int) ($item['CoveredSpaces'] ?? 0)),
            'Basement' => is_array($item['Basement'] ?? null)
                ? implode(', ', array_filter($item['Basement']))
                : (string) ($item['Basement'] ?? ''),
            'square' => is_array($item['LivingAreaRange'] ?? null) ? null : self::normalizeSquareStorage($item['LivingAreaRange'] ?? null),
            'listing_contract_date' => self::parseAmpDateForResync($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null),
            'listing_modified_at' => self::parseAmpDateForResync($item['ModificationTimestamp'] ?? null),
            'close_date' => self::parseAmpDateForResync($item['CloseDate'] ?? $item['PurchaseContractDate'] ?? null),
            'purchase_contract_date' => self::parseAmpDateForResync($item['PurchaseContractDate'] ?? null),
            'expire_date' => self::parseAmpDateForResync($item['ExpirationDate'] ?? null),
            'ClosePrice' => (float) ($item['ClosePrice'] ?? 0),
            'private_notes' => $item['PrivateRemarks'] ?? null,
            'description' => $item['PublicRemarks'] ?? null,
            'content' => trim((string) ($item['PublicRemarks'] ?? ''))
                . (empty($item['PrivateRemarks']) ? '' : '<br>' . $item['PrivateRemarks']),
        ], $changes);

        // array_merge can overwrite with null from $changes — force safe ints.
        $row['BedroomsBelowGrade'] = max(0, (int) ($row['BedroomsBelowGrade'] ?? 0));
        $row['ParkingSpaces'] = max(0, (int) ($row['ParkingSpaces'] ?? 0));
        $row['CoveredSpaces'] = max(0, (int) ($row['CoveredSpaces'] ?? 0));
        if (! isset($row['Basement']) || $row['Basement'] === null) {
            $row['Basement'] = '';
        }

        DB::table('re_properties')->insert($row);
        self::persistAmpSnapshot($listingKey, $item);
        self::clearListingHistoryCaches($listingKey);

        return 'imported';
    }

    /**
     * @return array<string, mixed>
     */
    public static function syncAddressHistoryForListing(
        string $listingKey,
        bool $dryRun = false,
        int $maxSiblings = 0
    ): array
    {
        $listingKey = strtoupper(trim($listingKey));
        // 0 = unlimited (manual artisan). Live/queue jobs pass a small cap.
        $maxSiblings = max(0, $maxSiblings);

        $stats = [
            'listing' => $listingKey,
            'amp_found' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'history_rows' => 0,
            'listing_ids' => [],
        ];

        if ($listingKey === '') {
            return $stats;
        }

        $local = self::localPropertyArray($listingKey);
        $record = self::resolveFactRecordForDetail($listingKey, $local);

        if ($record === []) {
            $record = self::enrichRecordAddress(self::recordFromLocal($local ?? [], $listingKey));
        }

        if (! empty($record['ListingKey']) && empty($record['RollNumber'])) {
            $raw = self::fetchAmpPropertyForResync($listingKey) ?: self::loadStoredAmpSnapshot($listingKey);

            if ($raw && ! empty($raw['RollNumber'])) {
                $record['RollNumber'] = $raw['RollNumber'];
            }
        }

        $candidates = self::fetchAmpListingsForAddressHistory($record);
        $stats['amp_found'] = count($candidates);

        // Process the anchor listing first, then a capped set of siblings.
        usort($candidates, function ($a, $b) use ($listingKey) {
            $ka = strtoupper((string) ($a['ListingKey'] ?? ''));
            $kb = strtoupper((string) ($b['ListingKey'] ?? ''));
            if ($ka === $listingKey) {
                return -1;
            }
            if ($kb === $listingKey) {
                return 1;
            }

            return 0;
        });

        $processed = 0;
        foreach ($candidates as $item) {
            if ($maxSiblings > 0 && $processed >= $maxSiblings) {
                $stats['skipped'] += count($candidates) - $processed;
                break;
            }

            try {
                $result = self::importAmpHistoryListing($item, $dryRun);
            } catch (\Throwable $e) {
                self::safeLog('warning', '[syncAddressHistory] sibling import failed', [
                    'anchor' => $listingKey,
                    'sibling' => $item['ListingKey'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $stats['skipped']++;
                $processed++;
                continue;
            }

            $processed++;
            $stats['listing_ids'][] = strtoupper((string) ($item['ListingKey'] ?? ''));

            if ($result === 'imported' || $result === 'would_import') {
                $stats['imported']++;
            } elseif ($result === 'updated' || $result === 'would_update') {
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }

        if (! $dryRun) {
            self::clearListingHistoryCaches($listingKey);

            foreach ($stats['listing_ids'] as $relatedKey) {
                if ($relatedKey !== '' && $relatedKey !== $listingKey) {
                    self::clearListingHistoryCaches($relatedKey);
                }
            }

            $local = self::localPropertyArray($listingKey);
            $fact = self::resolveFactRecordForDetail($listingKey, $local);
            $history = self::fetchListingHistoryForDetail($listingKey, $local, $fact);
            $stats['history_rows'] = count($history);
        }

        return $stats;
    }

    public static function clearListingHistoryCaches(string $listingKey): void
    {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return;
        }

        foreach (['treb_listing_history_v5_', 'treb_listing_history_detail_v6_', 'treb_listing_history_detail_v7_', 'treb_listing_history_detail_v8_'] as $prefix) {
            Cache::forget($prefix . $listingKey);
            Cache::forget($prefix . $listingKey . '_guest');
            Cache::forget($prefix . $listingKey . '_auth');
        }

        self::clearPropertyRelatedCaches($listingKey);
    }
}
