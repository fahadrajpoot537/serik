<?php

namespace App\Console\Commands;

use App\Support\HomepageCacheWarmer;
use Illuminate\Console\Command;

class WarmHomepageCacheCommand extends Command
{
    protected $signature = 'serik:cache:warm-homepage
        {--locale= : Locale to warm (defaults to app locale)}
        {--json : Output JSON only}';

    protected $description = 'Pre-warm homepage HTML and data caches after deploy or cache flush';

    public function handle(): int
    {
        $locale = $this->option('locale');
        $locale = is_string($locale) && $locale !== '' ? $locale : null;

        $timings = HomepageCacheWarmer::warm($locale);
        $totalMs = round(array_sum(array_column($timings, 'ms')), 2);

        if ($this->option('json')) {
            $this->line(json_encode([
                'total_ms' => $totalMs,
                'steps' => $timings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Homepage caches warmed in ' . $totalMs . ' ms');

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
