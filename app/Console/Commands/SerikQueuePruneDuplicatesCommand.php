<?php

namespace App\Console\Commands;

use App\Support\SerikQueueJobHygiene;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SerikQueuePruneDuplicatesCommand extends Command
{
    protected $signature = 'serik:queue:prune-duplicates
        {--dry-run : Report without deleting}
        {--class= : Job displayName substring (default: all configured rules)}
        {--queue= : Optional queue name filter}
        {--keep=1 : Number of newest matching jobs to retain}';

    protected $description = 'Prune duplicate queued jobs (keeps newest N per rule)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $class = trim((string) $this->option('class'));
        $queue = trim((string) $this->option('queue'));
        $keep = max(0, (int) $this->option('keep'));

        if (! \Illuminate\Support\Facades\Schema::hasTable('jobs')) {
            $this->warn('jobs table missing — nothing to prune.');

            return self::SUCCESS;
        }

        $this->line('jobs_total=' . DB::table('jobs')->count());

        if ($class !== '') {
            $result = SerikQueueJobHygiene::pruneDuplicates(
                $class,
                $queue !== '' ? $queue : null,
                $keep,
                $dryRun
            );
            $this->table(['Metric', 'Value'], [
                ['matched', (string) ($result['matched'] ?? 0)],
                ['deleted', (string) ($result['deleted'] ?? 0)],
                ['kept_ids', implode(',', $result['kept_ids'] ?? [])],
                ['dry_run', $dryRun ? 'yes' : 'no'],
            ]);

            return self::SUCCESS;
        }

        $report = SerikQueueJobHygiene::healConfiguredDuplicates($dryRun);
        foreach ($report as $key => $stats) {
            if (isset($stats['error'])) {
                $this->warn("{$key}: {$stats['error']}");

                continue;
            }
            $this->info(sprintf(
                '%s matched=%d deleted=%d kept=[%s]%s',
                $key,
                (int) ($stats['matched'] ?? 0),
                (int) ($stats['deleted'] ?? 0),
                implode(',', $stats['kept_ids'] ?? []),
                $dryRun ? ' (dry-run)' : ''
            ));
        }

        $purged = SerikQueueJobHygiene::purgeExceededAttempts(100);
        $this->line("purged_exceeded_attempts={$purged}");

        return self::SUCCESS;
    }
}
