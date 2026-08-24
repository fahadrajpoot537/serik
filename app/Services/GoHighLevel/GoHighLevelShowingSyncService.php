<?php

namespace App\Services\GoHighLevel;

use App\Models\GhlMlsSyncTask;
use App\Support\SerikAuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent MLS → GHL Custom Object "Showings" synchronizer.
 * Creates/updates a Showings record and associates it with the contact.
 * Does NOT write property data into Contact custom fields (inquiry fields stay untouched).
 */
class GoHighLevelShowingSyncService
{
    public function __construct(
        protected GoHighLevelHttpClient $http,
        protected GoHighLevelShowingObjectMapper $objectMapper,
        protected GoHighLevelShowingObjectRepository $objects,
    ) {
    }

    public function processTask(GhlMlsSyncTask $task): GhlMlsSyncTask
    {
        if (! $this->http->enabled()) {
            throw new \RuntimeException('GoHighLevel credentials are not configured.');
        }

        if (! config('gohighlevel.mls_sync.enabled', true)) {
            throw new \RuntimeException('GHL MLS sync is disabled.');
        }

        $task->status = GhlMlsSyncTask::STATUS_PROCESSING;
        $task->started_at = now();
        $task->attempts = (int) $task->attempts + 1;
        $task->save();

        $previousHash = (string) ($task->sync_hash ?? '');
        $previousMapped = is_array($task->mapped_fields) ? $task->mapped_fields : [];
        $previousRecordId = (string) ($previousMapped['_showing_record_id'] ?? '');

        $mapped = $this->objectMapper->mapFromMls($task->mls_number);
        $properties = $mapped['properties'];
        $hash = hash('sha256', json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

        $task->mapped_fields = array_merge($properties, [
            '_meta' => $mapped['meta'],
            '_showing_record_id' => $previousRecordId !== '' ? $previousRecordId : null,
        ]);
        $task->save();

        $contact = $this->http->get('/contacts/' . $task->contact_id);
        $existingId = (string) (data_get($contact, 'contact.id') ?? data_get($contact, 'id') ?? '');
        if ($existingId === '') {
            throw new \RuntimeException('GHL contact not found: ' . $task->contact_id);
        }

        if (
            config('gohighlevel.mls_sync.skip_unchanged', true)
            && $previousHash !== ''
            && hash_equals($previousHash, $hash)
            && $previousRecordId !== ''
        ) {
            $task->status = GhlMlsSyncTask::STATUS_COMPLETED;
            $task->completed_at = now();
            $task->last_error = null;
            $task->sync_hash = $hash;
            $task->save();

            Log::channel('ghl_sync')->info('GoHighLevel Showings sync skipped (unchanged)', [
                'task_id' => $task->id,
                'contact_id' => $task->contact_id,
                'mls' => $task->mls_number,
                'showing_record_id' => $previousRecordId,
            ]);

            GoHighLevelMetrics::incrDay('sync_skipped_unchanged');
            GoHighLevelMetrics::incrDay('sync_completed');
            GoHighLevelMetrics::markLastSuccess();

            return $task;
        }

        $attempted = count($properties);
        if ($attempted === 0) {
            throw new \RuntimeException('No Showings properties mapped for MLS ' . $task->mls_number);
        }

        $recordId = $previousRecordId !== ''
            ? $previousRecordId
            : (string) ($this->objects->findRecordIdByMls($task->mls_number, $task->contact_id) ?? '');

        $httpStatus = 200;
        try {
            if ($recordId !== '') {
                $record = $this->objects->updateRecord($recordId, $properties);
                $httpStatus = $this->http->lastStatus() ?? 200;
            } else {
                $record = $this->objects->createRecord($properties);
                $httpStatus = $this->http->lastStatus() ?? 201;
                $recordId = (string) ($record['id'] ?? data_get($record, 'record.id') ?? '');
            }
        } catch (\Throwable $e) {
            if (preg_match('/HTTP\s+(\d{3})/', $e->getMessage(), $m)) {
                $httpStatus = (int) $m[1];
            } elseif (str_contains($e->getMessage(), 'unauthorized')) {
                $httpStatus = 401;
            } else {
                $httpStatus = 0;
            }
            throw $e;
        }

        if ($recordId === '') {
            $recordId = (string) ($record['id'] ?? '');
        }
        if ($recordId === '') {
            throw new \RuntimeException('Showings record create/update did not return a record id.');
        }

        try {
            $this->objects->ensureAssociatedWithContact($recordId, $task->contact_id);
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel Showings association failed', [
                'task_id' => $task->id,
                'contact_id' => $task->contact_id,
                'showing_record_id' => $recordId,
                'message' => $e->getMessage(),
            ]);
            // Record write/verify is the completion gate. Association write can
            // 401 when associations/relation.write is missing; do not fail the task.
        }

        $verification = $this->verifyRecord($recordId, $properties);
        $verified = (int) ($verification['verified'] ?? 0);
        $rejected = (array) ($verification['rejected'] ?? []);
        $accepted = $attempted;
        $objectId = (string) ($verification['object_id'] ?? '');

        Log::channel('ghl_sync')->info('GoHighLevel Showings CO write+verify', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'object_key' => $this->objects->objectKey(),
            'object_id' => $objectId !== '' ? $objectId : null,
            'showing_record_id' => $recordId,
            'fields_attempted' => $attempted,
            'fields_accepted' => $accepted,
            'fields_verified' => $verified,
            'rejected_fields' => array_keys($rejected),
            'http_status' => $httpStatus,
            'address' => $mapped['meta']['unparsed_address'] ?? null,
        ]);

        if ($verified < 1 || $rejected !== []) {
            $coreRejected = array_values(array_intersect(
                array_keys($rejected),
                ['mls_number', 'address', 'price', 'bedroom', 'status', 'type']
            ));
            if ($verified < 1 || $coreRejected !== []) {
                $task->last_error = mb_substr('Showings verification failed: ' . json_encode([
                    'attempted' => $attempted,
                    'verified' => $verified,
                    'rejected' => $rejected,
                ]), 0, 2000);
                $task->mapped_fields = array_merge($properties, [
                    '_meta' => $mapped['meta'],
                    '_showing_record_id' => $recordId,
                    '_verification' => [
                        'attempted' => $attempted,
                        'accepted' => $accepted,
                        'verified' => $verified,
                        'rejected' => $rejected,
                        'http_status' => $httpStatus,
                        'object_key' => $this->objects->objectKey(),
                        'object_id' => $objectId !== '' ? $objectId : null,
                        'showing_record_id' => $recordId,
                    ],
                ]);
                $task->save();
                throw new \RuntimeException(
                    'Showings record update was not verified (verified=' . $verified . '/' . $attempted . ').'
                );
            }
        }

        $task->status = GhlMlsSyncTask::STATUS_COMPLETED;
        $task->completed_at = now();
        $task->last_error = null;
        $task->sync_hash = $hash;
        $task->mapped_fields = array_merge($properties, [
            '_meta' => $mapped['meta'],
            '_showing_record_id' => $recordId,
            '_verification' => [
                'attempted' => $attempted,
                'accepted' => $accepted,
                'verified' => $verified,
                'rejected' => $rejected,
                'http_status' => $httpStatus,
                'object_key' => $this->objects->objectKey(),
                'object_id' => $objectId !== '' ? $objectId : null,
                'showing_record_id' => $recordId,
            ],
        ]);
        $task->save();

        Log::channel('ghl_sync')->info('GoHighLevel Showings CO sync completed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'showing_record_id' => $recordId,
            'fields' => $attempted,
            'verified' => $verified,
        ]);

        GoHighLevelMetrics::incrDay('sync_completed');
        GoHighLevelMetrics::markLastSuccess();

        SerikAuditLog::event(SerikAuditLog::DOMAIN_GHL, 'sync_completed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'showing_record_id' => $recordId,
            'fields' => $attempted,
            'verified' => $verified,
        ]);

        return $task;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @return array{verified: int, rejected: array<string, string>, verified_keys: list<string>, object_id: string, object_key: string}
     */
    protected function verifyRecord(string $recordId, array $expected): array
    {
        $fresh = $this->objects->getRecord($recordId);
        $props = (array) ($fresh['properties'] ?? []);

        $verified = 0;
        $verifiedKeys = [];
        $rejected = [];

        foreach ($expected as $key => $value) {
            $prefixed = $this->objects->objectKey() . '.' . $key;
            $actual = $props[$key] ?? $props[$prefixed] ?? null;
            if ($actual === null && ! array_key_exists($key, $props) && ! array_key_exists($prefixed, $props)) {
                $rejected[$key] = 'missing_on_get';
                continue;
            }
            if ($this->valuesMatch($value, $actual)) {
                $verified++;
                $verifiedKeys[] = $key;
            } else {
                $rejected[$key] = 'mismatch';
            }
        }

        return [
            'verified' => $verified,
            'rejected' => $rejected,
            'verified_keys' => $verifiedKeys,
            'object_id' => (string) ($fresh['objectId'] ?? $fresh['object_id'] ?? ''),
            'object_key' => (string) ($fresh['objectKey'] ?? $fresh['object_key'] ?? $this->objects->objectKey()),
        ];
    }

    protected function valuesMatch(mixed $expected, mixed $actual): bool
    {
        if (is_array($expected) && isset($expected['value'])) {
            $expected = $expected['value'];
        }
        if (is_array($actual) && isset($actual['value'])) {
            $actual = $actual['value'];
        }
        if (is_array($actual) && ! isset($actual['value'])) {
            $actual = $actual[0] ?? json_encode($actual);
        }

        if (is_numeric($expected) && is_numeric($actual)) {
            return abs((float) $expected - (float) $actual) < 0.01;
        }

        $e = strtolower(trim((string) $expected));
        $a = strtolower(trim((string) $actual));
        if ($e === $a) {
            return true;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $e) && preg_match('/^\d{4}-\d{2}-\d{2}/', $a)) {
            return substr($e, 0, 10) === substr($a, 0, 10);
        }

        $ed = preg_replace('/\D+/', '', $e) ?? '';
        $ad = preg_replace('/\D+/', '', $a) ?? '';
        if ($ed !== '' && $ed === $ad) {
            return true;
        }

        // Option key vs label
        $en = preg_replace('/[^a-z0-9]+/', '', $e) ?? '';
        $an = preg_replace('/[^a-z0-9]+/', '', $a) ?? '';

        return $en !== '' && $en === $an;
    }

    public function markFailed(GhlMlsSyncTask $task, \Throwable $e): void
    {
        $task->status = GhlMlsSyncTask::STATUS_FAILED;
        $task->last_error = mb_substr($e->getMessage(), 0, 2000);
        $task->save();

        Log::channel('ghl_sync')->warning('GoHighLevel Showings CO sync failed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'message' => $e->getMessage(),
        ]);

        GoHighLevelMetrics::markLastFailure($e->getMessage());

        SerikAuditLog::event(SerikAuditLog::DOMAIN_GHL, 'sync_failed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'error' => $e->getMessage(),
        ], 'error');
    }
}
