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
 *   php artisan serik:ghl:sync-all-showings --missing-commission --sync --limit=200
 *   php artisan serik:ghl:sync-all-showings --missing-listing-status --sync --limit=200
 *   php artisan serik:ghl:sync-all-showings --missing-type --sync --limit=200
 */
class SyncAllGhlShowingsCommand extends Command
{
    protected $signature = 'serik:ghl:sync-all-showings
        {--dry-run : List matching Showings only (no GHL writes)}
        {--only-empty : Skip rows that already have address + price filled}
        {--missing-commission : Only rows that have MLS but empty commission}
        {--missing-listing-status : Only rows that have MLS but empty listing_status}
        {--missing-type : Only rows that have MLS but empty type}
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
        $missingCommission = (bool) $this->option('missing-commission');
        $missingListingStatus = (bool) $this->option('missing-listing-status');
        $missingType = (bool) $this->option('missing-type');
        $dryRun = (bool) $this->option('dry-run');
        $inline = (bool) $this->option('sync');
        $objectKey = $objects->objectKey();

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
            $mls = $this->extractMls($props, $objectKey);

            if ($recordId === '' || $mls === '') {
                $skip++;
                continue;
            }

            if ($missingType) {
                if (! $this->typeEmpty($props, $objectKey)) {
                    $this->line("SKIP has type {$mls} ({$recordId})");
                    $skip++;
                    continue;
                }
            } elseif ($missingListingStatus) {
                if (! $this->listingStatusEmpty($props, $objectKey)) {
                    $this->line("SKIP has listing_status {$mls} ({$recordId})");
                    $skip++;
                    continue;
                }
            } elseif ($missingCommission) {
                if (! $this->commissionEmpty($props, $objectKey)) {
                    $this->line("SKIP has commission {$mls} ({$recordId})");
                    $skip++;
                    continue;
                }
            } elseif ($onlyEmpty && $this->looksFilled($props, $objectKey)) {
                $this->line("SKIP filled {$mls} ({$recordId})");
                $skip++;
                continue;
            }

            if ($dryRun) {
                $addr = $this->propString($props, ['address', $objectKey . '.address']);
                $comm = $this->propString($props, ['commission', $objectKey . '.commission']);
                $ls = $this->propString($props, ['listing_status', $objectKey . '.listing_status']);
                $type = $this->propString($props, ['type', $objectKey . '.type']);
                $this->line(
                    "DRY {$mls} record={$recordId} address=" . ($addr !== '' ? $addr : '(empty)')
                    . ' type=' . ($type !== '' ? $type : '(empty)')
                    . ' commission=' . ($comm !== '' ? $comm : '(empty)')
                    . ' listing_status=' . ($ls !== '' ? $ls : '(empty)')
                );
                $queued++;
                continue;
            }

            try {
                $task = $pending->enqueue('', $mls, null, [], $recordId);
                // Force remap so newly resolved commission / listing_status / type write.
                if ($missingCommission || $missingListingStatus || $missingType || $task->sync_hash) {
                    $task->sync_hash = null;
                    $task->save();
                }
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
            $this->comment('Process queue: php artisan queue:work database --queue=ghl --stop-when-empty');
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function extractMls(array $props, string $objectKey): string
    {
        $raw = $this->propString($props, [
            'mls_number',
            $objectKey . '.mls_number',
            'MLS Number',
        ]);
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
        $address = $this->propString($props, ['address', $objectKey . '.address']);
        $price = $this->propString($props, ['price', $objectKey . '.price']);

        return $address !== '' && $price !== '';
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function commissionEmpty(array $props, string $objectKey): bool
    {
        return $this->propString($props, ['commission', $objectKey . '.commission']) === '';
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function listingStatusEmpty(array $props, string $objectKey): bool
    {
        return $this->propString($props, ['listing_status', $objectKey . '.listing_status']) === '';
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function typeEmpty(array $props, string $objectKey): bool
    {
        return $this->propString($props, ['type', $objectKey . '.type']) === '';
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  list<string>  $keys
     */
    private function propString(array $props, array $keys): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $props)) {
                continue;
            }
            $value = $props[$key];
            if (is_array($value)) {
                // GHL sometimes returns {value: "..."} or multi-select arrays.
                $value = $value['value'] ?? $value['label'] ?? reset($value);
            }
            if (is_bool($value) || is_numeric($value)) {
                return trim((string) $value);
            }
            if (is_string($value)) {
                return trim($value);
            }
        }

        return '';
    }
}
