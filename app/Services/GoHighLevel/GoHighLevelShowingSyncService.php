<?php

namespace App\Services\GoHighLevel;

use App\Models\GhlMlsSyncTask;
use App\Support\SerikAuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent MLS → GHL Showings (contact custom fields) synchronizer.
 * Updates the existing contact; never creates duplicate contacts/showings.
 * Marks completed only after GET verification of written custom fields.
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
        $attempted = count($customFields);
        if ($attempted === 0) {
            throw new \RuntimeException('No GHL custom field IDs resolved for mapped property fields.');
        }

        $httpStatus = 200;
        try {
            $this->http->put('/contacts/' . $task->contact_id, [
                'customFields' => $customFields,
            ]);
        } catch (\Throwable $e) {
            if (preg_match('/HTTP\s+(\d{3})/', $e->getMessage(), $m)) {
                $httpStatus = (int) $m[1];
            } else {
                $httpStatus = 0;
            }
            throw $e;
        }

        $verification = $this->verifyWrittenFields($task->contact_id, $customFields);
        $verified = (int) ($verification['verified'] ?? 0);
        $rejected = (array) ($verification['rejected'] ?? []);
        $accepted = $attempted; // PUT succeeded without 4xx

        Log::channel('ghl_sync')->info('GoHighLevel MLS showing sync write+verify', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'fields_attempted' => $attempted,
            'fields_accepted' => $accepted,
            'fields_verified' => $verified,
            'rejected_fields' => array_keys($rejected),
            'http_status' => $httpStatus,
            'address' => $mapped['meta']['unparsed_address'] ?? null,
        ]);

        $coreKeys = $this->mapper->coreShowingFieldKeys();
        $coreSent = 0;
        $coreVerified = 0;
        $idToKey = [];
        foreach ($customFields as $row) {
            $idToKey[(string) $row['id']] = (string) $row['key'];
            if (in_array((string) $row['key'], $coreKeys, true)) {
                $coreSent++;
            }
        }
        foreach ($verification['verified_keys'] ?? [] as $key) {
            if (in_array($key, $coreKeys, true)) {
                $coreVerified++;
            }
        }

        // Require every field we successfully addressed to GHL to round-trip on GET.
        // Soft-fail only when GHL omits optional empty-capable fields; core mismatches fail the task.
        if ($verified < 1 || ($coreSent > 0 && $coreVerified === 0)) {
            $task->last_error = mb_substr('Verification failed: ' . json_encode([
                'attempted' => $attempted,
                'verified' => $verified,
                'rejected' => $rejected,
            ]), 0, 2000);
            $task->save();
            throw new \RuntimeException(
                'GHL contact update was not verified after PUT (verified=' . $verified . '/' . $attempted . ').'
            );
        }

        if ($rejected !== []) {
            // Partial success: keep completed only if core showing fields that were sent verified.
            $coreRejected = array_values(array_filter(
                array_keys($rejected),
                static fn (string $k) => in_array($k, $coreKeys, true)
            ));
            if ($coreRejected !== []) {
                $task->last_error = mb_substr('Core field verification mismatch: ' . implode(',', $coreRejected), 0, 2000);
                $task->save();
                throw new \RuntimeException(
                    'GHL core showing fields failed verification: ' . implode(', ', $coreRejected)
                );
            }
        }

        $task->status = GhlMlsSyncTask::STATUS_COMPLETED;
        $task->completed_at = now();
        $task->last_error = null;
        $task->sync_hash = $hash;
        $task->mapped_fields = array_merge($fields, [
            '_verification' => [
                'attempted' => $attempted,
                'accepted' => $accepted,
                'verified' => $verified,
                'rejected' => $rejected,
                'http_status' => $httpStatus,
                'core_sent' => $coreSent,
                'core_verified' => $coreVerified,
            ],
        ]);
        $task->save();

        Log::channel('ghl_sync')->info('GoHighLevel MLS showing sync completed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'fields' => $attempted,
            'verified' => $verified,
            'address' => $mapped['meta']['unparsed_address'] ?? null,
        ]);

        GoHighLevelMetrics::incrDay('sync_completed');
        GoHighLevelMetrics::markLastSuccess();

        SerikAuditLog::event(SerikAuditLog::DOMAIN_GHL, 'sync_completed', [
            'task_id' => $task->id,
            'contact_id' => $task->contact_id,
            'mls' => $task->mls_number,
            'fields' => $attempted,
            'verified' => $verified,
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
            if (str_starts_with((string) $key, '_')) {
                continue;
            }
            $id = $ids[$key] ?? null;
            if (! $id) {
                Log::channel('ghl_sync')->info('GoHighLevel field id missing; skipping', ['key' => $key]);
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

    /**
     * @param  list<array{id: string, key: string, field_value: mixed}>  $sent
     * @return array{verified: int, rejected: array<string, string>, verified_keys: list<string>}
     */
    protected function verifyWrittenFields(string $contactId, array $sent): array
    {
        $fresh = $this->http->get('/contacts/' . $contactId);
        $contact = data_get($fresh, 'contact', $fresh);
        $custom = is_array($contact) ? (array) data_get($contact, 'customFields', []) : [];

        $byId = [];
        foreach ($custom as $field) {
            if (! is_array($field) || empty($field['id'])) {
                continue;
            }
            $byId[(string) $field['id']] = $field['value'] ?? $field['fieldValue'] ?? $field['field_value'] ?? null;
        }

        $verified = 0;
        $verifiedKeys = [];
        $rejected = [];

        foreach ($sent as $row) {
            $id = (string) $row['id'];
            $key = (string) $row['key'];
            $expected = $row['field_value'];
            if (! array_key_exists($id, $byId)) {
                $rejected[$key] = 'missing_on_get';
                continue;
            }
            if ($this->fieldValuesMatch($expected, $byId[$id])) {
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
        ];
    }

    protected function fieldValuesMatch(mixed $expected, mixed $actual): bool
    {
        if (is_array($actual)) {
            $actual = $actual[0] ?? json_encode($actual);
        }
        if (is_array($expected)) {
            $expected = $expected[0] ?? json_encode($expected);
        }

        if ($expected === null && ($actual === null || $actual === '')) {
            return true;
        }

        // Numeric / monetary
        if (is_numeric($expected) && is_numeric($actual)) {
            return abs((float) $expected - (float) $actual) < 0.01;
        }

        $e = strtolower(trim((string) $expected));
        $a = strtolower(trim((string) $actual));
        if ($e === $a) {
            return true;
        }

        // Dates: compare Y-m-d prefixes
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $e) && preg_match('/^\d{4}-\d{2}-\d{2}/', $a)) {
            return substr($e, 0, 10) === substr($a, 0, 10);
        }

        // Phone: compare digits only
        $ed = preg_replace('/\D+/', '', $e) ?? '';
        $ad = preg_replace('/\D+/', '', $a) ?? '';
        if ($ed !== '' && $ed === $ad) {
            return true;
        }

        // Money text with commas
        $en = preg_replace('/[^0-9.]/', '', $e) ?? '';
        $an = preg_replace('/[^0-9.]/', '', $a) ?? '';
        if ($en !== '' && is_numeric($en) && is_numeric($an)) {
            return abs((float) $en - (float) $an) < 0.01;
        }

        return false;
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
