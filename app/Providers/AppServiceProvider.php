<?php

namespace App\Providers;

use App\Support\CanonicalUrl;
use App\Support\ImageAlt;
use App\Support\SerikSeo;
use App\Support\TrustBadgeImageAlt;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once __DIR__ . '/../helpers/image_alt.php';

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

        add_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, function (string $screen, object $data): void {
            SerikSeo::applyForModel($screen, $data);
        }, 9999, 2);

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            $payload = $event->job->payload();
            $displayName = $payload['displayName'] ?? $event->job->resolveName();

            if (! self::isEmailQueueJob($displayName)) {
                return;
            }

            Log::error('[queue] Email job failed', [
                'job' => $displayName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
            ]);
        });

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

        add_filter(PAGE_FILTER_FRONT_PAGE_CONTENT, static function (?string $html): ?string {
            if (! is_string($html) || $html === '') {
                return $html;
            }

            return TrustBadgeImageAlt::apply($html);
        }, 1200);

        add_filter(MENU_FILTER_NODE_URL, static function (?string $url): ?string {
            if (! is_string($url) || $url === '') {
                return $url;
            }

            return \App\Support\MenuUrl::resolve($url);
        }, 1200);

        add_filter('core_media_image', static function ($html, $url, $alt, $attributes, $secure) {
            if (! is_string($url) || trim($url) === '') {
                return $html;
            }

            if (ImageAlt::clean((string) $alt) !== '') {
                return $html;
            }

            $resolved = ImageAlt::fromMediaPath($url);

            if ($resolved === '') {
                return $html;
            }

            $markup = $html instanceof HtmlString ? $html->toHtml() : (string) $html;

            if (preg_match('/\salt=(["\'])(.*?)\1/i', $markup)) {
                $markup = preg_replace(
                    '/\salt=(["\'])(.*?)\1/i',
                    ' alt="' . e($resolved) . '"',
                    $markup,
                    1
                ) ?? $markup;
            } else {
                $markup = Str::replaceFirst('<img ', '<img alt="' . e($resolved) . '" ', $markup);
            }

            return new HtmlString($markup);
        }, 20, 5);
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

    protected static function isEmailQueueJob(string $displayName): bool
    {
        $needles = [
            'SendContactEmailListener',
            'SendEmailNotificationAboutNewSubscriberListener',
            'ResetPasswordNotification',
            'ConfirmEmailNotification',
            'SendMailListener',
            'EmailHandler',
            'MailchimpContactListListener',
            'SendGridContactListListener',
        ];

        foreach ($needles as $needle) {
            if (str_contains($displayName, $needle)) {
                return true;
            }
        }

        return false;
    }
}
