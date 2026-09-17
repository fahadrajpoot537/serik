<?php

namespace App\Services\GoHighLevel;

use Carbon\Carbon;

/**
 * Maps Serik/TREB property data onto GHL Custom Object Showings properties
 * (custom_objects.showings.*). Does not map to Contact custom fields.
 */
class GoHighLevelShowingObjectMapper
{
    /** @var list<string> */
    public const PROPERTY_KEYS = [
        'mls_number',
        'address',
        'community',
        'garage_type',
        'price',
        'bedroom',
        'washroom',
        'kitchen',
        'contract',
        'status',
        'listing_status',
        'type',
        'sold_date',
        'fam',
        'ac',
        'heat',
        'listing_brokerage',
        'listing_brokerage_phone',
        'commission',
        'sold_price',
    ];

    public function __construct(
        protected GoHighLevelShowingFieldMapper $propertySource,
        protected GoHighLevelShowingObjectRepository $objects,
    ) {
    }

    /**
     * @return array{properties: array<string, mixed>, meta: array<string, mixed>, field_defs: array<string, array<string, mixed>>}
     */
    public function mapFromMls(string $mlsNumber): array
    {
        $source = $this->propertySource->resolvePropertySource($mlsNumber);
        $record = $source['record'];
        $mls = $source['mls'];
        $defs = $this->objects->fieldsByShortKey();

        $properties = [];
        $set = function (string $key, mixed $value) use (&$properties): void {
            if ($value === null) {
                return;
            }
            if (is_string($value) && trim($value) === '') {
                return;
            }
            $properties[$key] = $value;
        };

        $set('mls_number', $mls);
        $set('address', $this->string($record['UnparsedAddress'] ?? null));
        $set('community', $this->string($record['CityRegion'] ?? $record['City'] ?? null));
        $set('garage_type', $this->matchOption('garage_type', $this->firstListValue($record['GarageType'] ?? null), $defs));
        $set('price', $this->money($record['ListPrice'] ?? null));
        $set('bedroom', $this->numeric(
            $record['BedroomsAboveGrade'] ?? $record['BedroomsTotal'] ?? $record['BedroomsTotalInteger'] ?? null
        ));
        $set('washroom', $this->numeric(
            $record['BathroomsAboveGrade']
            ?? $record['BathroomsTotalInteger']
            ?? $record['BathroomsTotal']
            ?? null
        ));
        $kitchen = $this->numeric($record['KitchensTotal'] ?? null);
        $set('kitchen', $kitchen !== null ? (string) (int) $kitchen : null);
        $set('contract', $this->ghlDate($record['ListingContractDate'] ?? $record['OriginalEntryTimestamp'] ?? null));
        $set('status', $this->mapShowingStatus(
            $record['MlsStatus'] ?? $record['StandardStatus'] ?? null,
            $defs
        ));
        // Plain MLS listing status text (Active / Sold / …) — separate from appointment Status.
        $set('listing_status', $this->string(
            $record['MlsStatus'] ?? $record['StandardStatus'] ?? null
        ));
        $set('type', $this->mapPropertyType(
            $this->firstListValue($record['PropertySubType'] ?? $record['ArchitecturalStyle'] ?? null),
            $defs
        ));
        $set('sold_date', $this->ghlDate($record['CloseDate'] ?? null));
        $set('fam', $this->matchOption('fam', $this->ynToYesNo($record['DenFamilyroomYN'] ?? null), $defs));
        $set('ac', $this->matchOption('ac', $this->firstListValue($record['Cooling'] ?? null), $defs));
        $set('heat', $this->matchOption('heat', $this->firstListValue($record['HeatType'] ?? null), $defs));
        $set('listing_brokerage', $this->string($record['ListOfficeName'] ?? $record['broker'] ?? null));
        $set('listing_brokerage_phone', $this->resolveListingOfficePhone($mls, $record));
        $set('commission', $this->resolveCommission($mls, $record));
        $set('sold_price', $this->money($record['ClosePrice'] ?? null));

        ksort($properties);

        return [
            'properties' => $properties,
            'field_defs' => $defs,
            'meta' => [
                'mls' => $mls,
                'object_key' => $this->objects->objectKey(),
                'unparsed_address' => (string) ($record['UnparsedAddress'] ?? ''),
                'standard_status' => (string) ($record['StandardStatus'] ?? $record['MlsStatus'] ?? ''),
                'property_keys' => self::PROPERTY_KEYS,
            ],
        ];
    }

    /**
     * Map TREB PropertySubType onto GHL Showings Type option keys.
     * TREB uses "Condo Apartment"; GHL option is condo_apt / "Condo Apt" —
     * plain fuzzy contains fails because "condoapt" is not a substring of
     * "condoapartment".
     *
     * @param  array<string, array<string, mixed>>  $defs
     */
    protected function mapPropertyType(mixed $raw, array $defs): ?string
    {
        $value = $this->firstListValue($raw);
        if ($value === null) {
            return null;
        }

        $direct = $this->matchOption('type', $value, $defs);
        if ($direct !== null) {
            return $direct;
        }

        $normalized = $this->normalizeToken($value);
        $aliases = [
            'condoapartment' => 'condo_apt',
            'condoapt' => 'condo_apt',
            'condominiumapartment' => 'condo_apt',
            'condominium' => 'condo_apt',
            'apartment' => 'condo_apt',
            'commonelementcondo' => 'condo_apt',
            'coopapartment' => 'condo_apt',
            'coownershipapartment' => 'condo_apt',
            'detachedcondo' => 'condo_apt',
            'condotownhouse' => 'condo_townhouse',
            'condotownhome' => 'condo_townhouse',
            'attrowtownhouse' => 'townhouse',
            'attrowtwnhouse' => 'townhouse',
            'rowtownhouse' => 'townhouse',
            'twnhouse' => 'townhouse',
            'townhome' => 'townhouse',
            'semidetached' => 'semi_detached',
            'semidetachedhouse' => 'semi_detached',
        ];

        $preferred = $aliases[$normalized] ?? null;
        if ($preferred === null) {
            if (str_contains($normalized, 'condo') && (str_contains($normalized, 'apartment') || str_contains($normalized, 'apt'))) {
                $preferred = 'condo_apt';
            } elseif (str_contains($normalized, 'condo') && (str_contains($normalized, 'town') || str_contains($normalized, 'row'))) {
                $preferred = 'condo_townhouse';
            } elseif (str_contains($normalized, 'townhouse') || str_contains($normalized, 'twnhouse') || str_contains($normalized, 'townhome')) {
                $preferred = 'townhouse';
            } elseif (str_contains($normalized, 'semidetach')) {
                $preferred = 'semi_detached';
            } elseif ($normalized === 'detached' || str_starts_with($normalized, 'detached')) {
                $preferred = 'detached';
            } elseif ($normalized === 'link' || str_contains($normalized, 'linkhome')) {
                $preferred = 'link';
            }
        }

        if ($preferred === null) {
            return null;
        }

        return $this->matchOption('type', $preferred, $defs) ?? $preferred;
    }

    /**
     * AMPRE often omits ListOfficePhone on Property; try inline fields then a
     * dedicated Office lookup by ListOfficeKey (best-effort — many feeds redact phones).
     *
     * @param  array<string, mixed>  $record
     */
    protected function resolveListingOfficePhone(string $mls, array $record): ?string
    {
        $inline = $this->string(
            $record['ListOfficePhone']
            ?? $record['ListOfficePhoneNumber']
            ?? $record['ListAgentOfficePhone']
            ?? $record['CoListOfficePhone']
            ?? null
        );
        if ($inline !== null) {
            return $this->propertySource->normalizePhone($inline);
        }

        if (! class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
            return null;
        }

        try {
            if (method_exists(\Theme\homzen\Supports\TrebPropertyHelper::class, 'resolveListOfficePhoneForDetail')) {
                $resolved = \Theme\homzen\Supports\TrebPropertyHelper::resolveListOfficePhoneForDetail(
                    $mls,
                    $this->string(
                        $record['ListOfficeKey']
                        ?? $record['MainOfficeKey']
                        ?? $record['CoListOfficeKey']
                        ?? null
                    ),
                    $this->string($record['ListOfficeName'] ?? $record['broker'] ?? null)
                );
                if ($resolved !== null && trim($resolved) !== '') {
                    return $this->propertySource->normalizePhone($resolved);
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * AMPRE omits TransactionBrokerCompensation unless fetched with an explicit
     * filter — reuse the detail-page resolver so Showings commission stays filled.
     *
     * @param  array<string, mixed>  $record
     */
    protected function resolveCommission(string $mls, array $record): ?string
    {
        $inline = $this->string(
            $record['TransactionBrokerCompensation']
            ?? $record['BuyerAgencyCompensation']
            ?? $record['BuyerBrokerageCompensation']
            ?? null
        );
        if ($inline !== null && class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
            $formatted = \Theme\homzen\Supports\TrebPropertyHelper::formatCoopCommission($inline);

            return $formatted ?? $inline;
        }
        if ($inline !== null) {
            return $inline;
        }

        if (! class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
            return null;
        }

        try {
            return \Theme\homzen\Supports\TrebPropertyHelper::resolveCoopCommissionForDetail($mls);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $defs
     */
    protected function matchOption(string $shortKey, mixed $raw, array $defs): ?string
    {
        $value = $this->firstListValue($raw);
        if ($value === null) {
            return null;
        }

        $options = $this->optionPairs($defs[$shortKey] ?? []);
        if ($options === []) {
            return $value;
        }

        $needle = $this->normalizeToken($value);
        foreach ($options as $pair) {
            if ($this->normalizeToken($pair['key']) === $needle || $this->normalizeToken($pair['label']) === $needle) {
                return $pair['key'];
            }
        }

        // Fuzzy contains (Forced Air → gas_forced_air / Gas Forced Air)
        foreach ($options as $pair) {
            $k = $this->normalizeToken($pair['key']);
            $l = $this->normalizeToken($pair['label']);
            if ($needle !== '' && (str_contains($k, $needle) || str_contains($l, $needle) || str_contains($needle, $k) || str_contains($needle, $l))) {
                return $pair['key'];
            }
        }

        // Heat alias
        if ($shortKey === 'heat' && str_contains($needle, 'forcedair')) {
            foreach ($options as $pair) {
                if (str_contains($this->normalizeToken($pair['key'] . $pair['label']), 'forcedair')) {
                    return $pair['key'];
                }
            }
        }

        return null;
    }

    /**
     * Showings Status options are appointment-oriented (Scheduled/Completed/…).
     * Map listing status onto the closest available option key.
     *
     * @param  array<string, array<string, mixed>>  $defs
     */
    protected function mapShowingStatus(mixed $raw, array $defs): ?string
    {
        $value = $this->firstListValue($raw);
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeToken($value);
        $aliases = [
            'sold' => 'completed',
            'closed' => 'completed',
            'sld' => 'completed',
            'active' => 'interested',
            'new' => 'interested',
            'pending' => 'offer_made',
            'conditional' => 'offer_made',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'withdrawn' => 'cancelled',
        ];

        $preferred = $aliases[$normalized] ?? null;
        if ($preferred !== null) {
            return $this->matchOption('status', $preferred, $defs) ?? $preferred;
        }

        return $this->matchOption('status', $value, $defs);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<array{key: string, label: string}>
     */
    protected function optionPairs(array $field): array
    {
        $raw = $field['options'] ?? $field['picklistOptions'] ?? [];
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $opt) {
            if (is_string($opt)) {
                $out[] = ['key' => $opt, 'label' => $opt];
                continue;
            }
            if (! is_array($opt)) {
                continue;
            }
            $key = (string) ($opt['key'] ?? $opt['value'] ?? $opt['id'] ?? '');
            $label = (string) ($opt['label'] ?? $opt['name'] ?? $key);
            if ($key === '' && $label === '') {
                continue;
            }
            if ($key === '') {
                $key = $label;
            }
            $out[] = ['key' => $key, 'label' => $label !== '' ? $label : $key];
        }

        return $out;
    }

    protected function money(mixed $raw): mixed
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

        // GHL Custom Object MONETORY fields reject ISO codes that are not
        // enabled on the location (CAD → 400). Official shape is {currency, value}
        // with currency "default" (location business currency).
        return [
            'currency' => 'default',
            'value' => round((float) $raw, 2),
        ];
    }

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

    protected function numeric(mixed $raw): ?float
    {
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
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

    protected function ynToYesNo(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_bool($raw)) {
            return $raw ? 'Yes' : 'No';
        }
        $v = strtolower(trim((string) $raw));
        if (in_array($v, ['1', 'y', 'yes', 'true'], true)) {
            return 'Yes';
        }
        if (in_array($v, ['0', 'n', 'no', 'false'], true)) {
            return 'No';
        }

        return $this->string($raw);
    }

    protected function normalizeToken(string $value): string
    {
        $value = strtolower($value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
