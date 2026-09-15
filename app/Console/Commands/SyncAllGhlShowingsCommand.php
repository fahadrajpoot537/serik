<?php

namespace App\Console\Commands;

use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use App\Services\GoHighLevel\GoHighLevelShowingObjectRepository;
use App\Services\GoHighLevel\GoHighLevelShowingSyncService;
use Illuminate\Console\Command;

/**
 * Pull every GHL Showings row that has an MLS number and push Serik property data.
 * No need to type each MLS by hand.
 *
 *   php artisan serik:ghl:sync-all-showings --dry-run
 *   php artisan serik:ghl:sync-all-showings --only-empty --sync --limit=50
 */
class SyncAllGhlShowingsCommand extends Command
{
    protected $signature = 'serik:ghl:sync-all-showings
        {--dry-run : List matching Showings only (no GHL writes)}
        {--only-empty : Skip rows that already have address + price filled}
        {--sync : Process inline (otherwise enqueue pending + dispatch job)}
        {--limit=200 : Max Showings records to process}
        {--page-size=50 : GHL search page size}';

    protected $description = 'Bulk-sync all GHL Showings records that have an MLS number';

    public function handle(
        GoHighLevelShowingObjectRepository $objects,
        GoHighLevelMlsPendingService $pending,
        GoHighLevelShowingSyncService $sync,
    ): int {
        if (! config('services.gohighlevel.enabled')) {
            $this->error('GoHighLevel is disabled.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $pageSize = max(1, min(100, (int) $this->option('page-size')));
        $onlyEmpty = (bool) $this->option('only-empty');
        $dryRun = (bool) $this->option('dry-run');
        $inline = (bool) $this->option('sync');

        $this->info('Fetching Showings from GHL…');
        $records = $objects->listAllRecords($limit, $pageSize);
        $this->line('Fetched ' . count($records) . ' Showings record(s).');

        $ok = 0;
        $fail = 0;
        $skip = 0;
        $queued = 0;

        foreach ($records as $record) {
            $recordId = trim((string) ($record['id'] ?? ''));
            $props = (array) ($record['properties'] ?? []);
            $mls = $this->extractMls($props, $objects->objectKey());

            if ($recordId === '' || $mls === '') {
                $skip++;
                continue;
            }

            if ($onlyEmpty && $this->looksFilled($props, $objects->objectKey())) {
                $this->line("SKIP filled {$mls} ({$recordId})");
                $skip++;
                continue;
            }

            if ($dryRun) {
                $addr = (string) ($props['address'] ?? $props[$objects->objectKey() . '.address'] ?? '');
                $this->line("DRY {$mls} record={$recordId} address=" . ($addr !== '' ? $addr : '(empty)'));
                $queued++;
                continue;
            }

            try {
                $task = $pending->enqueue('', $mls, null, [], $recordId);
                if ($inline) {
                    $sync->processTask($task);
                    $this->line("OK {$mls} #{$task->id}");
                    $ok++;
                } else {
                    $pending->dispatchSyncJob($task);
                    $this->line("QUEUED {$mls} #{$task->id}");
                    $queued++;
                }
            } catch (\Throwable $e) {
                $fail++;
                $this->error("FAIL {$mls} ({$recordId}): " . $e->getMessage());
                if (isset($task)) {
                    try {
                        $sync->markFailed($task, $e);
                    } catch (\Throwable) {
                        // ignore secondary failure
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Done: ok={$ok} queued={$queued} fail={$fail} skip={$skip}");
        if (! $dryRun && ! $inline && $queued > 0) {
            $this->comment('Process queue: php artisan queue:work --queue=ghl --stop-when-empty');
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function extractMls(array $props, string $objectKey): string
    {
        $raw = (string) (
            $props['mls_number']
            ?? $props[$objectKey . '.mls_number']
            ?? $props['MLS Number']
            ?? ''
        );
        $mls = strtoupper(trim($raw));
        if ($mls === '') {
            return '';
        }

        // TREB-style: letter + digits (C13751240, W12984840, …)
        if (! preg_match('/^[A-Z]\d{5,}$/', $mls)) {
            return '';
        }

        return $mls;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function looksFilled(array $props, string $objectKey): bool
    {
        $address = trim((string) ($props['address'] ?? $props[$objectKey . '.address'] ?? ''));
        $price = trim((string) ($props['price'] ?? $props[$objectKey . '.price'] ?? ''));

        return $address !== '' && $price !== '';
    }
}
