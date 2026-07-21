<?php

namespace App\Providers;

use App\Support\CanonicalUrl;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Botble\Theme\Supports\SiteMapManager::class, \App\Support\SerikSiteMapManager::class);

        $this->app->singleton(\App\Services\Geocoding\GeocodingManager::class);
        $this->app->bind(
            \App\Contracts\GeocodingProviderInterface::class,
            fn ($app) => $app->make(\App\Services\Geocoding\GeocodingManager::class)->driver()
        );

        // Eagerly resolve translator after providers are registered so deferred
        // binding cannot race during AJAX shortcode / Blade rendering on IIS.
        $this->app->booting(function (): void {
            \App\Support\EnsuresTranslator::ensure();
            self::ensureWritableLoggingOrFallback();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Support\EnsuresTranslator::ensure();
        self::ensureWritableLoggingOrFallback();

        CanonicalUrl::forceApplicationUrl();

        add_filter('core_seo_canonical', function (string $url): string {
            return CanonicalUrl::normalize($url);
        }, 999);

        $rewriteLegacyMediaUrls = static function (?string $html): string {
            if (! is_string($html) || $html === '') {
                return (string) $html;
            }

            $origin = CanonicalUrl::origin();

            $html = preg_replace(
                '#https?://[^"\']*mytemp\.website/storage/#i',
                $origin . '/storage/',
                $html
            ) ?? $html;

            return preg_replace(
                '#(["\'])storage/([^"\']+)#i',
                '$1' . $origin . '/storage/$2',
                $html
            ) ?? $html;
        };

        add_filter(THEME_FRONT_HEADER, $rewriteLegacyMediaUrls, 999);
        add_filter(THEME_FRONT_FOOTER, $rewriteLegacyMediaUrls, 999);
        add_filter(THEME_FRONT_BODY, $rewriteLegacyMediaUrls, 999);
        add_filter('theme_logo_image', static function ($html) use ($rewriteLegacyMediaUrls) {
            return $rewriteLegacyMediaUrls((string) $html);
        }, 999);

        add_filter(MENU_FILTER_NODE_URL, static function (?string $url): ?string {
            if (! is_string($url) || $url === '') {
                return $url;
            }

            return \App\Support\MenuUrl::resolve($url);
        }, 1200);
    }

    protected static function ensureWritableLoggingOrFallback(): void
    {
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
            app()->forgetInstance('log');
            \Illuminate\Support\Facades\Log::clearResolvedInstances();
        } catch (\Throwable) {
            config(['logging.default' => 'errorlog']);
            app()->forgetInstance('log');
            \Illuminate\Support\Facades\Log::clearResolvedInstances();
        }
    }
}
