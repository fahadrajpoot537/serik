<?php

namespace App\Services\GoHighLevel;

use App\Jobs\ProcessGhlMlsSyncTaskJob;
use App\Models\GhlMlsSyncTask;
use App\Support\SerikQueue;
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
    public function enqueue(string $contactId, string $mlsNumber, ?string $locationId = null, array $payload = [], ?string $showingRecordId = null): GhlMlsSyncTask
    {
        $contactId = trim($contactId);
        $mlsNumber = strtoupper(trim($mlsNumber));
        $locationId = $locationId ?: (string) config('services.gohighlevel.location_id');
        $showingRecordId = strtolower(trim((string) (
            $showingRecordId
            ?? $this->extractShowingRecordIdFromPayload($payload)
            ?? ''
        )));
        if ($showingRecordId === '') {
            $showingRecordId = null;
        }

        if ($contactId === '' && $showingRecordId) {
            $contactId = $showingRecordId;
        }

        if ($contactId === '' || $mlsNumber === '') {
            throw new \InvalidArgumentException('mls_number is required, plus contact_id or showing_record_id.');
        }

        $externalKey = GhlMlsSyncTask::makeExternalKey($contactId, $mlsNumber, $showingRecordId);

        return DB::transaction(function () use ($contactId, $mlsNumber, $locationId, $externalKey, $payload, $showingRecordId) {
            /** @var GhlMlsSyncTask|null $existing */
            $existing = GhlMlsSyncTask::query()
                ->where('external_key', $externalKey)
                ->lockForUpdate()
                ->first();

            if (! $existing && $showingRecordId) {
                $existing = GhlMlsSyncTask::query()
                    ->where('showing_record_id', $showingRecordId)
                    ->where('mls_number', $mlsNumber)
                    ->lockForUpdate()
                    ->first();
            }

            if ($existing) {
                $sameShowing = $showingRecordId !== null
                    && strtolower(trim((string) $existing->showing_record_id)) === $showingRecordId;
                $sameMls = strtoupper((string) $existing->mls_number) === $mlsNumber;
                // Keep hash when the same Showings row+MLS is re-notified (our own
                // field write must not clear skip_unchanged and loop the workflow).
                $keepHash = $sameShowing && $sameMls;

                $existing->fill([
                    'status' => GhlMlsSyncTask::STATUS_PENDING,
                    'contact_id' => $contactId,
                    'showing_record_id' => $showingRecordId ?: $existing->showing_record_id,
                    'location_id' => $locationId,
                    'last_error' => null,
                    'sync_hash' => $keepHash ? $existing->sync_hash : null,
                    'queued_at' => now(),
                    'started_at' => null,
                    'completed_at' => $keepHash ? $existing->completed_at : null,
                    'source_payload' => $payload !== [] ? $payload : $existing->source_payload,
                    'external_key' => $externalKey,
                ]);
                $existing->save();

                Log::info('GoHighLevel MLS pending task upserted', [
                    'id' => $existing->id,
                    'contact_id' => $contactId,
                    'mls' => $mlsNumber,
                    'showing_record_id' => $showingRecordId,
                    'status' => $existing->status,
                ]);

                return $existing->fresh() ?? $existing;
            }

            $task = GhlMlsSyncTask::query()->create([
                'contact_id' => $contactId,
                'mls_number' => $mlsNumber,
                'showing_record_id' => $showingRecordId,
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
                'showing_record_id' => $showingRecordId,
            ]);

            return $task;
        });
    }

    /**
     * Queue the actual Showings write. Webhooks must not wait until 05:15.
     */
    public function dispatchSyncJob(GhlMlsSyncTask $task): void
    {
        ProcessGhlMlsSyncTaskJob::dispatch((int) $task->id)
            ->onQueue(SerikQueue::ghl());
    }

    /**
     * Extract contact + MLS + optional Showings record id from GHL webhook / workflow payloads.
     *
     * @param  array<string, mixed>  $payload
     * @return array{contact_id: string, mls_number: string, location_id: ?string, showing_record_id: string}|null
     */
    public function extractFromWebhookPayload(array $payload): ?array
    {
        $wrapped = $payload['data'] ?? null;
        if (is_array($wrapped) && $wrapped !== []) {
            $payload = array_replace($wrapped, $payload);
        }

        $isShowingsEvent = $this->isShowingsObjectPayload($payload);
        $showingRecordId = $this->extractShowingRecordIdFromPayload($payload);

        $contactId = $this->extractContactIdHint($payload, $isShowingsEvent);

        $locationId = data_get($payload, 'locationId')
            ?? data_get($payload, 'location_id')
            ?? data_get($payload, 'contact.locationId')
            ?? data_get($payload, 'customData.location_id')
            ?? data_get($payload, 'data.locationId');

        $mls = '';
        if ($isShowingsEvent || $showingRecordId !== '') {
            $mls = $this->extractMlsFromShowingsProperties($payload);
        }

        if ($mls === '') {
            $mls = (string) (
                data_get($payload, 'mls_number')
                ?? data_get($payload, 'mlsNumber')
                ?? data_get($payload, 'MLS Number')
                ?? data_get($payload, 'MLS Number ')
                ?? data_get($payload, 'customData.mls_number')
                ?? data_get($payload, 'customData.mlsNumber')
                ?? data_get($payload, 'customData.MLS Number')
                ?? data_get($payload, 'data.mls_number')
                ?? ''
            );
        }

        // GHL Custom Webhook often uses the custom-field id as the JSON/form key.
        if (trim($mls) === '') {
            $mlsFieldId = $this->resolveMlsFieldId();
            if ($mlsFieldId) {
                $mls = $this->stringifyFieldValue(
                    data_get($payload, $mlsFieldId)
                    ?? data_get($payload, 'customData.' . $mlsFieldId)
                    ?? data_get($payload, 'data.' . $mlsFieldId)
                    ?? data_get($payload, 'customFields.' . $mlsFieldId)
                );
            }
        }

        if ($mls === '') {
            // GHL workflows sometimes use arbitrary custom-data key labels containing "mls".
            foreach ($payload as $key => $value) {
                if (! is_string($key) || stripos($key, 'mls') === false) {
                    continue;
                }
                if (is_array($value)) {
                    continue;
                }
                $candidate = $this->stringifyFieldValue($value);
                if ($candidate !== '') {
                    $mls = $candidate;
                    break;
                }
            }
        }

        if ($mls === '' && ! $isShowingsEvent) {
            $mls = $this->extractMlsFromCustomFields($payload);
        }

        $contactId = trim($contactId);
        $mls = strtoupper(trim($mls));

        if ($mls === '') {
            return null;
        }

        if ($contactId === '') {
            $contactId = trim((string) (
                data_get($payload, 'full_name')
                ?? data_get($payload, 'fullName')
                ?? data_get($payload, 'contact_name')
                ?? data_get($payload, 'email')
                ?? ''
            ));
        }

        return [
            'contact_id' => $contactId,
            'mls_number' => $mls,
            'location_id' => $locationId ? (string) $locationId : null,
            'showing_record_id' => $showingRecordId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractShowingRecordIdFromPayload(array $payload): string
    {
        $wrapped = $payload['data'] ?? null;
        if (is_array($wrapped) && $wrapped !== []) {
            $payload = array_replace($wrapped, $payload);
        }

        $candidates = [
            data_get($payload, 'showing_record_id'),
            data_get($payload, 'showingRecordId'),
            data_get($payload, 'showing_id'),
            data_get($payload, 'showingId'),
            data_get($payload, 'record_id'),
            data_get($payload, 'recordId'),
            data_get($payload, 'record.id'),
            data_get($payload, 'custom_objects.showings.id'),
            data_get($payload, 'customData.showing_record_id'),
            data_get($payload, 'customData.showingRecordId'),
            data_get($payload, 'customData.record_id'),
            data_get($payload, 'customData.recordId'),
        ];

        if ($this->isShowingsObjectPayload($payload)) {
            $candidates[] = data_get($payload, 'id');
            $candidates[] = data_get($payload, 'data.id');
        }

        foreach ($candidates as $raw) {
            $id = trim($this->stringifyFieldValue($raw));
            if ($id !== '' && $this->looksLikeGhlRecordId($id)) {
                return $id;
            }
        }

        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $norm = strtolower(str_replace([' ', '-'], '_', $key));
            if (! in_array($norm, [
                'showing_record_id', 'showingrecordid', 'showing_id', 'showingid',
                'record_id', 'recordid',
            ], true)) {
                continue;
            }
            $id = trim($this->stringifyFieldValue($value));
            if ($id !== '' && $this->looksLikeGhlRecordId($id)) {
                return $id;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function isShowingsObjectPayload(array $payload): bool
    {
        $objectKey = strtolower(trim((string) (
            data_get($payload, 'objectKey')
            ?? data_get($payload, 'object_key')
            ?? data_get($payload, 'schemaKey')
            ?? data_get($payload, 'schema_key')
            ?? data_get($payload, 'customData.objectKey')
            ?? ''
        )));
        if ($objectKey !== '' && str_contains($objectKey, 'showing')) {
            return true;
        }

        $type = strtolower(trim((string) (data_get($payload, 'type') ?? data_get($payload, 'event') ?? '')));
        if ($type !== '' && (str_contains($type, 'showing') || (str_contains($type, 'object') && str_contains($type, 'record')))) {
            return true;
        }

        foreach ([
            'showing_record_id', 'showingRecordId', 'showing_id', 'showingId',
            'custom_objects.showings.id', 'custom_objects.showings.mls_number',
        ] as $key) {
            if (data_get($payload, $key)) {
                return true;
            }
        }

        $props = data_get($payload, 'properties');
        if (is_array($props) && (
            array_key_exists('mls_number', $props)
            || array_key_exists('custom_objects.showings.mls_number', $props)
        )) {
            return true;
        }

        return false;
    }

    /**
     * Resolve MLS from a GHL contact payload (API GET /contacts/{id} or webhook body).
     * GHL commonly returns customFields as {id, value} without fieldKey.
     *
     * @param  array<string, mixed>  $contact
     */
    public function extractMlsFromContact(array $contact): string
    {
        $direct = (string) (
            data_get($contact, 'mls_number')
            ?? data_get($contact, 'mlsNumber')
            ?? ''
        );
        if (trim($direct) !== '') {
            return strtoupper(trim($direct));
        }

        return strtoupper(trim($this->extractMlsFromCustomFields($contact)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractMlsFromCustomFields(array $payload): string
    {
        $candidates = [
            data_get($payload, 'customFields'),
            data_get($payload, 'contact.customFields'),
            data_get($payload, 'data.customFields'),
            data_get($payload, 'customField'),
            data_get($payload, 'attribs'),
            data_get($payload, 'contact.attribs'),
        ];

        $mlsKey = (string) config('gohighlevel.mls_sync.mls_field_key', 'contact.mls_number');
        $mlsFieldId = $this->resolveMlsFieldId();

        foreach ($candidates as $fields) {
            if (! is_array($fields)) {
                continue;
            }

            // Associative map: key => value
            if (array_is_list($fields) === false) {
                foreach ([$mlsKey, 'mls_number', 'MLS Number', 'MLS Number ', $mlsFieldId] as $mapKey) {
                    if ($mapKey && array_key_exists($mapKey, $fields)) {
                        $mapped = $this->stringifyFieldValue($fields[$mapKey]);
                        if ($mapped !== '') {
                            return $mapped;
                        }
                    }
                }
            }

            foreach ($fields as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $key = (string) ($field['key'] ?? $field['fieldKey'] ?? '');
                $id = (string) ($field['id'] ?? '');
                $name = strtolower(trim((string) ($field['name'] ?? '')));

                $matchesKey = $key !== '' && ($key === $mlsKey || str_ends_with($key, 'mls_number'));
                $matchesId = $mlsFieldId !== null && $id !== '' && hash_equals($mlsFieldId, $id);
                $matchesName = $name === 'mls number' || $name === 'mls';

                if (! $matchesKey && ! $matchesId && ! $matchesName) {
                    continue;
                }

                $value = $this->stringifyFieldValue(
                    $field['field_value']
                    ?? $field['value']
                    ?? $field['fieldValue']
                    ?? $field['fieldValueString']
                    ?? $field['field_value_string']
                    ?? null
                );
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractMlsFromShowingsProperties(array $payload): string
    {
        $props = data_get($payload, 'properties');
        if (is_array($props)) {
            $fromProps = $this->stringifyFieldValue(
                $props['mls_number']
                ?? $props['custom_objects.showings.mls_number']
                ?? $props['MLS Number']
                ?? null
            );
            if ($fromProps !== '') {
                return $fromProps;
            }
        }

        return $this->stringifyFieldValue(
            data_get($payload, 'custom_objects.showings.mls_number')
            ?? data_get($payload, 'customData.custom_objects.showings.mls_number')
            ?? data_get($payload, 'mls_number')
            ?? data_get($payload, 'customData.mls_number')
            ?? ''
        );
    }

    protected function looksLikeGhlRecordId(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, ' ') || str_contains($value, '@')) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9_-]{12,64}$/', $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractContactIdHint(array $payload, bool $isShowingsEvent = false): string
    {
        $raw = data_get($payload, 'contact_id')
            ?? data_get($payload, 'contactId')
            ?? data_get($payload, 'Contact ID')
            ?? data_get($payload, 'Contact Id')
            ?? data_get($payload, 'Contact_ID')
            ?? data_get($payload, 'contact.id')
            ?? data_get($payload, 'associatedContactId')
            ?? data_get($payload, 'associated_contact_id')
            ?? data_get($payload, 'customData.contact_id')
            ?? data_get($payload, 'customData.contactId')
            ?? data_get($payload, 'customData.Contact ID')
            ?? data_get($payload, 'data.contact_id')
            ?? data_get($payload, 'data.contactId')
            ?? data_get($payload, 'data.contact.id')
            ?? '';

        // ContactCreate/ContactUpdate use top-level id as the contact id.
        // Showings object events use top-level id as the Showings record id.
        if ($raw === '' || $raw === null) {
            if (! $isShowingsEvent) {
                $raw = data_get($payload, 'data.id') ?? data_get($payload, 'id') ?? '';
            }
        }

        $hint = trim($this->stringifyFieldValue($raw));
        if ($hint !== '') {
            return $hint;
        }

        // PHP parse_str / form-urlencoded converts "Contact ID" → "Contact_ID".
        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $norm = strtolower(str_replace([' ', '-'], '_', $key));
            if (! in_array($norm, ['contact_id', 'contactid'], true)) {
                continue;
            }
            $candidate = trim($this->stringifyFieldValue($value));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * GHL ContactUpdate / Contacts API often omit fieldKey and send only custom field id.
     */
    public function resolveMlsFieldId(): ?string
    {
        $configured = trim((string) config('gohighlevel.mls_sync.mls_field_id', ''));
        if ($configured !== '') {
            return $configured;
        }

        try {
            $mlsKey = (string) config('gohighlevel.mls_sync.mls_field_key', 'contact.mls_number');
            $ids = app(GoHighLevelShowingFieldMapper::class)->fieldIdMap();
            $id = trim((string) ($ids[$mlsKey] ?? ''));

            return $id !== '' ? $id : null;
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel MLS field id resolve failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function stringifyFieldValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            // GHL sometimes wraps TEXT values as a one-element array.
            $first = $value[0] ?? null;
            if (is_scalar($first)) {
                return trim((string) $first);
            }

            return trim(implode(' ', array_map(
                static fn ($v) => is_scalar($v) ? (string) $v : '',
                $value
            )));
        }
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }
}
