<?php

namespace App\Jobs;

use App\Services\GoHighLevel\GoHighLevelHttpClient;
use App\Services\GoHighLevel\GoHighLevelMetrics;
use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use App\Support\SerikQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Resolve MLS from a GHL contact (when webhook did not include the value)
 * and create a pending sync task — no TREB/GHL write in this job.
 */
class EnqueueGhlMlsFromContactJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    public int $timeout = 60;

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
        return 'ghl-enqueue-' . md5(strtolower(trim($this->contactId)) . '|' . strtoupper(trim((string) $this->mlsNumber)));
    }

    public function handle(GoHighLevelMlsPendingService $pending, GoHighLevelHttpClient $http): void
    {
        $mls = strtoupper(trim((string) $this->mlsNumber));

        if ($mls === '') {
            if (! $http->enabled()) {
                return;
            }

            $data = $http->get('/contacts/' . $this->contactId);
            $contact = data_get($data, 'contact', $data);
            if (! is_array($contact)) {
                $contact = is_array($data) ? $data : [];
            }

            // GHL Contacts API returns customFields as {id, value} without fieldKey.
            $mls = $pending->extractMlsFromContact($contact);
        }

        if ($mls === '') {
            Log::channel('ghl_sync')->info('GoHighLevel enqueue skipped: contact has no MLS number', [
                'contact_id' => $this->contactId,
            ]);

            return;
        }

        $pending->enqueue($this->contactId, $mls, $this->locationId, $this->payload);
        GoHighLevelMetrics::incrDay('tasks_enqueued');
    }
}
