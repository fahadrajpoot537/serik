<?php

namespace App\Services\GoHighLevel;

use App\Models\GhlMlsSyncTask;
use App\Support\SerikAuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent MLS → GHL Showings (contact custom fields) synchronizer.
 * Updates the existing contact; never creates duplicate contacts/showings.
 */
class GoHighLevelShowingSyncService
{
    public function __construct(
        protected GoHighLevelHttpClient $http,
        protected GoHighLevelShowingFieldMapper $mapper,
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

        $mapped = $this->mapper->mapFromMls($task->mls_number);
        $fields = $mapped['fields'];
        $hash = hash('sha256', json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

        $task->mapped_fields = $fields;

        $contact = $this->http->get('/contacts/' . $task->contact_id);
        $existingId = (string) (data_get($contact, 'contact.id') ?? data_get($contact, 'id') ?? '');
        if ($existingId === '') {
            throw new \RuntimeException('GHL contact not found: ' . $task->contact_id);
        }

        if (
            config('gohighlevel.mls_sync.skip_unchanged', true)
            && $previousHash !== ''
            && hash_equals($previousHash, $hash)
        ) {
            $task->status = GhlMlsSyncTask::STATUS_COMPLETED;
            $task->completed_at = now();
            $task->last_error = null;
            $task->sync_hash = $hash;
            $task->save();

            Log::channel('ghl_sync')->info('GoHighLevel MLS sync skipped (unchanged)', [
                'task_id' => $task->id,
                'contact_id' => $task->contact_id,
                'mls' => $task->mls_number,
            ]);

            GoHighLevelMetrics::incrDay('sync_skipped_unchanged');
            GoHighLevelMetrics::incrDay('sync_completed');
            GoHighLevelMetrics::markLastSuccess();

            return $task;
        }

        $customFields = $this->buildCustomFieldsPayload($fields);

        $this->http->put('/contacts/' . $task->contact_id, [
            'customFields' => $customFields,
        ]);

        $task->status = GhlMlsSyncTask::STATUS_COMPLETED;
        $task->completed_at = now();
        $task->last_error = null;
        $task->sync_hash = $hash;
        $task->save();

        Log::channel('ghl_sync')->info('GoHighLevel MLS showing sync completed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'fields' => count($fields),
            'address' => $mapped['meta']['unparsed_address'] ?? null,
        ]);

        GoHighLevelMetrics::incrDay('sync_completed');
        GoHighLevelMetrics::markLastSuccess();

        SerikAuditLog::event(SerikAuditLog::DOMAIN_GHL, 'sync_completed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'fields' => count($fields),
        ]);

        return $task;
    }

    /**
     * @param  array<string, mixed>  $fields  keyed by contact.* fieldKey
     * @return list<array{id: string, key: string, field_value: mixed}>
     */
    protected function buildCustomFieldsPayload(array $fields): array
    {
        $ids = $this->mapper->fieldIdMap();
        $out = [];

        foreach ($fields as $key => $value) {
            $id = $ids[$key] ?? null;
            if (! $id) {
                Log::info('GoHighLevel field id missing; skipping', ['key' => $key]);
                continue;
            }

            $out[] = [
                'id' => $id,
                'key' => $key,
                'field_value' => $value,
            ];
        }

        return $out;
    }

    public function markFailed(GhlMlsSyncTask $task, \Throwable $e): void
    {
        $task->status = GhlMlsSyncTask::STATUS_FAILED;
        $task->last_error = mb_substr($e->getMessage(), 0, 2000);
        $task->save();

        Log::channel('ghl_sync')->warning('GoHighLevel MLS showing sync failed', [
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
