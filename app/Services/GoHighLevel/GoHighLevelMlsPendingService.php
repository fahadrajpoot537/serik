<?php

namespace App\Services\GoHighLevel;

use App\Models\GhlMlsSyncTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Creates / re-queues pending MLS sync tasks without calling TREB or GHL.
 */
class GoHighLevelMlsPendingService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(string $contactId, string $mlsNumber, ?string $locationId = null, array $payload = []): GhlMlsSyncTask
    {
        $contactId = trim($contactId);
        $mlsNumber = strtoupper(trim($mlsNumber));
        $locationId = $locationId ?: (string) config('services.gohighlevel.location_id');

        if ($contactId === '' || $mlsNumber === '') {
            throw new \InvalidArgumentException('contact_id and mls_number are required.');
        }

        $externalKey = GhlMlsSyncTask::makeExternalKey($contactId, $mlsNumber);

        return DB::transaction(function () use ($contactId, $mlsNumber, $locationId, $externalKey, $payload) {
            /** @var GhlMlsSyncTask|null $existing */
            $existing = GhlMlsSyncTask::query()
                ->where('external_key', $externalKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // Re-queue completed/failed when MLS is entered again (idempotent pending).
                if (in_array($existing->status, [
                    GhlMlsSyncTask::STATUS_COMPLETED,
                    GhlMlsSyncTask::STATUS_FAILED,
                    GhlMlsSyncTask::STATUS_PENDING,
                ], true)) {
                    $existing->fill([
                        'status' => GhlMlsSyncTask::STATUS_PENDING,
                        'location_id' => $locationId,
                        'last_error' => null,
                        'queued_at' => now(),
                        'started_at' => null,
                        'completed_at' => null,
                        'source_payload' => $payload !== [] ? $payload : $existing->source_payload,
                    ]);
                    $existing->save();
                }

                Log::info('GoHighLevel MLS pending task upserted', [
                    'id' => $existing->id,
                    'contact_id' => $contactId,
                    'mls' => $mlsNumber,
                    'status' => $existing->status,
                ]);

                return $existing->fresh() ?? $existing;
            }

            $task = GhlMlsSyncTask::query()->create([
                'contact_id' => $contactId,
                'mls_number' => $mlsNumber,
                'location_id' => $locationId,
                'status' => GhlMlsSyncTask::STATUS_PENDING,
                'external_key' => $externalKey,
                'attempts' => 0,
                'source_payload' => $payload !== [] ? $payload : null,
                'queued_at' => now(),
            ]);

            Log::info('GoHighLevel MLS pending task created', [
                'id' => $task->id,
                'contact_id' => $contactId,
                'mls' => $mlsNumber,
            ]);

            return $task;
        });
    }

    /**
     * Extract contact + MLS from GHL webhook / workflow payloads.
     *
     * @param  array<string, mixed>  $payload
     * @return array{contact_id: string, mls_number: string, location_id: ?string}|null
     */
    public function extractFromWebhookPayload(array $payload): ?array
    {
        $contactId = (string) (
            data_get($payload, 'contact_id')
            ?? data_get($payload, 'contactId')
            ?? data_get($payload, 'id')
            ?? data_get($payload, 'contact.id')
            ?? data_get($payload, 'customData.contact_id')
            ?? ''
        );

        $locationId = data_get($payload, 'locationId')
            ?? data_get($payload, 'location_id')
            ?? data_get($payload, 'customData.location_id');

        $mls = (string) (
            data_get($payload, 'mls_number')
            ?? data_get($payload, 'mlsNumber')
            ?? data_get($payload, 'MLS Number')
            ?? data_get($payload, 'customData.mls_number')
            ?? data_get($payload, 'customData.mlsNumber')
            ?? ''
        );

        if ($mls === '') {
            $mls = $this->extractMlsFromCustomFields($payload);
        }

        $contactId = trim($contactId);
        $mls = strtoupper(trim($mls));

        if ($contactId === '' || $mls === '') {
            return null;
        }

        return [
            'contact_id' => $contactId,
            'mls_number' => $mls,
            'location_id' => $locationId ? (string) $locationId : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractMlsFromCustomFields(array $payload): string
    {
        $candidates = [
            data_get($payload, 'customFields'),
            data_get($payload, 'contact.customFields'),
            data_get($payload, 'customField'),
        ];

        $mlsKey = (string) config('gohighlevel.mls_sync.mls_field_key', 'contact.mls_number');

        foreach ($candidates as $fields) {
            if (! is_array($fields)) {
                continue;
            }

            // Associative map: key => value
            if (array_key_exists($mlsKey, $fields) || array_key_exists('mls_number', $fields)) {
                return (string) ($fields[$mlsKey] ?? $fields['mls_number'] ?? '');
            }

            foreach ($fields as $field) {
                if (! is_array($field)) {
                    continue;
                }
                $key = (string) ($field['key'] ?? $field['fieldKey'] ?? $field['id'] ?? '');
                $name = strtolower((string) ($field['name'] ?? ''));
                if (
                    $key === $mlsKey
                    || str_ends_with($key, 'mls_number')
                    || $name === 'mls number'
                    || $name === 'mls number '
                ) {
                    return (string) ($field['field_value'] ?? $field['value'] ?? $field['fieldValue'] ?? '');
                }
            }
        }

        return '';
    }
}
