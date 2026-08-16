<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTrebArchiveImportJob;
use App\Services\Treb\TrebArchiveImportService;
use App\Support\SerikQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Lightweight TREB Archive (AUTH2) sold import — isolated from existing TREB/VOW crons.
 *
 * Default (no --sync): dispatch ProcessTrebArchiveImportJob onto the imports queue.
 * --sync: run inline (manual ops / debugging). Scheduler must never use --sync.
 */
class TrebArchiveImportCommand extends Command
{
    protected $signature = 'serik:treb-archive-import
        {--batch= : Rows to fetch per AMP page (10-500; defaults to config)}
        {--pages= : Max AMP pages per run/job (defaults to config)}
        {--max-seconds= : Soft time budget seconds (defaults to config)}
        {--dry-run : Fetch without DB writes / progress advance}
        {--reset : Reset archive progress to the start of the window}
        {--status : Show progress only}
        {--health : Show health metrics JSON}
        {--benchmark : Show benchmark estimates JSON}
        {--sync : Run inline in this process (do not use from scheduler)}
        {--dispatch : Force queue dispatch (default when not --sync)}';

    protected $description = 'Import TREB Archive sold data via TREB_AUTH2 (queue-first, resumable, bulk upsert)';

    public function handle(TrebArchiveImportService $service): int
    {
        if ($this->option('status')) {
            $progress = $service->readProgress();
            // Monitoring only: feed progress samples for throughput/ETA (no import side effects).
            app(\App\Services\Treb\TrebArchiveHealthMonitor::class)->sampleProgress($progress);
            $this->line(json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($this->option('health') || $this->option('benchmark')) {
            return $this->call('serik:treb-archive-health', [
                '--benchmark' => (bool) $this->option('benchmark'),
            ]);
        }

        if (! config('treb.archive.enabled', true)) {
            $this->warn('TREB archive import is disabled (TREB_ARCHIVE_IMPORT_ENABLED=false).');

            return self::SUCCESS;
        }

        $batch = $this->option('batch');
        $pages = $this->option('pages');
        $maxSeconds = $this->option('max-seconds');
        $batchSize = $batch !== null && $batch !== '' ? (int) $batch : null;
        $maxPages = $pages !== null && $pages !== '' ? (int) $pages : null;
        $maxSecs = $maxSeconds !== null && $maxSeconds !== '' ? (int) $maxSeconds : null;
        $dryRun = (bool) $this->option('dry-run');
        $reset = (bool) $this->option('reset');
        $sync = (bool) $this->option('sync');

        if (! $sync) {
            $maxParallel = max(1, (int) config('treb.archive.max_parallel_jobs', 4));
            $count = $reset ? 1 : $maxParallel;
            for ($i = 0; $i < $count; $i++) {
                ProcessTrebArchiveImportJob::dispatch(
                    reset: $reset && $i === 0,
                    dryRun: $dryRun,
                    batchSize: $batchSize,
                    maxPages: $maxPages,
                    maxSeconds: $maxSecs,
                )->onQueue(SerikQueue::imports());
            }

            $this->info(sprintf(
                'Dispatched %d× ProcessTrebArchiveImportJob on queue [%s] (batch=%s pages=%s parallel=%s).',
                $count,
                SerikQueue::imports(),
                $batchSize ?? 'config',
                $maxPages ?? 'config',
                config('treb.archive.parallel_enabled', true) ? 'on' : 'off',
            ));

            return self::SUCCESS;
        }

        $lockSeconds = max(60, (int) config('treb.archive.lock_seconds', 180));
        $lock = Cache::lock(TrebArchiveImportService::LOCK_KEY, $lockSeconds);
        if (! $lock->get()) {
            $this->warn('Archive import already running; skipping this tick.');

            return self::SUCCESS;
        }

        try {
            $result = $service->run(
                batchSize: $batchSize,
                dryRun: $dryRun,
                reset: $reset,
                maxPages: $maxPages ?? 1,
                maxSeconds: $maxSecs,
            );
        } catch (Throwable $e) {
            $this->error('Archive import failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }

        if (! empty($result['skipped']) && ($result['reason'] ?? '') === 'missing_treb_auth2') {
            $this->warn('TREB_AUTH2 is not set — archive import idle.');

            return self::SUCCESS;
        }

        if (! empty($result['idle'])) {
            $this->info('Archive import complete; idle until --reset.');

            return self::SUCCESS;
        }

        if (! ($result['ok'] ?? false)) {
            $this->error('Archive batch error: ' . ($result['error'] ?? 'unknown'));

            // Keep scheduler/CLI healthy — soft failure (progress retained).
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Archive OK — pages=%d fetched=%d imported=%d updated=%d skipped=%d api=%dms db=%dms elapsed=%dms',
            (int) ($result['pages'] ?? 0),
            (int) ($result['fetched'] ?? 0),
            (int) ($result['imported'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            (int) ($result['api_ms'] ?? 0),
            (int) ($result['db_ms'] ?? 0),
            (int) ($result['elapsed_ms'] ?? 0),
        ));

        $progress = $result['progress'] ?? [];
        if (! empty($progress['completed'])) {
            $this->info('Archive window finished.');
        } else {
            $this->line(sprintf(
                'Next: year=%s skip=%s has_more=%s',
                (string) ($progress['year'] ?? '?'),
                (string) ($progress['skip'] ?? '?'),
                ! empty($result['has_more']) ? 'yes' : 'no',
            ));
        }

        return self::SUCCESS;
    }
}
