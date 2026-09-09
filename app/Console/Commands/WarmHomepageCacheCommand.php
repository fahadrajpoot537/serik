<?php

namespace App\Console\Commands;

use App\Support\HomepageCacheWarmer;
use Illuminate\Console\Command;

class WarmHomepageCacheCommand extends Command
{
    protected $signature = 'serik:cache:warm-homepage
        {--locale= : Locale to warm (defaults to app locale)}
        {--keys-only : Refresh data/fragment cache keys; skip full HTML render when already warm}
        {--json : Output JSON only}';

    protected $description = 'Pre-warm homepage HTML and data caches after deploy or cache flush';

    public function handle(): int
    {
        $locale = $this->option('locale');
        $locale = is_string($locale) && $locale !== '' ? $locale : null;
        $keysOnly = (bool) $this->option('keys-only');

        $timings = HomepageCacheWarmer::warm($locale, $keysOnly);
        $totalMs = round(array_sum(array_column($timings, 'ms')), 2);

        if ($this->option('json')) {
            $this->line(json_encode([
                'total_ms' => $totalMs,
                'keys_only' => $keysOnly,
                'steps' => $timings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Homepage caches warmed in ' . $totalMs . ' ms' . ($keysOnly ? ' (keys-only)' : ''));

        $this->table(
            ['Step', 'ms', 'Detail'],
            array_map(static fn (array $row): array => [
                $row['step'],
                $row['ms'],
                $row['detail'],
            ], $timings)
        );

        return self::SUCCESS;
    }
}
