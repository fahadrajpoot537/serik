<?php

namespace App\Services\GoHighLevel;

use App\Support\SerikCache;
use Illuminate\Support\Facades\Log;

/**
 * GHL Custom Object "Showings" (custom_objects.showings) record access.
 * Does not touch Contact custom fields used by inquiry/lead flows.
 */
class GoHighLevelShowingObjectRepository
{
    public const OBJECT_KEY = 'custom_objects.showings';

    public function __construct(protected GoHighLevelHttpClient $http)
    {
    }

    public function objectKey(): string
    {
        $key = trim((string) config('gohighlevel.mls_sync.showings_object_key', self::OBJECT_KEY));

        return $key !== '' ? $key : self::OBJECT_KEY;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function schemaFields(): array
    {
        $ttl = max(300, (int) config('gohighlevel.mls_sync.field_cache_ttl', 3600));
        $cacheKey = 'ghl_showings_co_fields_v1_' . md5($this->objectKey() . '|' . $this->http->locationId());

        return SerikCache::remember($cacheKey, $ttl, function () {
            $data = $this->http->get('/custom-fields/object-key/' . $this->objectKey(), [
                'locationId' => $this->http->locationId(),
            ]);
            $fields = data_get($data, 'fields', []);

            return is_array($fields) ? array_values(array_filter($fields, 'is_array')) : [];
        });
    }

    /**
     * @return array<string, array<string, mixed>> keyed by short field key (e.g. address)
     */
    public function fieldsByShortKey(): array
    {
        $prefix = $this->objectKey() . '.';
        $out = [];
        foreach ($this->schemaFields() as $field) {
            $fk = (string) ($field['fieldKey'] ?? '');
            if ($fk === '') {
                continue;
            }
            $short = str_starts_with($fk, $prefix) ? substr($fk, strlen($prefix)) : $fk;
            $out[$short] = $field;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $properties  short keys => values
     * @return array<string, mixed> created record
     */
    public function createRecord(array $properties): array
    {
        $payload = [
            'locationId' => $this->http->locationId(),
            'properties' => $properties,
        ];

        $data = $this->http->post(
            '/objects/' . $this->objectKey() . '/records',
            $payload
        );
        $record = data_get($data, 'record', $data);

        return is_array($record) ? $record : [];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public function updateRecord(string $recordId, array $properties): array
    {
        $payload = [
            'properties' => $properties,
        ];

        $data = $this->http->put(
            '/objects/' . $this->objectKey() . '/records/' . $recordId,
            $payload,
            ['locationId' => $this->http->locationId()]
        );
        $record = data_get($data, 'record', $data);

        return is_array($record) ? $record : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecord(string $recordId): array
    {
        $data = $this->http->get('/objects/' . $this->objectKey() . '/records/' . $recordId, [
            'locationId' => $this->http->locationId(),
        ]);
        $record = data_get($data, 'record', $data);

        return is_array($record) ? $record : [];
    }

    /**
     * Find an existing Showings record for this MLS (prefer one already linked to contact).
     */
    public function findRecordIdByMls(string $mlsNumber, ?string $contactId = null): ?string
    {
        $mls = strtoupper(trim($mlsNumber));
        if ($mls === '') {
            return null;
        }

        try {
            $data = $this->http->post(
                '/objects/' . $this->objectKey() . '/records/search',
                [
                    'locationId' => $this->http->locationId(),
                    'page' => 1,
                    'pageLimit' => 20,
                    'query' => $mls,
                ]
            );
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel Showings search failed', [
                'mls' => $mls,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $records = data_get($data, 'records', data_get($data, 'record', []));
        if (! is_array($records)) {
            return null;
        }
        // Some responses wrap a single record
        if (isset($records['id'])) {
            $records = [$records];
        }

        $matches = [];
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            $props = (array) ($record['properties'] ?? []);
            $value = (string) (
                $props['mls_number']
                ?? $props[$this->objectKey() . '.mls_number']
                ?? ''
            );
            if (strtoupper(trim($value)) !== $mls) {
                continue;
            }
            $id = (string) ($record['id'] ?? '');
            if ($id !== '') {
                $matches[$id] = count($props);
            }
        }

        if ($matches === []) {
            return null;
        }

        if ($contactId && count($matches) > 1) {
            foreach (array_keys($matches) as $id) {
                if ($this->isAssociatedWithContact($id, $contactId)) {
                    return $id;
                }
            }
        }

        arsort($matches);

        return (string) array_key_first($matches);
    }

    public function ensureAssociatedWithContact(string $recordId, string $contactId): void
    {
        if ($this->isAssociatedWithContact($recordId, $contactId)) {
            return;
        }

        $associationId = $this->resolveContactAssociationId();
        if ($associationId === null) {
            throw new \RuntimeException(
                'No GHL association found between contact and ' . $this->objectKey()
                . '. Create a Contact↔Showings association in GHL, or set GOHIGHLEVEL_SHOWINGS_CONTACT_ASSOCIATION_ID.'
            );
        }

        $this->http->post('/associations/relations', [
            'locationId' => $this->http->locationId(),
            'associationId' => $associationId,
            'firstRecordId' => $contactId,
            'secondRecordId' => $recordId,
        ]);
    }

    public function isAssociatedWithContact(string $recordId, string $contactId): bool
    {
        try {
            $data = $this->http->get('/associations/relations', [
                'locationId' => $this->http->locationId(),
                'recordId' => $recordId,
                'objectKey' => $this->objectKey(),
            ]);
        } catch (\Throwable) {
            // Fallback probe: associations by contact
            try {
                $data = $this->http->get('/associations/relations', [
                    'locationId' => $this->http->locationId(),
                    'recordId' => $contactId,
                    'objectKey' => 'contact',
                ]);
            } catch (\Throwable $e) {
                Log::channel('ghl_sync')->info('GoHighLevel association lookup skipped', [
                    'message' => $e->getMessage(),
                ]);

                return false;
            }
        }

        $relations = data_get($data, 'relations', data_get($data, 'associationRelations', []));
        if (! is_array($relations)) {
            return false;
        }

        foreach ($relations as $rel) {
            if (! is_array($rel)) {
                continue;
            }
            $first = (string) ($rel['firstRecordId'] ?? $rel['first_record_id'] ?? '');
            $second = (string) ($rel['secondRecordId'] ?? $rel['second_record_id'] ?? '');
            if (
                ($first === $contactId && $second === $recordId)
                || ($first === $recordId && $second === $contactId)
            ) {
                return true;
            }
        }

        return false;
    }

    public function resolveContactAssociationId(): ?string
    {
        $configured = trim((string) config('gohighlevel.mls_sync.showings_contact_association_id', ''));
        if ($configured !== '') {
            return $configured;
        }

        $ttl = max(300, (int) config('gohighlevel.mls_sync.field_cache_ttl', 3600));
        $cacheKey = 'ghl_showings_contact_assoc_v2_' . md5($this->objectKey() . '|' . $this->http->locationId());

        return SerikCache::remember($cacheKey, $ttl, function () {
            try {
                $data = $this->http->get('/associations/', [
                    'locationId' => $this->http->locationId(),
                ]);
            } catch (\Throwable $e) {
                Log::channel('ghl_sync')->warning('GoHighLevel associations list failed', [
                    'message' => $e->getMessage(),
                ]);

                return null;
            }

            $rows = data_get($data, 'associations', data_get($data, 'data', $data));
            if (! is_array($rows)) {
                return null;
            }

            $objectKey = $this->objectKey();
            $candidates = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $first = (string) ($row['firstObjectKey'] ?? $row['first_object_key'] ?? '');
                $second = (string) ($row['secondObjectKey'] ?? $row['second_object_key'] ?? '');
                $pair = [strtolower($first), strtolower($second)];
                sort($pair);
                $want = ['contact', strtolower($objectKey)];
                sort($want);
                if ($pair !== $want) {
                    continue;
                }
                $id = (string) ($row['id'] ?? $row['associationId'] ?? '');
                if ($id === '') {
                    continue;
                }
                $key = strtolower((string) ($row['key'] ?? $row['associationKey'] ?? ''));
                $candidates[] = ['id' => $id, 'key' => $key];
            }

            foreach (['showing_buyertenant', 'showing_buyer', 'buyer'] as $preferred) {
                foreach ($candidates as $candidate) {
                    if ($candidate['key'] === $preferred || str_contains($candidate['key'], $preferred)) {
                        return $candidate['id'];
                    }
                }
            }

            return $candidates[0]['id'] ?? null;
        });
    }
}
