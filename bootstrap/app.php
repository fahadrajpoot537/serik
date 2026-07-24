<?php

use App\Jobs\RunArtisanOnLowQueueJob;
use App\Support\SerikQueue;
use App\Support\SerikScheduler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        \App\Console\Commands\SerikQueueStatusCommand::class,
        \App\Console\Commands\SerikQueueInstallImagesWorkerCommand::class,
    ])
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

        $dispatchLow = function (string $command, array $arguments = [], bool $requireLightLoad = true) {
            return function () use ($command, $arguments, $requireLightLoad) {
                try {
                    if ($requireLightLoad && ! SerikScheduler::shouldDispatchHeavyLow()) {
                        Log::debug('[schedule] skipped heavy LOW dispatch', [
                            'command' => $command,
                            'low_depth' => SerikScheduler::lowQueueDepth(),
                        ]);

                        return 0;
                    }

                    RunArtisanOnLowQueueJob::dispatch($command, $arguments)
                        ->onQueue(SerikQueue::low());
                } catch (\Throwable $e) {
                    Log::error('[schedule] LOW dispatch failed: ' . $command . ' — ' . $e->getMessage());
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

        // D) Meili catch-up for recent actives — LOW queue only (never block schedule:run).
        $schedule->call($dispatchLow('serik:search-index-recent', [
            '--days' => 3,
            '--limit' => (int) config('serik.scheduler.search_index_recent_limit', 300),
        ]))
            ->name('serik-search-index-recent-dispatch')
            ->everyThirtyMinutes()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-search-index.log'));

        // Historical TREB import — LOW queue slices (was blocking schedule:run up to 4 min).
        $schedule->call($dispatchLow('serik:import-historical', [
            '--resume' => true,
            '--max-runtime' => (int) config('serik.scheduler.import_historical_max_runtime', 180),
        ]))
            ->name('serik-import-historical-dispatch')
            ->hourly()
            ->withoutOverlapping(15)
            ->appendOutputTo(storage_path('logs/treb-historical.log'));

        // E) Heavy maintenance → LOW queue (scheduler only dispatches)
        $schedule->call($dispatchLow('serik:catch-up', [
            '--from-year' => 2000,
            '--hours' => 2,
            '--resume' => true,
            '--skip-existing' => true,
            '--no-geocode' => true,
        ]))
            ->name('serik-catch-up-dispatch')
            ->everyTwoHours()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-catch-up.log'));

        $schedule->call($dispatchLow('serik:import-amp-gaps', [
            '--resume' => true,
            '--page' => 80,
            '--max-runtime' => 90,
        ]))
            ->name('serik-amp-gaps-dispatch')
            ->hourly()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-amp-gaps.log'));

        $schedule->call($dispatchLow('serik:fix-slugs', [
            '--limit' => 5000,
        ]))
            ->name('serik-fix-slugs-dispatch')
            ->hourly()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-fix-slugs.log'));

        $schedule->call($dispatchLow('serik:geocode-borrow', [
            '--limit' => 300,
            '--active-days' => 14,
        ]))
            ->name('serik-geocode-borrow-dispatch')
            ->dailyAt('01:45')
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-geocode.log'));

        $schedule->call($dispatchLow('serik:backfill-property-images', [
            '--limit' => 200,
        ], requireLightLoad: true))
            ->name('serik-backfill-property-images')
            ->everyFiveMinutes()
            ->withoutOverlapping(15)
            ->appendOutputTo(storage_path('logs/treb-images.log'));

        $schedule->call($dispatchLow('serik:reconcile', [
            '--days' => 7,
            '--fix-coords' => true,
        ]))
            ->name('serik-reconcile-dispatch')
            ->dailyAt('03:30')
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/treb-reconcile.log'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\ForceCanonicalDomainMiddleware::class);
        $middleware->prepend(\App\Http\Middleware\BlockSensitivePathsMiddleware::class);
        $middleware->prepend(\App\Http\Middleware\WagesMaintenanceMiddleware::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\GeoBlockMiddleware::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\RequestProfilerMiddleware::class);
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

    \App\Support\SerikLogging::ensureWritableOrFallback($app);
});

$app->booted(function () use ($app): void {
    $app->make('view')->composer('packages/theme::partials.header', function (): void {
        \App\Support\SerikSeo::apply();
    });

    add_filter('shortcode_should_skip_lazy_loading', static function (bool $skip, string $name, $compiled): bool {
        return $skip || \App\Support\SerikHomepage::shouldServerRenderShortcode($name, $compiled);
    }, 10, 3);

    add_filter('theme_preloader', static function (?string $html): ?string {
        if (\App\Support\SerikHomepage::isHomepageRequest()) {
            return null;
        }

        return $html;
    }, 1000);

    if (class_exists(\Botble\RealEstate\Models\Property::class) && ! defined('SERIK_PROPERTY_OBSERVER_REGISTERED')) {
        \Botble\RealEstate\Models\Property::observe(\App\Observers\PropertyHomepageCacheObserver::class);
        define('SERIK_PROPERTY_OBSERVER_REGISTERED', true);
    }
});

return $app;
