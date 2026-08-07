<?php

namespace App\Console\Commands;

use App\Services\Treb\TrebArchiveImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Lightweight TREB Archive (AUTH2) sold import — isolated from existing TREB/VOW crons.
 */
class TrebArchiveImportCommand extends Command
{
    protected $signature = 'serik:treb-archive-import
        {--batch=40 : Rows to fetch per execution (10-100)}
        {--dry-run : Fetch and advance nothing / no DB writes}
        {--reset : Reset archive progress to the start of the 14-year window}
        {--status : Show progress only}';

    protected $description = 'Import TREB Archive sold data via TREB_AUTH2 in small resumable batches (does not affect existing TREB sync)';

    public function handle(TrebArchiveImportService $service): int
    {
        if ($this->option('status')) {
            $progress = $service->readProgress();
            $this->line(json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $lock = Cache::lock(TrebArchiveImportService::LOCK_KEY, 120);
        if (! $lock->get()) {
            $this->warn('Archive import already running; skipping this tick.');

            return self::SUCCESS;
        }

        try {
            $result = $service->run(
                batchSize: (int) $this->option('batch'),
                dryRun: (bool) $this->option('dry-run'),
                reset: (bool) $this->option('reset'),
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

            // Keep scheduler healthy — soft failure.
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Archive batch OK — fetched=%d imported=%d updated=%d skipped=%d elapsed=%dms',
            (int) ($result['fetched'] ?? 0),
            (int) ($result['imported'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            (int) ($result['elapsed_ms'] ?? 0),
        ));

        $progress = $result['progress'] ?? [];
        if (! empty($progress['completed'])) {
            $this->info('14-year archive window finished.');
        } else {
            $this->line(sprintf(
                'Next: year=%s skip=%s',
                (string) ($progress['year'] ?? '?'),
                (string) ($progress['skip'] ?? '?'),
            ));
        }

        return self::SUCCESS;
    }
}
