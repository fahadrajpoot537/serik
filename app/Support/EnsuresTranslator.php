<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Translation\TranslationServiceProvider;
use Throwable;

/**
 * Production guard for Laravel 12 deferred translator binding.
 * Prevents ReflectionException: Class "translator" does not exist
 * during Blade/shortcode rendering (common after stale bootstrap/cache).
 */
final class EnsuresTranslator
{
    public static function ensure(): void
    {
        $app = app();

        if ($app->bound('translator')) {
            return;
        }

        try {
            // Force-load deferred TranslationServiceProvider if map is stale/corrupt.
            if (method_exists($app, 'loadDeferredProvider')) {
                $app->loadDeferredProvider('translator');
            }
        } catch (Throwable) {
            // continue to explicit register
        }

        if ($app->bound('translator')) {
            return;
        }

        try {
            $app->register(TranslationServiceProvider::class, force: true);
        } catch (Throwable $e) {
            try {
                Log::error('[EnsuresTranslator] register failed: ' . $e->getMessage());
            } catch (Throwable) {
                // ignore
            }
        }
    }

    public static function setLocaleSafe(?string $locale): void
    {
        if ($locale === null || $locale === '') {
            return;
        }

        self::ensure();

        $app = app();

        try {
            if ($app->bound('translator')) {
                $app->setLocale($locale);
            } else {
                $app['config']->set('app.locale', $locale);
            }
        } catch (Throwable $e) {
            try {
                $app['config']->set('app.locale', $locale);
                Log::warning('[EnsuresTranslator] setLocale fell back to config only: ' . $e->getMessage());
            } catch (Throwable) {
                // ignore
            }
        }
    }
}
