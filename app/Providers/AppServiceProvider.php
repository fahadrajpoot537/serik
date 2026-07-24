<?php

namespace App\Providers;

use App\Support\CanonicalUrl;
use App\Support\ImageAlt;
use App\Support\SerikSeo;
use App\Support\SerikLogging;
use App\Support\TrustBadgeImageAlt;
use Illuminate\Foundation\Application;
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

        if (defined('BASE_ACTION_PUBLIC_RENDER_SINGLE')) {
            add_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, function (string $screen, object $data): void {
                SerikSeo::applyForModel($screen, $data);
            }, 9999, 2);
        }

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

        add_filter('core_seo_canonical', function (string $url): string {
            return CanonicalUrl::normalize($url);
        }, 999);

        $this->registerBotbleHooks();

        add_filter('core_media_image', static function ($html, $url, $alt = null, $attributes = [], $secure = false) {
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
        }, 20, 4);

        add_filter('core_media_image', static function ($html, ?string $url = null, $alt = null, array $attributes = [], $secure = false) {
            if (! \App\Support\SerikHomepage::isHomepageRequest()) {
                return $html;
            }

            $eager = ($attributes['fetchpriority'] ?? null) === 'high'
                || ($attributes['loading'] ?? null) === 'eager'
                || ($attributes['data-bb-lazy'] ?? null) === 'false';

            if (! $eager || ! is_string($url) || $url === '') {
                return $html;
            }

            $markup = $html instanceof HtmlString ? $html->toHtml() : (string) $html;

            if (str_contains($markup, 'data-src=')) {
                $markup = preg_replace('/\ssrc=(["\'])[^"\']*\1/', ' src="' . e($url) . '"', $markup, 1) ?? $markup;
                $markup = preg_replace('/\sdata-src=(["\'])[^"\']*\1/', '', $markup) ?? $markup;
                $markup = str_replace('data-bb-lazy="true"', 'data-bb-lazy="false"', $markup);
                $markup = str_replace("loading=\"lazy\"", 'loading="eager"', $markup);

                return new HtmlString($markup);
            }

            return $html;
        }, 125, 4);
    }

    protected function registerBotbleHooks(): void
    {
        if (! defined('THEME_FRONT_HEADER')) {
            $this->app->booted(function (): void {
                $this->registerBotbleThemeHooks();
            });

            return;
        }

        $this->registerBotbleThemeHooks();
    }

    protected function registerBotbleThemeHooks(): void
    {
        if (! defined('THEME_FRONT_HEADER')) {
            return;
        }

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
            $markup = $rewriteLegacyMediaUrls($html instanceof HtmlString ? $html->toHtml() : (string) $html);

            return new HtmlString($markup);
        }, 999);

        if (defined('PAGE_FILTER_FRONT_PAGE_CONTENT')) {
            add_filter(PAGE_FILTER_FRONT_PAGE_CONTENT, static function (?string $html): ?string {
                if (! is_string($html) || $html === '') {
                    return $html;
                }

                return TrustBadgeImageAlt::apply($html);
            }, 1200);
        }

        if (defined('MENU_FILTER_NODE_URL')) {
            add_filter(MENU_FILTER_NODE_URL, static function (?string $url): ?string {
                if (! is_string($url) || $url === '') {
                    return $url;
                }

                return \App\Support\MenuUrl::resolve($url);
            }, 1200);
        }
    }

    protected static function ensureWritableLoggingOrFallback(): void
    {
        if (! app()->bound(Application::class)) {
            return;
        }

        SerikLogging::ensureWritableOrFallback(app());
    }

    protected static function isEmailQueueJob(string $displayName): bool
    {
        $needles = [
            'SendContactEmailListener',
            'SendEmailNotificationAboutNewSubscriberListener',
            'ResetPasswordNotification',
            'ConfirmEmailNotification',
            'SendAccountPinEmailJob',
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
