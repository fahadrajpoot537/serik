<?php

namespace App\Jobs;

use App\Services\GoHighLevel\GoHighLevelContactResolver;
use App\Services\GoHighLevel\GoHighLevelHttpClient;
use App\Services\GoHighLevel\GoHighLevelMetrics;
use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use App\Support\SerikQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Resolve MLS + real GHL Contact ID from a webhook hint (name/email/id),
 * then create a pending sync task — no TREB/GHL write in this job.
 */
class EnqueueGhlMlsFromContactJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    public int $timeout = 90;

    public int $uniqueFor = 300;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $contactId,
        public ?string $mlsNumber = null,
        public ?string $locationId = null,
        public array $payload = [],
    ) {
        $this->onQueue(SerikQueue::ghl());
    }

    public function uniqueId(): string
    {
        $showing = '';
        if ($this->payload !== []) {
            $showing = (string) (
                $this->payload['showing_record_id']
                ?? $this->payload['showingRecordId']
                ?? $this->payload['record_id']
                ?? $this->payload['recordId']
                ?? ''
            );
        }
        if (trim($showing) !== '') {
            return 'ghl-enqueue-showing-' . md5(strtolower(trim($showing)) . '|' . strtoupper(trim((string) $this->mlsNumber)));
        }

        return 'ghl-enqueue-' . md5(strtolower(trim($this->contactId)) . '|' . strtoupper(trim((string) $this->mlsNumber)));
    }

    public function handle(
        GoHighLevelMlsPendingService $pending,
        GoHighLevelHttpClient $http,
        GoHighLevelContactResolver $resolver,
    ): void {
        if (! $http->enabled()) {
            return;
        }

        $mls = strtoupper(trim((string) $this->mlsNumber));
        $hint = trim($this->contactId);
        $showingId = '';

        if ($this->payload !== []) {
            $fromPayload = $pending->extractFromWebhookPayload($this->payload);
            if ($fromPayload) {
                $showingId = trim((string) ($fromPayload['showing_record_id'] ?? ''));
                if ($mls === '') {
                    $mls = $fromPayload['mls_number'];
                }
                if ($hint === '') {
                    $hint = trim($fromPayload['contact_id']);
                }
            }
        }

        // Showings Custom Object webhook: MLS + record id are enough.
        // Do not require (or look up) a Contact MLS field.
        if ($showingId !== '' && $mls !== '') {
            $resolvedId = null;
            if ($hint !== '' && $resolver->looksLikeGhlContactId($hint) && $hint !== $showingId) {
                $resolvedId = $resolver->resolve($hint, null, $this->payload);
            }

            $task = $pending->enqueue(
                $resolvedId ?: $showingId,
                $mls,
                $this->locationId,
                $this->payload,
                $showingId,
            );
            $pending->dispatchSyncJob($task);
            GoHighLevelMetrics::incrDay('tasks_enqueued');

            return;
        }

        // Prefer MLS already present in the webhook body; otherwise load from contact later.
        if ($mls === '' && $hint !== '' && $resolver->looksLikeGhlContactId($hint)) {
            try {
                $data = $http->get('/contacts/' . $hint);
                $contact = data_get($data, 'contact', $data);
                if (! is_array($contact)) {
                    $contact = is_array($data) ? $data : [];
                }
                $mls = $pending->extractMlsFromContact($contact);
            } catch (\Throwable $e) {
                Log::channel('ghl_sync')->info('GoHighLevel enqueue: direct contact fetch failed (will resolve)', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($mls === '' && $this->payload !== []) {
            $fromPayload = $pending->extractFromWebhookPayload(array_merge($this->payload, [
                'contact_id' => $hint !== '' ? $hint : 'pending',
            ]));
            if ($fromPayload) {
                $mls = $fromPayload['mls_number'];
            } else {
                $mls = strtoupper(trim($pending->extractMlsFromContact($this->payload)));
            }
        }

        $resolvedId = $resolver->resolve($hint, $mls !== '' ? $mls : null, $this->payload);
        if (! $resolvedId) {
            Log::channel('ghl_sync')->warning('GoHighLevel enqueue skipped: could not resolve contact id', [
                'hint_present' => $hint !== '',
                'mls' => $mls !== '' ? $mls : null,
            ]);

            return;
        }

        if ($mls === '') {
            $data = $http->get('/contacts/' . $resolvedId);
            $contact = data_get($data, 'contact', $data);
            if (! is_array($contact)) {
                $contact = is_array($data) ? $data : [];
            }
            $mls = $pending->extractMlsFromContact($contact);
        }

        if ($mls === '') {
            Log::channel('ghl_sync')->info('GoHighLevel enqueue skipped: contact has no MLS number', [
                'contact_id' => $resolvedId,
            ]);

            return;
        }

        $task = $pending->enqueue($resolvedId, $mls, $this->locationId, $this->payload);
        $pending->dispatchSyncJob($task);
        GoHighLevelMetrics::incrDay('tasks_enqueued');
    }
}
