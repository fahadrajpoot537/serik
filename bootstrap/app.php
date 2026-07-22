<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        /*
        | Dual-priority queues — scheduler only DISPATCHES (<2s each).
        | HIGH worker: SyncLiveJob + GeocodePropertyJob + SyncPropertyHistoryJob
        | LOW worker:  GeocodeBacklogPropertyJob (+ optional maintenance)
        |
        | Do NOT schedule serik:geocode-all or queue:work here.
        | Task Scheduler: php artisan schedule:run every 1 minute.
        */

        $safe = function (string $command, array $arguments = []) {
            return function () use ($command, $arguments) {
                try {
                    @set_time_limit(30);
                    Artisan::call($command, $arguments);
                } catch (\Throwable $e) {
                    Log::error('[schedule-safe] ' . $command . ' failed: ' . $e->getMessage());
                }

                return 0;
            };
        };

        // A) HIGH — live AMP import / geocode / history (worker does the work)
        $schedule->call($safe('serik:sync-live:dispatch'))
            ->name('serik-sync-live-dispatch')
            ->everyMinute()
            ->withoutOverlapping(2)
            ->appendOutputTo(storage_path('logs/treb-sync-live.log'));

        // B) LOW — backlog geocode dispatcher (adaptive; pauses when HIGH is busy)
        $schedule->call($safe('serik:backlog:dispatch'))
            ->name('serik-backlog-dispatch')
            ->everyMinute()
            ->withoutOverlapping(2)
            ->appendOutputTo(storage_path('logs/treb-geocode-backlog.log'));

        // C) Stuck / failed recovery (lightweight SQL only)
        $schedule->call($safe('serik:geocode:reset-stuck'))
            ->name('serik-geocode-reset-stuck')
            ->everyFiveMinutes()
            ->withoutOverlapping(5)
            ->appendOutputTo(storage_path('logs/treb-geocode-backlog.log'));

        $schedule->call($safe('serik:geocode:retry-failed'))
            ->name('serik-geocode-retry-failed')
            ->hourly()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-geocode-backlog.log'));

        // D) Meili catch-up for recent actives (bounded)
        $schedule->call($safe('serik:search-index-recent', [
            '--days' => 3,
            '--limit' => 2000,
        ]))
            ->name('serik-search-index-recent')
            ->everyTenMinutes()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-search-index.log'));

        // Historical TREB import (TRREB_AUTH1 archive) — resumable slices.
        $schedule->call($safe('serik:import-historical', [
            '--resume' => true,
            '--max-runtime' => 240,
        ]))
            ->name('serik-import-historical')
            ->hourly()
            ->withoutOverlapping(15)
            ->appendOutputTo(storage_path('logs/treb-historical.log'));

        // E) Heavy maintenance → LOW queue (scheduler only dispatches)
        $schedule->call(function () {
            try {
                \App\Jobs\RunArtisanOnLowQueueJob::dispatch(
                    'serik:catch-up',
                    [
                        '--from-year' => 2000,
                        '--hours' => 2,
                        '--resume' => true,
                        '--skip-existing' => true,
                        '--no-geocode' => true,
                    ]
                )->onQueue(\App\Support\SerikQueue::low());
            } catch (\Throwable $e) {
                Log::error('[schedule] catch-up dispatch failed: ' . $e->getMessage());
            }

            return 0;
        })
            ->name('serik-catch-up-dispatch')
            ->everyTwoHours()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-catch-up.log'));

        $schedule->call(function () {
            try {
                \App\Jobs\RunArtisanOnLowQueueJob::dispatch(
                    'serik:import-amp-gaps',
                    [
                        '--resume' => true,
                        '--page' => 80,
                        '--max-runtime' => 90,
                    ]
                )->onQueue(\App\Support\SerikQueue::low());
            } catch (\Throwable $e) {
                Log::error('[schedule] amp-gaps dispatch failed: ' . $e->getMessage());
            }

            return 0;
        })
            ->name('serik-amp-gaps-dispatch')
            ->hourly()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-amp-gaps.log'));

        $schedule->call(function () {
            try {
                \App\Jobs\RunArtisanOnLowQueueJob::dispatch(
                    'serik:fix-slugs',
                    ['--limit' => 5000]
                )->onQueue(\App\Support\SerikQueue::low());
            } catch (\Throwable $e) {
                Log::error('[schedule] fix-slugs dispatch failed: ' . $e->getMessage());
            }

            return 0;
        })
            ->name('serik-fix-slugs-dispatch')
            ->hourly()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-fix-slugs.log'));

        $schedule->call(function () {
            try {
                \App\Jobs\RunArtisanOnLowQueueJob::dispatch(
                    'serik:geocode-borrow',
                    [
                        '--limit' => 300,
                        '--active-days' => 14,
                    ]
                )->onQueue(\App\Support\SerikQueue::low());
            } catch (\Throwable $e) {
                Log::error('[schedule] geocode-borrow dispatch failed: ' . $e->getMessage());
            }

            return 0;
        })
            ->name('serik-geocode-borrow-dispatch')
            ->dailyAt('01:45')
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-geocode.log'));

        $schedule->call(function () {
            try {
                \App\Jobs\RunArtisanOnLowQueueJob::dispatch(
                    'serik:reconcile',
                    [
                        '--days' => 7,
                        '--fix-coords' => true,
                    ]
                )->onQueue(\App\Support\SerikQueue::low());
            } catch (\Throwable $e) {
                Log::error('[schedule] reconcile dispatch failed: ' . $e->getMessage());
            }

            return 0;
        })
            ->name('serik-reconcile-dispatch')
            ->dailyAt('03:30')
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-reconcile.log'));

        // F) TREB image WebP backfill — resumes checkpoint, runs on LOW queue worker.
        $schedule->call(function () {
            try {
                \App\Jobs\RunArtisanOnLowQueueJob::dispatch(
                    'serik:treb-images-webp',
                    [
                        '--chunk' => 100,
                        '--gallery' => true,
                        '--max-runtime' => 3300,
                    ]
                )->onQueue(\App\Support\SerikQueue::low());
            } catch (\Throwable $e) {
                Log::error('[schedule] treb-images-webp dispatch failed: ' . $e->getMessage());
            }

            return 0;
        })
            ->name('serik-treb-images-webp-dispatch')
            ->everyTenMinutes()
            ->withoutOverlapping(60)
            ->appendOutputTo(storage_path('logs/treb-images-webp.log'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\ForceCanonicalDomainMiddleware::class);
        $middleware->prepend(\App\Http\Middleware\BlockSensitivePathsMiddleware::class);
        $middleware->prepend(\App\Http\Middleware\WagesMaintenanceMiddleware::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\GeoBlockMiddleware::class);
        $middleware->prependToGroup('web', \App\Http\Middleware\UseRequestRootUrlInLocal::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

// Before any provider boots: if storage/logs is locked on IIS, use error_log
// so Log:: / report() never throw UnexpectedValueException on public pages.
$app->booting(function () use ($app): void {
    $helper = $app->basePath('app/helpers/image_alt.php');
    if (is_file($helper)) {
        require_once $helper;
    }

    $app->singleton(
        \Botble\Theme\Supports\SiteMapManager::class,
        \App\Support\SerikSiteMapManager::class
    );

    try {
        $logDir = storage_path('logs');
        if (! is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $probe = $logDir . DIRECTORY_SEPARATOR . '.write_probe';
        $ok = @file_put_contents($probe, (string) time()) !== false;
        if ($ok) {
            @unlink($probe);

            return;
        }

        config(['logging.default' => 'errorlog']);
        $app->forgetInstance('log');
        \Illuminate\Support\Facades\Log::clearResolvedInstances();
    } catch (\Throwable) {
        config(['logging.default' => 'errorlog']);
        $app->forgetInstance('log');
        \Illuminate\Support\Facades\Log::clearResolvedInstances();
    }
});

return $app;
