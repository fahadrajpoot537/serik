<?php

namespace App\Providers;

use App\Support\CanonicalUrl;
use App\Support\ImageAlt;
use App\Support\SerikSeo;
use App\Support\SerikLogging;
use App\Support\TrustBadgeImageAlt;
use App\Listeners\TrackSerikQueueMetrics;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
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

        if (class_exists(\Botble\Ads\Supports\AdsManager::class)) {
            $this->app->singleton(\Botble\Ads\Supports\AdsManager::class, \App\Support\SerikCachedAdsManager::class);
            $this->app->alias(\Botble\Ads\Supports\AdsManager::class, 'AdsManager');
        }

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
        self::disablePhpTimeLimitForQueueDaemons();

        CanonicalUrl::forceApplicationUrl();

        $this->registerAccountAuthCookieMarker();
        $this->registerQueueMetricsListeners();

        if (class_exists(\Botble\Contact\Events\SentContactEvent::class)) {
            Event::listen(
                \Botble\Contact\Events\SentContactEvent::class,
                \App\Listeners\ApplyMortgageCalculatorContactContext::class,
                250
            );
            Event::listen(
                \Botble\Contact\Events\SentContactEvent::class,
                \App\Listeners\ApplyServiceInquiryContactContext::class,
                240
            );
        }

        // New CMS uploads → WebP (TREB images use a separate proxy; untouched).
        // Skip Redis on console so Artisan maintenance does not hang when Memurai is down.
        if (! $this->app->runningInConsole()) {
            try {
                if (! \App\Support\SerikCache::get('serik_media_webp_enabled_v1') && ! (bool) setting('media_convert_image_to_webp', false)) {
                    setting()->set(['media_convert_image_to_webp' => '1'])->save();
                    \App\Support\SerikCache::forever('serik_media_webp_enabled_v1', 1);
                }
            } catch (\Throwable $e) {
                \App\Support\SerikSafeLog::write('debug', '[boot] media webp setting skipped', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        add_filter('core_media_image', function ($html, $url = null) {
            if (! is_string($html) || $html === '' || ! is_string($url) || $url === '') {
                return $html;
            }

            if (\App\Support\CmsWebp::isTrebPath($url)) {
                return $html;
            }

            $webp = \App\Support\CmsWebp::preferWebpUrl($url);
            if (! is_string($webp) || $webp === '' || $webp === $url) {
                return $html;
            }

            return str_replace($url, $webp, $html);
        }, 20, 2);

        if (defined('BASE_ACTION_PUBLIC_RENDER_SINGLE')) {
            add_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, function (string $screen, object $data): void {
                SerikSeo::applyForModel($screen, $data);

                if ($screen === PROPERTY_MODULE_SCREEN_NAME && $data instanceof \Botble\RealEstate\Models\Property) {
                    \Botble\SeoHelper\Facades\SeoHelper::meta()->addMeta('robots', 'noindex, follow');
                }
            }, 9999, 2);
        }

        // Belt-and-suspenders: ensure property detail HTML always has robots noindex.
        if (defined('THEME_FRONT_HEADER')) {
            add_filter(THEME_FRONT_HEADER, function (?string $header): ?string {
                $request = request();
                $slugPrefix = \Botble\Slug\Facades\SlugHelper::getPrefix(
                    \Botble\RealEstate\Models\Property::class,
                    'properties'
                ) ?: 'properties';

                if (! $request->is($slugPrefix . '/*') || $request->is($slugPrefix, $slugPrefix . '/map')) {
                    return $header;
                }

                if (is_string($header) && str_contains($header, 'name="robots"')) {
                    return $header;
                }

                return ($header ?? '') . '<meta name="robots" content="noindex, follow">' . "\n";
            }, 9999);
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

        if (defined('BASE_ACTION_PUBLIC_RENDER_SINGLE')) {
            add_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, static function (): void {
                if (\App\Support\SerikHomepage::isHomepageRequest()) {
                    \Botble\Theme\Facades\Theme::asset()->remove('ckeditor-content-styles');
                }
            }, 16);
        }

        if (class_exists(\Botble\Page\Models\Page::class)) {
            \Botble\Page\Models\Page::saved(static function (\Botble\Page\Models\Page $page): void {
                try {
                    if ((int) $page->id === (int) \Botble\Base\Facades\BaseHelper::getHomepageId()) {
                        \App\Support\HomepageResponseCache::bump();
                        \App\Support\HomepageFragmentCache::bumpAll();
                    }
                } catch (\Throwable) {
                    // ignore
                }
            });
        }

        if (class_exists(\Botble\Blog\Models\Post::class)) {
            $forgetBlogCache = static function (): void {
                foreach (['recent', 'featured', 'popular'] as $type) {
                    \App\Support\SerikCache::forget('serik_homepage_blog_posts_v1:' . $type . ':3');
                }

                \App\Support\HomepageFragmentCache::bump('shortcode:blog-posts');
                \App\Support\HomepageResponseCache::bump();
            };

            \Botble\Blog\Models\Post::saved($forgetBlogCache);
            \Botble\Blog\Models\Post::deleted($forgetBlogCache);
        }

        if (class_exists(\Botble\Ads\Models\Ads::class)) {
            $bustAds = static function (): void {
                \App\Support\SerikCachedAdsManager::bust();
            };

            \Botble\Ads\Models\Ads::saved($bustAds);
            \Botble\Ads\Models\Ads::deleted($bustAds);
        }

        if (class_exists(\Botble\Media\Models\MediaFile::class)) {
            \Botble\Media\Models\MediaFile::saved(static function (\Botble\Media\Models\MediaFile $file): void {
                if (is_string($file->url) && $file->url !== '') {
                    \App\Support\MediaFileLookupCache::forget($file->url);
                }
            });
        }
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

            if (preg_match('/<img\b/i', $markup) && ! preg_match('/\bwidth=/i', $markup)) {
                $markup = preg_replace(
                    '/<img\b/i',
                    '<img width="160" height="44" decoding="async" loading="eager" fetchpriority="high"',
                    $markup,
                    1
                ) ?? $markup;
            }

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

        // Full-page/anonymous HTML (homepage, Ontario SEO, bfcache) can still show
        // Login while the session is authenticated. Reload once on any front page.
        add_filter(THEME_FRONT_FOOTER, static function (?string $html): ?string {
            $script = <<<'HTML'
<script>
(function () {
    if (window.__serikAuthNavSync) return;
    window.__serikAuthNavSync = true;
    function serikAuthNavNeedsSync() {
        return !!document.querySelector('.js-auth-open-login, .js-auth-open-register');
    }
    function serikAuthNavSync(fromBfcache) {
        if (!serikAuthNavNeedsSync()) return;
        var key = 'serik_auth_nav_reloaded:' + location.pathname;
        if (!fromBfcache && sessionStorage.getItem(key) === '1') return;
        fetch('/api/v1/auth/session-status', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (!data || !data.logged_in) {
                sessionStorage.removeItem(key);
                return;
            }
            sessionStorage.setItem(key, '1');
            window.location.reload();
        }).catch(function () {});
    }
    serikAuthNavSync(false);
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) serikAuthNavSync(true);
    });
})();
</script>
HTML;

            return ($html ?? '') . $script;
        }, 1500);
    }

    /**
     * Lightweight cookie so EarlyHomepageCacheMiddleware can skip guest HTML
     * without waiting for StartSession (session login has no remember_* cookie).
     */
    protected function registerAccountAuthCookieMarker(): void
    {
        Event::listen(\Illuminate\Auth\Events\Login::class, static function ($event): void {
            if (($event->guard ?? '') !== 'account') {
                return;
            }

            cookie()->queue(cookie(
                'serik_acct',
                '1',
                60 * 24 * 45,
                '/',
                null,
                (bool) config('session.secure', false),
                true,
                false,
                config('session.same_site', 'lax')
            ));
        });

        Event::listen(\Illuminate\Auth\Events\Logout::class, static function ($event): void {
            if (($event->guard ?? '') !== 'account') {
                return;
            }

            cookie()->queue(cookie()->forget('serik_acct'));
            // Client sessionStorage keys are cleared on next auth-sync miss.
        });
    }

    /**
     * queue:work / queue:listen are long-lived CLI daemons.
     * This does NOT change IIS/FastCGI max_execution_time for HTTP requests.
     */
    protected static function disablePhpTimeLimitForQueueDaemons(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $argv = implode(' ', array_map('strval', $_SERVER['argv'] ?? []));
        if (! preg_match('/queue:(?:work|listen)\b/', $argv)) {
            return;
        }

        ini_set('max_execution_time', '0');
        set_time_limit(0);
    }

    protected function registerQueueMetricsListeners(): void
    {
        if (! config('serik.orchestration.enabled', true)) {
            return;
        }

        Event::listen(JobProcessing::class, [TrackSerikQueueMetrics::class, 'handleProcessing']);
        Event::listen(JobProcessed::class, [TrackSerikQueueMetrics::class, 'handleProcessed']);
        Event::listen(JobFailed::class, [TrackSerikQueueMetrics::class, 'handleFailed']);
        Event::listen(JobExceptionOccurred::class, [TrackSerikQueueMetrics::class, 'handleException']);
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
