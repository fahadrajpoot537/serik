<?php

namespace App\Http\Controllers;

use App\Jobs\EnqueueGhlMlsFromContactJob;
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
            ]);

            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401)
                ->header('X-Serik-Correlation-Id', $correlationId);
        }

        $payload = $request->all();
        $extracted = $pending->extractFromWebhookPayload($payload);

        if ($extracted) {
            if (! $guard->claimIdempotency($extracted['contact_id'], $extracted['mls_number'], $correlationId)) {
                GoHighLevelMetrics::observeLatency('webhook_latency', (microtime(true) - $t0) * 1000);

                // Same public contract as a successful accept (GHL retries stay happy).
                return response()->json([
                    'ok' => true,
                    'queued' => true,
                    'mode' => 'pending',
                ], 202)->header('X-Serik-Correlation-Id', $correlationId);
            }

            // Fast path: MLS present — create pending row then return (no TREB/GHL write).
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
                'contact_id' => $extracted['contact_id'],
                'mls' => $extracted['mls_number'],
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
            ]);

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

        return response()->json([
            'ok' => true,
            'queued' => true,
            'mode' => 'resolve_contact',
        ], 202)->header('X-Serik-Correlation-Id', $correlationId);
    }
}
