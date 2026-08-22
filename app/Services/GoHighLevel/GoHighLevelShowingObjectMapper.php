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
        $set('type', $this->matchOption(
            'type',
            $this->firstListValue($record['PropertySubType'] ?? $record['ArchitecturalStyle'] ?? null),
            $defs
        ));
        $set('sold_date', $this->ghlDate($record['CloseDate'] ?? null));
        $set('fam', $this->matchOption('fam', $this->ynToYesNo($record['DenFamilyroomYN'] ?? null), $defs));
        $set('ac', $this->matchOption('ac', $this->firstListValue($record['Cooling'] ?? null), $defs));
        $set('heat', $this->matchOption('heat', $this->firstListValue($record['HeatType'] ?? null), $defs));
        $set('listing_brokerage', $this->string($record['ListOfficeName'] ?? null));
        $set('listing_brokerage_phone', $this->propertySource->normalizePhone(
            $this->string($record['ListOfficePhone'] ?? $record['ListOfficePhoneNumber'] ?? null)
        ));
        $set('commission', $this->string(
            $record['TransactionBrokerCompensation'] ?? $record['BuyerAgencyCompensation'] ?? null
        ));
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

        // MONETORY fields commonly accept {currency, value}
        return [
            'currency' => 'CAD',
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
