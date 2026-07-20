<?php

namespace Botble\RealEstate\Supports;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyHistory;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Records append-only snapshots of a listing into re_property_history whenever
 * a tracked field changes. Designed to be completely side-effect free: any
 * failure is swallowed so the TREB import/upsert path is never interrupted.
 */
class PropertyHistoryRecorder
{
    /**
     * Runtime kill-switch (e.g. for one-off bulk field corrections that should
     * not generate history noise).
     */
    public static bool $enabled = true;

    private static ?bool $tableExists = null;

    /**
     * Fields whose change is worth a history row, mapped to the snapshot key.
     */
    private const TRACKED = [
        'price',
        'ClosePrice',
        'MlsStatus',
        'TransactionType',
        'status',
        'close_date',
        'purchase_contract_date',
        'listing_contract_date',
        'listing_modified_at',
    ];

    /**
     * Changes to these fields are "significant" enough to always log.
     */
    private const SIGNIFICANT = ['price', 'ClosePrice', 'MlsStatus', 'status', 'close_date'];

    public static function record(Property $property, bool $isNew, string $source = 'amp'): void
    {
        if (! self::$enabled) {
            return;
        }

        try {
            if (! self::tableExists()) {
                return;
            }

            $changed = [];

            if ($isNew) {
                $event = 'listed';
            } else {
                foreach (self::TRACKED as $field) {
                    if ($property->wasChanged($field)) {
                        $changed[$field] = [
                            'old' => self::scalar($property->getOriginal($field)),
                            'new' => self::scalar($property->getAttribute($field)),
                        ];
                    }
                }

                // Nothing meaningful changed — skip to avoid history noise.
                if (empty(array_intersect(array_keys($changed), self::SIGNIFICANT))) {
                    return;
                }

                $event = self::resolveEvent($property, $changed);
            }

            PropertyHistory::query()->create([
                'property_id' => $property->getKey(),
                'external_id' => $property->external_id,
                'event' => $event,
                'price' => self::numeric($property->price),
                'close_price' => self::numeric($property->ClosePrice),
                'mls_status' => $property->MlsStatus,
                'transaction_type' => $property->TransactionType,
                'status' => self::scalar($property->getAttribute('status')),
                'listing_contract_date' => $property->listing_contract_date,
                'listing_modified_at' => $property->listing_modified_at,
                'close_date' => $property->close_date,
                'purchase_contract_date' => $property->purchase_contract_date,
                'changed' => $changed ?: null,
                'snapshot' => self::snapshot($property),
                'source' => $source,
                'recorded_at' => now(),
            ]);
        } catch (Throwable $e) {
            // History is best-effort; never break the write that triggered it.
            report($e);
        }
    }

    private static function resolveEvent(Property $property, array $changed): string
    {
        $mls = (string) $property->MlsStatus;

        if (isset($changed['MlsStatus'])) {
            return match (true) {
                str_contains($mls, 'Sold') => 'sold',
                str_contains($mls, 'Leased') => 'leased',
                $mls === 'Terminated' => 'terminated',
                $mls === 'Expired' => 'expired',
                $mls === 'Suspended' => 'suspended',
                $mls === 'New' => 'relisted',
                default => 'status_change',
            };
        }

        if (isset($changed['price']) || isset($changed['ClosePrice'])) {
            return 'price_change';
        }

        return 'updated';
    }

    private static function snapshot(Property $property): array
    {
        $data = [];

        foreach (self::TRACKED as $field) {
            $data[$field] = self::scalar($property->getAttribute($field));
        }

        $data['name'] = (string) $property->name;
        $data['zip_code'] = $property->zip_code;
        $data['number_bedroom'] = $property->number_bedroom;
        $data['number_bathroom'] = $property->number_bathroom;

        return $data;
    }

    private static function tableExists(): bool
    {
        if (self::$tableExists === null) {
            self::$tableExists = Schema::hasTable('re_property_history');
        }

        return self::$tableExists;
    }

    private static function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function scalar(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'getValue')) {
            return $value->getValue();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $value;
    }
}
