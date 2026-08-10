<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/**
 * Production hardening: config/event/view caches + optional latency probe.
 * Does NOT run route:cache (routes/web.php uses closures).
 */
class SerikProductionOptimizeCommand extends Command
{
    protected $signature = 'serik:production:optimize
        {--bench : Probe local latency for / /map and report JSON}
        {--base= : Base URL override (default APP_URL)}';

    protected $description = 'Safe production optimize (config/view/event cache) + optional latency bench';

    public function handle(): int
    {
        if (! app()->environment('production') && ! $this->option('bench')) {
            $this->warn('Not production — running caches anyway (safe). Prefer APP_ENV=production on live.');
        }

        // route:cache intentionally skipped — closures in routes/web.php.
        Artisan::call('config:cache');
        $this->line(trim(Artisan::output()));
        Artisan::call('event:cache');
        $this->line(trim(Artisan::output()));
        Artisan::call('view:cache');
        $this->line(trim(Artisan::output()));

        if (config('serik.cache.warm_dispatch_on_optimize', true)) {
            Artisan::call('serik:cache:warm', ['--dispatch' => true, '--light' => true]);
            $this->line(trim(Artisan::output()) ?: 'Dispatched light cache warm on cache-refresh.');
        }

        if ($this->option('bench')) {
            $this->bench();
        }

        $this->info('Done. Reminder: CACHE_STORE=redis (Memurai), Meilisearch, OPcache, NSSM workers on production.');

        return self::SUCCESS;
    }

    protected function bench(): void
    {
        $base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');
        $paths = ['/', '/map', '/blogs'];
        $rows = [];

        foreach ($paths as $path) {
            $url = $base . $path;
            $samples = [];
            for ($i = 0; $i < 3; $i++) {
                $t0 = microtime(true);
                try {
                    $res = Http::timeout(20)->withHeaders([
                        'Accept' => 'text/html',
                        'User-Agent' => 'SerikBench/1.0',
                    ])->get($url);
                    $ms = round((microtime(true) - $t0) * 1000, 1);
                    $samples[] = [
                        'ms' => $ms,
                        'status' => $res->status(),
                        'bytes' => strlen($res->body()),
                        'cache' => $res->header('X-Serik-Homepage-Cache') ?: null,
                        'hsts' => $res->header('Strict-Transport-Security') ? 'yes' : 'no',
                        'csp' => $res->header('Content-Security-Policy') ? 'yes' : 'no',
                    ];
                } catch (\Throwable $e) {
                    $samples[] = [
                        'ms' => round((microtime(true) - $t0) * 1000, 1),
                        'error' => $e->getMessage(),
                    ];
                }
            }
            $ok = array_values(array_filter($samples, fn ($s) => isset($s['ms']) && ! isset($s['error'])));
            $avg = $ok !== [] ? round(array_sum(array_column($ok, 'ms')) / count($ok), 1) : null;
            $rows[] = [
                'path' => $path,
                'avg_ms' => $avg,
                'samples' => $samples,
            ];
        }

        $this->line(json_encode(['base' => $base, 'results' => $rows], JSON_PRETTY_PRINT));
    }
}
