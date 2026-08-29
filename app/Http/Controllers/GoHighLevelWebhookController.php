<?php

namespace App\Http\Controllers;

use App\Jobs\EnqueueGhlMlsFromContactJob;
use App\Services\GoHighLevel\GoHighLevelContactResolver;
use App\Services\GoHighLevel\GoHighLevelMetrics;
use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use App\Services\GoHighLevel\GoHighLevelWebhookGuard;
use App\Support\SerikQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Inbound GoHighLevel webhooks / workflow hooks.
 * Never runs TREB or Showings sync inline — only queues pending work on ghl.
 */
class GoHighLevelWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        GoHighLevelMlsPendingService $pending,
        GoHighLevelWebhookGuard $guard,
    ): JsonResponse {
        $t0 = microtime(true);
        $correlationId = GoHighLevelMetrics::correlationId(
            $request->header('X-Request-Id') ?: $request->header('X-Correlation-Id')
        );
        $request->attributes->set('ghl_correlation_id', $correlationId);

        if (! $guard->authorize($request) || ! $guard->timestampFresh($request)) {
            GoHighLevelMetrics::incrDay('webhook_unauthorized');
            Log::channel('ghl_sync')->warning('GoHighLevel webhook unauthorized', [
                'correlation_id' => $correlationId,
                'ip' => $request->ip(),
                'method' => $request->method(),
            ]);

            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401)
                ->header('X-Serik-Correlation-Id', $correlationId);
        }

        $payload = $this->normalizeWebhookPayload($request);
        GoHighLevelMetrics::incrDay('webhook_received');
        Log::channel('ghl_sync')->info('GoHighLevel webhook received', [
            'correlation_id' => $correlationId,
            'method' => $request->method(),
            'content_type' => (string) $request->header('Content-Type', ''),
            'body_len' => strlen($request->getContent()),
            'keys' => array_keys($payload),
            'query_keys' => array_keys($request->query()),
        ]);

        $extracted = $pending->extractFromWebhookPayload($payload);

        // Workflow custom-data often maps contact_id → Full Name (not the GHL id).
        // Still accept when MLS is present; EnqueueGhlMlsFromContactJob resolves the real id.
        if ($extracted) {
            $showingId = trim((string) ($extracted['showing_record_id'] ?? ''));
            $idemKey = $showingId !== ''
                ? ('showing:' . $showingId)
                : ($extracted['contact_id'] !== ''
                    ? $extracted['contact_id']
                    : ('mls:' . $extracted['mls_number']));

            if (! $guard->claimIdempotency($idemKey, $extracted['mls_number'], $correlationId)) {
                GoHighLevelMetrics::observeLatency('webhook_latency', (microtime(true) - $t0) * 1000);

                // Same public contract as a successful accept (GHL retries stay happy).
                return response()->json([
                    'ok' => true,
                    'queued' => true,
                    'mode' => 'pending',
                ], 202)->header('X-Serik-Correlation-Id', $correlationId);
            }

            // Persist the pending row now so the 05:15 processor has work even if
            // the ghl worker is down (EnqueueGhlMlsFromContactJob would otherwise sit).
            $this->persistPendingFromExtracted($pending, $extracted, $payload);

            // Fast path: MLS present — also queue contact resolve / upsert (no TREB/GHL write).
            EnqueueGhlMlsFromContactJob::dispatch(
                $extracted['contact_id'],
                $extracted['mls_number'],
                $extracted['location_id'],
                $payload,
            )->onQueue(SerikQueue::ghl());

            GoHighLevelMetrics::incrDay('webhook_accepted');
            GoHighLevelMetrics::incrDay('tasks_enqueued');
            GoHighLevelMetrics::observeLatency('webhook_latency', (microtime(true) - $t0) * 1000);

            Log::channel('ghl_sync')->info('GoHighLevel webhook accepted MLS pending enqueue', [
                'correlation_id' => $correlationId,
                'contact_hint_present' => $extracted['contact_id'] !== '',
                'showing_record_id' => $extracted['showing_record_id'] !== '' ? $extracted['showing_record_id'] : null,
                'mls' => $extracted['mls_number'],
                'content_type' => (string) $request->header('Content-Type', ''),
                'body_len' => strlen($request->getContent()),
            ]);

            return response()->json([
                'ok' => true,
                'queued' => true,
                'mode' => 'pending',
            ], 202)->header('X-Serik-Correlation-Id', $correlationId);
        }

        $contactId = trim((string) (
            data_get($payload, 'contact_id')
            ?? data_get($payload, 'contactId')
            ?? data_get($payload, 'id')
            ?? data_get($payload, 'contact.id')
            ?? ''
        ));

        if ($contactId === '') {
            Log::channel('ghl_sync')->info('GoHighLevel webhook ignored: no contact/MLS', [
                'correlation_id' => $correlationId,
                'keys' => array_keys($payload),
                'custom_field_ids' => $this->payloadCustomFieldIds($payload),
                'content_type' => (string) $request->header('Content-Type', ''),
                'body_len' => strlen($request->getContent()),
            ]);
            GoHighLevelMetrics::incrDay('webhook_ignored_no_mls');

            return response()->json(['ok' => true, 'queued' => false, 'reason' => 'no_mls'], 200)
                ->header('X-Serik-Correlation-Id', $correlationId);
        }

        if (! $guard->claimIdempotency($contactId, null, $correlationId)) {
            GoHighLevelMetrics::observeLatency('webhook_latency', (microtime(true) - $t0) * 1000);

            return response()->json([
                'ok' => true,
                'queued' => true,
                'mode' => 'resolve_contact',
            ], 202)->header('X-Serik-Correlation-Id', $correlationId);
        }

        // Contact event without MLS in body — resolve on ghl queue, still no sync.
        EnqueueGhlMlsFromContactJob::dispatch(
            $contactId,
            null,
            data_get($payload, 'locationId') ? (string) data_get($payload, 'locationId') : null,
            $payload,
        )->onQueue(SerikQueue::ghl());

        GoHighLevelMetrics::incrDay('webhook_accepted');
        GoHighLevelMetrics::incrDay('tasks_enqueued');
        GoHighLevelMetrics::observeLatency('webhook_latency', (microtime(true) - $t0) * 1000);

        Log::channel('ghl_sync')->info('GoHighLevel webhook queued contact MLS resolve', [
            'correlation_id' => $correlationId,
            'contact_id' => $contactId,
            'custom_field_ids' => $this->payloadCustomFieldIds($payload),
        ]);

        return response()->json([
            'ok' => true,
            'queued' => true,
            'mode' => 'resolve_contact',
        ], 202)->header('X-Serik-Correlation-Id', $correlationId);
    }

    /**
     * GHL Workflow "Custom Webhook" often posts JSON with Content-Type text/plain
     * (or missing). Laravel then leaves $request->all() empty and MLS extraction fails.
     *
     * @return array<string, mixed>
     */
    protected function normalizeWebhookPayload(Request $request): array
    {
        $payload = $request->all();
        if (! is_array($payload)) {
            $payload = [];
        }

        $raw = trim((string) $request->getContent());
        if ($raw !== '' && ($payload === [] || ! $this->payloadLooksUseful($payload))) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = array_replace($payload, $decoded);
            } elseif (str_contains($raw, '=')) {
                $parsed = [];
                parse_str($raw, $parsed);
                if (is_array($parsed) && $parsed !== []) {
                    $payload = array_replace($payload, $parsed);
                }
            }
        }

        $query = $request->query();
        if (is_array($query) && $query !== []) {
            $payload = array_replace($query, $payload);
        }

        $wrapped = $payload['data'] ?? null;
        if (is_array($wrapped) && $wrapped !== []) {
            $payload = array_replace($wrapped, $payload);
        }

        // customData may arrive as a JSON string.
        $customData = $payload['customData'] ?? null;
        if (is_string($customData) && $customData !== '') {
            $decodedCustom = json_decode($customData, true);
            if (is_array($decodedCustom)) {
                $payload['customData'] = $decodedCustom;
                $payload = array_replace($decodedCustom, $payload);
            }
        } elseif (is_array($customData)) {
            $payload = array_replace($customData, $payload);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function payloadLooksUseful(array $payload): bool
    {
        foreach ([
            'mls_number', 'mlsNumber', 'MLS Number', 'contact_id', 'contactId', 'Contact ID',
            'id', 'customData', 'customFields', 'contact', 'data',
            'showing_record_id', 'showingRecordId', 'record_id', 'recordId',
            'objectKey', 'object_key', 'properties',
        ] as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }

        $mlsFieldId = trim((string) config('gohighlevel.mls_sync.mls_field_id', ''));
        if ($mlsFieldId !== '' && array_key_exists($mlsFieldId, $payload)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    protected function payloadCustomFieldIds(array $payload): array
    {
        $ids = [];
        foreach ([
            data_get($payload, 'customFields'),
            data_get($payload, 'contact.customFields'),
        ] as $fields) {
            if (! is_array($fields)) {
                continue;
            }
            foreach ($fields as $field) {
                if (is_array($field) && ! empty($field['id'])) {
                    $ids[] = (string) $field['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Persist immediately when MLS is present and we already know the Showings
     * record id and/or a real GHL contact id. Name/email hints still wait for
     * EnqueueGhlMlsFromContactJob.
     *
     * @param  array{contact_id: string, mls_number: string, location_id: ?string, showing_record_id?: string}  $extracted
     * @param  array<string, mixed>  $payload
     */
    protected function persistPendingFromExtracted(
        GoHighLevelMlsPendingService $pending,
        array $extracted,
        array $payload,
    ): void {
        $showingId = trim((string) ($extracted['showing_record_id'] ?? ''));
        $contactId = trim($extracted['contact_id']);
        $validContact = $contactId !== ''
            && app(GoHighLevelContactResolver::class)->looksLikeGhlContactId($contactId)
            && ($showingId === '' || $contactId !== $showingId);

        if ($showingId === '' && ! $validContact) {
            return;
        }

        try {
            $task = $pending->enqueue(
                $validContact ? $contactId : $showingId,
                $extracted['mls_number'],
                $extracted['location_id'],
                $payload,
                $showingId !== '' ? $showingId : null,
            );
            $pending->dispatchSyncJob($task);
        } catch (\Throwable $e) {
            Log::channel('ghl_sync')->warning('GoHighLevel webhook inline pending failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
