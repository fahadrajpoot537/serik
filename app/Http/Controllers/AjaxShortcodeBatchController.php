<?php

namespace App\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Shortcode\Facades\Shortcode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * App-level batch shortcode renderer — avoids stale vendor/botble/shortcode copies on path repos.
 */
class AjaxShortcodeBatchController extends BaseController
{
    public function __invoke(Request $request)
    {
        $started = microtime(true);

        try {
            if (class_exists(\App\Support\EnsuresTranslator::class)) {
                \App\Support\EnsuresTranslator::ensure();
            }

            $blocks = $request->input('blocks', []);

            if (! is_array($blocks) || $blocks === []) {
                return $this->httpResponse()->setData([]);
            }

            $payload = [];

            foreach ($blocks as $index => $block) {
                if (! is_array($block)) {
                    continue;
                }

                $name = (string) ($block['name'] ?? '');
                $blockId = (string) ($block['id'] ?? $index);
                $attributes = $this->normalizeAttributes($block['attributes'] ?? []);

                if ($name === '') {
                    continue;
                }

                try {
                    $payload[$blockId] = $this->renderBlock($name, $attributes);
                } catch (Throwable $e) {
                    $this->safeLog('error', '[ajaxRenderUiBlocksBatch] block failed', [
                        'shortcode' => $name,
                        'block_id' => $blockId,
                        'message' => $e->getMessage(),
                    ]);
                    $payload[$blockId] = '<div class="text-center py-3 text-muted">Content temporarily unavailable.</div>';
                }
            }

            $elapsedMs = round((microtime(true) - $started) * 1000, 1);
            if ($elapsedMs >= 500) {
                $this->safeLog('warning', '[ajaxRenderUiBlocksBatch] slow', [
                    'blocks' => count($payload),
                    'elapsed_ms' => $elapsedMs,
                ]);
            }

            return $this->httpResponse()->setData($payload);
        } catch (Throwable $e) {
            $this->safeLog('error', '[ajaxRenderUiBlocksBatch] FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->httpResponse()->setData([]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function normalizeAttributes(mixed $attributes): array
    {
        if (! is_array($attributes)) {
            return [];
        }

        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $normalized[$key] = $value ? '1' : '0';
            } elseif ($value === null) {
                $normalized[$key] = '';
            } else {
                $normalized[$key] = (string) $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function renderBlock(string $name, array $attributes): string
    {
        if (! in_array($name, array_keys(Shortcode::getAll()), true)) {
            return '';
        }

        if (class_exists(\App\Support\EnsuresTranslator::class)) {
            \App\Support\EnsuresTranslator::ensure();
        }

        $locale = app()->getLocale();
        $cacheEnabled = false;

        try {
            $cacheEnabled = (bool) setting('shortcode_cache_enabled', false);
        } catch (Throwable) {
            $cacheEnabled = false;
        }

        $renderVersion = class_exists(\App\Support\ShortcodeRenderCache::class)
            ? \App\Support\ShortcodeRenderCache::version($name)
            : 1;
        $cacheKey = 'shortcode_ajax_v'.$renderVersion.'_'.md5($name.serialize($attributes).$locale);

        if ($cacheEnabled) {
            $cacheable = $this->isCacheable($name);
            $defaultTtl = (int) setting('shortcode_cache_ttl_default', 300);
            $cacheableTtl = (int) setting('shortcode_cache_ttl_cacheable', 1800);
            $cacheDuration = $cacheable
                ? Carbon::now()->addSeconds(max(5, $cacheableTtl))
                : Carbon::now()->addSeconds(max(5, $defaultTtl));

            $cached = Cache::get($cacheKey);

            if (is_string($cached)) {
                return $cached;
            }

            $content = $this->compile($name, $attributes);
            Cache::put($cacheKey, $content, $cacheDuration);

            return $content;
        }

        return $this->compile($name, $attributes);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function compile(string $name, array $attributes): string
    {
        return Shortcode::compile(
            Shortcode::generateShortcode($name, $attributes),
            true
        )->toHtml();
    }

    protected function isCacheable(string $name): bool
    {
        return in_array($name, [
            'static-block', 'featured-posts', 'gallery', 'youtube-video', 'google-map',
            'contact-form', 'image', 'properties', 'property-categories', 'agents',
            'testimonials', 'blog-posts', 'services', 'location', 'image-slider',
        ], true);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::{$level}($message, $context);
        } catch (Throwable) {
        }
    }
}
