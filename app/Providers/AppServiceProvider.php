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
    }
}
