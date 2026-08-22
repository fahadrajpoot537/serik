<?php

namespace App\Services\GoHighLevel;

use Carbon\Carbon;
use App\Support\SerikCache;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Maps TREB/AMP property records onto existing GHL Contact custom field keys.
 * Field keys and dropdown labels are never renamed.
 */
class GoHighLevelShowingFieldMapper
{
    public function __construct(protected GoHighLevelHttpClient $http)
    {
    }

    /**
     * @return array{fields: array<string, mixed>, meta: array<string, mixed>}
     */
    public function mapFromMls(string $mlsNumber): array
    {
        $mls = strtoupper(trim($mlsNumber));
        if ($mls === '') {
            throw new \InvalidArgumentException('MLS number is empty.');
        }

        // Prefer Serik DB when present; enrich from TREB/AMP for missing keys.
        $local = $this->recordFromLocalDatabase($mls);
        $remote = TrebPropertyHelper::fetchPropertyRecord($mls)
            ?: TrebPropertyHelper::fetchPropertyRecordRaw($mls)
            ?: TrebPropertyHelper::fetchAmpPropertyForResync($mls);

        if (is_array($local) && $local !== [] && is_array($remote) && $remote !== []) {
            $record = $this->mergePropertyRecords($local, $remote);
            Log::info('GoHighLevel MLS using local DB + TREB enrich', ['mls' => $mls]);
        } elseif (is_array($local) && $local !== []) {
            $record = $local;
            Log::info('GoHighLevel MLS using local DB only', ['mls' => $mls]);
        } elseif (is_array($remote) && $remote !== []) {
            $record = $remote;
        } else {
            throw new \RuntimeException('Property not found for MLS ' . $mls);
        }

        $rooms = [];
        try {
            $rooms = TrebPropertyHelper::fetchPropertyRooms($mls);
        } catch (\Throwable $e) {
            Log::info('GoHighLevel room fetch skipped', [
                'mls' => $mls,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->mapRecord($record, $rooms, $mls);
    }

    /**
     * Local wins on non-empty values; remote fills gaps (richer AMP attributes).
     *
     * @param  array<string, mixed>  $local
     * @param  array<string, mixed>  $remote
     * @return array<string, mixed>
     */
    protected function mergePropertyRecords(array $local, array $remote): array
    {
        $merged = $remote;
        foreach ($local as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value) && $value === []) {
                continue;
            }
            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  list<array<string, mixed>>  $rooms
     * @return array{fields: array<string, mixed>, meta: array<string, mixed>}
     */
    public function mapRecord(array $record, array $rooms, string $mls): array
    {
        $options = $this->fieldOptions();

        $fields = [];

        $set = function (string $key, mixed $value) use (&$fields): void {
            if ($value === null) {
                return;
            }
            if (is_string($value) && trim($value) === '') {
                return;
            }
            $fields[$key] = $value;
        };

        $setOption = function (string $key, mixed $raw) use (&$fields, $options, $set): void {
            $matched = $this->matchOption($key, $raw, $options[$key] ?? []);
            if ($matched !== null) {
                $set($key, $matched);
            }
        };

        $set('contact.mls_number', $mls);
        $set('contact.property_address', $this->string($record['UnparsedAddress'] ?? null));
        $set('contact.listing_address', $this->string($record['UnparsedAddress'] ?? null));
        $set('contact.street_number', $this->string($record['StreetNumber'] ?? null));
        $set('contact.street_name', $this->string($record['StreetName'] ?? null));
        $set('contact.street_type', $this->string($record['StreetSuffix'] ?? null));
        $setOption('contact.street_direction_prefix', $record['StreetDirPrefix'] ?? null);
        $setOption('contact.street_direction_suffix', $record['StreetDirSuffix'] ?? null);
        $set('contact.unit_number', $this->string($record['UnitNumber'] ?? null));
        $set('contact.municipality', $this->string($record['City'] ?? null));
        $set('contact.community', $this->string($record['CityRegion'] ?? null));
        $set('contact.areadistrict', $this->string($record['CityRegion'] ?? null));
        $set('contact.province', $this->string($record['StateOrProvince'] ?? null));
        $set('contact.cross_streets', $this->string($record['CrossStreet'] ?? null));
        $set('contact.public_remarks', $this->string($record['PublicRemarks'] ?? null));
        $set('contact.list_price', $this->moneyText($record['ListPrice'] ?? null));
        $set('contact.purchase_price', $this->moneyNumber($record['ClosePrice'] ?? null));
        $set('contact.year_built', $this->string($record['YearBuilt'] ?? $record['ApproximateAge'] ?? null));
        $set('contact.tax_year', $this->string($record['TaxYear'] ?? null));
        $set('contact.municipal_tax_amount', $this->moneyText($record['TaxAnnualAmount'] ?? null));
        $set('contact.assessment_roll_number', $this->string($record['RollNumber'] ?? null));
        $set('contact.lot_frontage', $this->string($record['LotWidth'] ?? $record['FrontageLength'] ?? null));
        $set('contact.lot_depth', $this->string($record['LotDepth'] ?? null));
        $set('contact.garage_spaces', $this->string($record['CoveredSpaces'] ?? $record['ParkingTotal'] ?? null));
        $set('contact.parking_spaces', $this->numeric($record['ParkingSpaces'] ?? $record['ParkingTotal'] ?? null));

        $bedsAbove = $this->numeric($record['BedroomsAboveGrade'] ?? null);
        $bedsBelow = $this->numeric($record['BedroomsBelowGrade'] ?? null);
        if ($bedsAbove === null && $bedsBelow === null) {
            $bedsAbove = $this->numeric($record['BedroomsTotal'] ?? $record['BedroomsTotalInteger'] ?? null);
        }
        $set('contact.bedrooms_above_grade', $bedsAbove);
        $set('contact.bedrooms_below_grade', $bedsBelow);

        $bathsAbove = $this->numeric($record['BathroomsAboveGrade'] ?? null);
        $bathsBelow = $this->numeric($record['BathroomsBelowGrade'] ?? null);
        if ($bathsAbove === null && $bathsBelow === null) {
            $bathsAbove = $this->numeric($record['BathroomsTotalInteger'] ?? $record['BathroomsTotal'] ?? null);
        }
        $set('contact.bathrooms_above_grade', $bathsAbove);
        $set('contact.bathrooms_below_grade', $bathsBelow);

        $set('contact.kitchens', $this->numeric($record['KitchensTotal'] ?? null));
        $set('contact.seller_name', $this->string($record['ListOfficeName'] ?? null));
        $set('contact.agent_information', $this->string($record['ListOfficeName'] ?? null));
        $set('contact.builders_phone', $this->normalizePhone(
            $this->string($record['ListOfficePhone'] ?? $record['ListOfficePhoneNumber'] ?? null)
        ));
        $set('contact.commission', $this->string(
            $record['TransactionBrokerCompensation'] ?? $record['BuyerAgencyCompensation'] ?? null
        ));
        $set('contact.extras', $this->listToText($record['ExteriorFeatures'] ?? null));
        $set('contact.chattels_included', $this->listToText($record['Appliances'] ?? null));

        $setOption('contact.property_style', $this->firstListValue($record['PropertySubType'] ?? $record['ArchitecturalStyle'] ?? null));
        $setOption('contact.basement', $this->firstListValue($record['Basement'] ?? null));
        $setOption('contact.heating_type', $this->firstListValue($record['HeatType'] ?? null));
        $setOption('contact.heating_fuel', $this->firstListValue($record['HeatSource'] ?? null));
        $setOption('contact.air_conditioning', $this->firstListValue($record['Cooling'] ?? null));
        $setOption('contact.garage_type', $this->firstListValue($record['GarageType'] ?? null));
        $setOption('contact.fronting_on', $this->firstListValue($record['DirectionFaces'] ?? null));
        $setOption('contact.water_supply', $this->firstListValue($record['WaterSource'] ?? null));
        $setOption('contact.sewers', $this->firstListValue($record['Sewer'] ?? null));
        $setOption('contact.exterior', $this->firstListValue($record['ConstructionMaterials'] ?? null));
        $setOption('contact.approximate_square_footage', $this->matchSquareRange($record['LivingAreaRange'] ?? null, $options['contact.approximate_square_footage'] ?? []));
        $setOption('contact.family_room', $this->ynToYesNo($record['DenFamilyroomYN'] ?? null));
        $setOption('contact.fireplacestove', $this->ynToYesNo($record['FireplaceYN'] ?? null));
        $setOption('contact.pool', $this->firstListValue($record['PoolFeatures'] ?? $record['OtherStructures'] ?? null));
        $setOption('contact.deal_status', $this->mapListingStatus(
            $record['MlsStatus'] ?? $record['StandardStatus'] ?? null,
            $options['contact.deal_status'] ?? []
        ));

        // Contract date = listing contract; sold/closing date from CloseDate.
        $set('contact.list_date', $this->ghlDate($record['ListingContractDate'] ?? $record['OriginalEntryTimestamp'] ?? null));
        $set('contact.expiry_date', $this->ghlDate($record['ExpirationDate'] ?? null));
        $set('contact.closing_date', $this->ghlDate($record['CloseDate'] ?? null));
        $set('contact.possession_date', $this->ghlDate($record['PossessionDate'] ?? null));

        $this->mapRooms($rooms, $set, $setOption);

        $this->mapApplianceHints($record, $setOption);

        ksort($fields);

        return [
            'fields' => $fields,
            'meta' => [
                'mls' => $mls,
                'listing_key' => (string) ($record['ListingKey'] ?? $mls),
                'unparsed_address' => (string) ($record['UnparsedAddress'] ?? ''),
                'standard_status' => (string) ($record['StandardStatus'] ?? $record['MlsStatus'] ?? ''),
                'field_count' => count($fields),
                'core_fields' => $this->coreShowingFieldKeys(),
            ],
        ];
    }

    /**
     * Business "Showings" property fields mapped onto existing Contact custom field keys.
     * (GHL template keys like custom_objects.showings.* resolve to these contact.* fields.)
     *
     * @return list<string>
     */
    public function coreShowingFieldKeys(): array
    {
        return [
            'contact.property_address',      // Address
            'contact.community',             // Community
            'contact.garage_type',           // Garage Type
            'contact.list_price',            // Price
            'contact.bedrooms_above_grade',  // Bedroom
            'contact.bathrooms_above_grade', // Washroom
            'contact.kitchens',              // Kitchen
            'contact.list_date',             // Contract
            'contact.deal_status',           // Status
            'contact.property_style',        // Type
            'contact.closing_date',          // Sold Date
            'contact.family_room',           // FAM
            'contact.air_conditioning',      // AC
            'contact.heating_type',          // Heat
            'contact.agent_information',     // Listing Brokerage
            'contact.builders_phone',        // Listing Brokerage Phone
            'contact.commission',            // Commission
            'contact.purchase_price',        // Sold Price
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rooms
     * @param  callable(string, mixed): void  $set
     * @param  callable(string, mixed): void  $setOption
     */
    protected function mapRooms(array $rooms, callable $set, callable $setOption): void
    {
        $max = min(12, count($rooms));
        for ($i = 0; $i < $max; $i++) {
            $room = $rooms[$i];
            $n = $i + 1;
            $set("contact.room_{$n}__type", $this->string($room['name'] ?? null));
            $dims = $room['dimensions'] ?? $room['size'] ?? null;
            if (is_string($dims) && ($dims === '-' || $dims === '')) {
                $dims = null;
            }
            $set("contact.room_{$n}__dimensions", $this->string($dims));
            $features = $room['features'] ?? null;
            if (is_string($features) && ($features === '-' || $features === '')) {
                $features = null;
            }
            $set("contact.room_{$n}__features", $this->string($features));
            $level = $room['level'] ?? null;
            if (is_string($level) && ($level === '-' || $level === '')) {
                $level = null;
            }
            $setOption("contact.room_{$n}__level", $level);
        }
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  callable(string, mixed): void  $setOption
     */
    protected function mapApplianceHints(array $record, callable $setOption): void
    {
        $blob = strtolower(implode(' ', array_filter([
            $this->listToText($record['Appliances'] ?? null),
            $this->listToText($record['ExteriorFeatures'] ?? null),
            $this->string($record['PublicRemarks'] ?? null),
        ])));

        if ($blob === '') {
            return;
        }

        $hints = [
            'contact.fridge' => ['fridge', 'refrigerator'],
            'contact.stove' => ['stove', 'range', 'cooktop'],
            'contact.dishwasher' => ['dishwasher'],
            'contact.washer' => ['washer'],
            'contact.dryer' => ['dryer'],
            'contact.microwave_oven' => ['microwave'],
            'contact.hood_fan' => ['hood fan', 'range hood'],
            'contact.central_vac' => ['central vac'],
            'contact.furnace' => ['furnace'],
            'contact.heat_pump' => ['heat pump'],
            'contact.ac' => [' a/c', 'air condition'],
            'contact.alarm_system' => ['alarm'],
            'contact.security_camera_system' => ['security camera', 'cctv'],
            'contact.solar_panel' => ['solar'],
            'contact.water_heater' => ['water heater', 'hot water'],
            'contact.water_softener' => ['water softener'],
            'contact.air_purifier' => ['air purifier'],
        ];

        foreach ($hints as $key => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($blob, $needle)) {
                    $setOption($key, 'Yes');
                    break;
                }
            }
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function fieldOptions(): array
    {
        return SerikCache::remember('ghl_contact_field_options_v1', max(300, (int) config('gohighlevel.mls_sync.field_cache_ttl', 3600)), function () {
            $data = $this->http->get('/locations/' . $this->http->locationId() . '/customFields');
            $out = [];
            foreach (($data['customFields'] ?? []) as $field) {
                $key = (string) ($field['fieldKey'] ?? '');
                if ($key === '') {
                    continue;
                }
                $opts = $field['options'] ?? $field['picklistOptions'] ?? [];
                if (! is_array($opts) || $opts === []) {
                    continue;
                }
                $out[$key] = array_values(array_filter(array_map(
                    static fn ($o) => is_string($o) ? $o : (string) ($o['label'] ?? $o['name'] ?? $o['value'] ?? ''),
                    $opts
                )));
            }

            return $out;
        });
    }

    /**
     * @return array<string, string> fieldKey => fieldId
     */
    public function fieldIdMap(): array
    {
        return SerikCache::remember('ghl_contact_field_ids_v1', max(300, (int) config('gohighlevel.mls_sync.field_cache_ttl', 3600)), function () {
            $data = $this->http->get('/locations/' . $this->http->locationId() . '/customFields');
            $out = [];
            foreach (($data['customFields'] ?? []) as $field) {
                $key = (string) ($field['fieldKey'] ?? '');
                $id = (string) ($field['id'] ?? '');
                if ($key !== '' && $id !== '') {
                    $out[$key] = $id;
                }
            }

            return $out;
        });
    }

    /**
     * @param  list<string>  $options
     */
    protected function matchOption(string $key, mixed $raw, array $options): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = is_array($raw) ? (string) ($raw[0] ?? '') : trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if ($options === []) {
            return $value;
        }

        foreach ($options as $opt) {
            if (strcasecmp($opt, $value) === 0) {
                return $opt;
            }
        }

        $normalized = $this->normalizeToken($value);
        foreach ($options as $opt) {
            if ($this->normalizeToken($opt) === $normalized) {
                return $opt;
            }
        }

        foreach ($options as $opt) {
            $optNorm = $this->normalizeToken($opt);
            if ($optNorm !== '' && (str_contains($normalized, $optNorm) || str_contains($optNorm, $normalized))) {
                return $opt;
            }
        }

        // Direction faces: North → N
        if ($key === 'contact.fronting_on') {
            $first = strtoupper(substr($normalized, 0, 1));
            foreach ($options as $opt) {
                if (strtoupper($opt) === $first) {
                    return $opt;
                }
            }
        }

        Log::info('GoHighLevel dropdown unmatched; skipping field', [
            'key' => $key,
            'value' => $value,
        ]);

        return null;
    }

    /**
     * @param  list<string>  $options
     */
    protected function matchSquareRange(mixed $raw, array $options): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $text = trim((string) $raw);
        if ($options !== []) {
            $direct = $this->matchOption('contact.approximate_square_footage', $text, $options);
            if ($direct !== null) {
                return $direct;
            }
        }

        if (! preg_match('/(\d{3,5})/', $text, $m)) {
            return null;
        }

        $sqft = (int) $m[1];
        $buckets = [
            '700-1100' => [700, 1099],
            '1100-1500' => [1100, 1499],
            '1500-2000' => [1500, 1999],
            '2000-2500' => [2000, 2499],
            '2500-3000' => [2500, 2999],
            '3000-3500' => [3000, 3499],
            '3500-5000' => [3500, 4999],
            '5000+' => [5000, PHP_INT_MAX],
        ];

        foreach ($buckets as $label => [$min, $max]) {
            if ($sqft >= $min && $sqft <= $max && in_array($label, $options, true)) {
                return $label;
            }
        }

        return null;
    }

    protected function ynToYesNo(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_bool($raw)) {
            return $raw ? 'Yes' : 'No';
        }
        $v = strtolower(trim((string) $raw));
        if (in_array($v, ['y', 'yes', 'true', '1'], true)) {
            return 'Yes';
        }
        if (in_array($v, ['n', 'no', 'false', '0'], true)) {
            return 'No';
        }

        return null;
    }

    /**
     * GHL Contact DATE custom fields accept calendar strings (Y-m-d), not epoch ms.
     */
    protected function ghlDate(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function moneyText(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return trim((string) $raw);
        }

        return number_format((float) $raw, 0, '.', '');
    }

    /**
     * Monetary custom fields (Purchase Price) accept plain numbers.
     */
    protected function moneyNumber(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            $digits = preg_replace('/[^0-9.]/', '', (string) $raw) ?? '';
            if ($digits === '' || ! is_numeric($digits)) {
                return null;
            }
            $raw = $digits;
        }

        return round((float) $raw, 2);
    }

    /**
     * Map TREB MlsStatus/StandardStatus onto GHL deal_status checkbox options.
     *
     * @param  list<string>  $options
     */
    protected function mapListingStatus(mixed $raw, array $options): ?string
    {
        $value = $this->firstListValue($raw);
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeToken($value);
        $aliases = [
            'sold' => 'Closed',
            'closed' => 'Closed',
            'sld' => 'Closed',
            'active' => null, // no matching deal_status option
            'pending' => 'Conditional Deal',
            'conditional' => 'Conditional Deal',
            'firm' => 'Firm Deal',
        ];

        $preferred = $aliases[$normalized] ?? null;
        if ($preferred !== null) {
            return $this->matchOption('contact.deal_status', $preferred, $options) ?? $preferred;
        }

        return $this->matchOption('contact.deal_status', $value, $options);
    }

    protected function numeric(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }

        return (float) $raw + 0;
    }

    protected function string(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $v = trim((string) $raw);

        return $v === '' ? null : $v;
    }

    protected function listToText(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_array($raw)) {
            $parts = array_values(array_filter(array_map(
                static fn ($v) => is_scalar($v) ? trim((string) $v) : '',
                $raw
            )));

            return $parts === [] ? null : implode(', ', $parts);
        }

        return $this->string($raw);
    }

    protected function firstListValue(mixed $raw): ?string
    {
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (is_scalar($item) && trim((string) $item) !== '') {
                    return trim((string) $item);
                }
            }

            return null;
        }

        return $this->string($raw);
    }

    protected function normalizeToken(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';

        return $value;
    }

    /**
     * Build an AMP-shaped record from local re_properties when remote TREB misses.
     *
     * @return array<string, mixed>|null
     */
    protected function recordFromLocalDatabase(string $mls): ?array
    {
        if (! class_exists(\Botble\RealEstate\Models\Property::class)) {
            return null;
        }

        /** @var \Botble\RealEstate\Models\Property|null $property */
        $property = \Botble\RealEstate\Models\Property::query()
            ->where(function ($q) use ($mls): void {
                $q->where('external_id', $mls)
                    ->orWhere('external_id', strtolower($mls));
            })
            ->first();

        if (! $property) {
            return null;
        }

        return [
            'ListingKey' => (string) $property->external_id,
            'UnparsedAddress' => (string) ($property->name ?? $property->location ?? ''),
            'City' => (string) ($property->city?->name ?? ''),
            'CityRegion' => (string) ($property->CityRegion ?? ''),
            'StateOrProvince' => (string) ($property->state?->name ?? 'ON'),
            'PostalCode' => (string) ($property->zip_code ?? ''),
            'ListPrice' => $property->price,
            'PublicRemarks' => (string) ($property->description ?? ''),
            'BedroomsAboveGrade' => $property->number_bedroom,
            'BedroomsBelowGrade' => $property->BedroomsBelowGrade,
            'BathroomsTotalInteger' => $property->number_bathroom,
            'KitchensTotal' => $property->number_floor ?? null,
            'ParkingSpaces' => $property->ParkingSpaces,
            'CoveredSpaces' => $property->CoveredSpaces,
            'Basement' => $property->Basement,
            'LivingAreaRange' => $property->square,
            'ArchitecturalStyle' => $property->PropertySubType ?? $property->ArchitecturalStyle ?? null,
            'PropertySubType' => $property->PropertySubType,
            'ListOfficeName' => $property->broker,
            'ListingContractDate' => $property->created_at?->toDateString(),
            'YearBuilt' => $property->YearBuilt ?? null,
            'TaxAnnualAmount' => $property->TaxAnnualAmount ?? null,
            'TaxYear' => $property->TaxYear ?? null,
            'LotWidth' => $property->LotWidth ?? null,
            'LotDepth' => $property->LotDepth ?? null,
            'CrossStreet' => $property->CrossStreet ?? null,
            'HeatType' => $property->HeatType ?? null,
            'HeatSource' => $property->HeatSource ?? null,
            'Cooling' => $property->Cooling ?? null,
            'GarageType' => $property->GarageType ?? null,
            'WaterSource' => $property->WaterSource ?? null,
            'Sewer' => $property->Sewer ?? null,
            'DirectionFaces' => $property->DirectionFaces ?? null,
            'StreetNumber' => $property->StreetNumber ?? null,
            'StreetName' => $property->StreetName ?? null,
            'StreetSuffix' => $property->StreetSuffix ?? null,
            'UnitNumber' => $property->UnitNumber ?? null,
        ];
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }
        if (str_starts_with($phone, '+')) {
            return '+' . ltrim($digits, '+');
        }

        return '+' . $digits;
    }
}
