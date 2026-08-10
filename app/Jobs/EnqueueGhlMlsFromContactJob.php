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
            $mlsKey = (string) config('gohighlevel.mls_sync.mls_field_key', 'contact.mls_number');

            foreach ((array) data_get($contact, 'customFields', []) as $field) {
                if (! is_array($field)) {
                    continue;
                }
                $key = (string) ($field['key'] ?? $field['fieldKey'] ?? '');
                if ($key === $mlsKey || str_ends_with($key, 'mls_number')) {
                    $mls = strtoupper(trim((string) ($field['value'] ?? $field['field_value'] ?? $field['fieldValue'] ?? '')));
                    break;
                }
            }

            // Some payloads expose customField as associative map
            if ($mls === '') {
                $map = data_get($contact, 'customField');
                if (is_array($map)) {
                    $mls = strtoupper(trim((string) ($map[$mlsKey] ?? $map['mls_number'] ?? '')));
                }
            }
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
