<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Geocoding\GeocodingManager::class);
        $this->app->bind(
            \App\Contracts\GeocodingProviderInterface::class,
            fn ($app) => $app->make(\App\Services\Geocoding\GeocodingManager::class)->driver()
        );

        // Eagerly resolve translator after providers are registered so deferred
        // binding cannot race during AJAX shortcode / Blade rendering on IIS.
        $this->app->booting(function (): void {
            \App\Support\EnsuresTranslator::ensure();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Support\EnsuresTranslator::ensure();

        // If storage/logs is not writable (common on IIS), fall back to PHP error_log
        // so Monolog never throws UnexpectedValueException during page renders.
        try {
            $logDir = storage_path('logs');
            if (! is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $probe = $logDir . DIRECTORY_SEPARATOR . '.write_probe';
            $ok = @file_put_contents($probe, (string) time()) !== false;
            if ($ok) {
                @unlink($probe);
            } else {
                config(['logging.default' => 'errorlog']);
                $this->app->forgetInstance('log');
                \Illuminate\Support\Facades\Log::clearResolvedInstances();
            }
        } catch (\Throwable) {
            config(['logging.default' => 'errorlog']);
            $this->app->forgetInstance('log');
            \Illuminate\Support\Facades\Log::clearResolvedInstances();
        }
    }
}
