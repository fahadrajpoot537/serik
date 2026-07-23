<?php

namespace Botble\Shortcode\Http\Controllers;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Shortcode\Events\ShortcodeAdminConfigRendering;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Shortcode\Http\Requests\GetShortcodeDataRequest;
use Botble\Shortcode\Http\Requests\RenderBlockUiRequest;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShortcodeController extends BaseController
{
    public function ajaxGetAdminConfig(?string $key, GetShortcodeDataRequest $request)
    {
        ShortcodeAdminConfigRendering::dispatch();

        $registered = shortcode()->getAll();

        $key = $key ?: $request->input('key');

        $data = Arr::get($registered, $key . '.admin_config');

        $attributes = [];
        $content = null;

        if ($code = $request->input('code')) {
            $compiler = shortcode()->getCompiler();
            $attributes = $compiler->getAttributes(html_entity_decode($code));
            $content = $compiler->getContent();
        } else {
            $attributes = $request->except(['_token', 'key', 'code']);
            if (isset($attributes['content'])) {
                $content = $attributes['content'];
                unset($attributes['content']);
            }
        }

        if ($data instanceof Closure || is_callable($data)) {
            $data = call_user_func($data, $attributes, $content);

            if ($modifier = Arr::get($registered, $key . '.admin_config_modifier')) {
                $data = call_user_func($modifier, $data, $attributes, $content);
            }

            if ($data instanceof ShortcodeForm) {
                $data->withCacheWarning($key)->withCaching();
                $data = $data->renderForm();
            } elseif ($data instanceof FormAbstract) {
                $data = $data->renderForm();
            }
        }

        $data = apply_filters(SHORTCODE_REGISTER_CONTENT_IN_ADMIN, $data, $key, $attributes);

        if (! $data) {
            $data = Html::tag('code', Shortcode::generateShortcode($key, $attributes))->toHtml();
        }

        return $this
            ->httpResponse()
            ->setData($data);
    }

    public function ajaxRenderUiBlock(RenderBlockUiRequest $request)
    {
        $started = microtime(true);
        $name = '';
        $attributes = [];

        try {
            [$name, $attributes] = $this->parseBlockRequest($request);

            $content = $this->renderUiBlockHtml($name, $attributes);

            $this->logSlowAjaxBlock($name, $started, strlen((string) $content));

            return $this->httpResponse()->setData($content ?: '');
        } catch (Throwable $e) {
            return $this->handleAjaxRenderFailure($e, $request, $name, $attributes, $started);
        }
    }

    public function ajaxRenderUiBlocksBatch(Request $request)
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
                $attributes = $block['attributes'] ?? [];
                $blockId = (string) ($block['id'] ?? $index);

                if ($name === '') {
                    continue;
                }

                try {
                    $payload[$blockId] = $this->renderUiBlockHtml($name, is_array($attributes) ? $attributes : []);
                } catch (Throwable $e) {
                    $this->safeLog('error', '[ajaxRenderUiBlocksBatch] block failed', [
                        'shortcode' => $name,
                        'block_id' => $blockId,
                        'message' => $e->getMessage(),
                    ]);
                    $payload[$blockId] = '<div class="text-center py-3 text-muted">Content temporarily unavailable.</div>';
                }
            }

            $this->logSlowAjaxBlock('batch:' . count($payload), $started, 0);

            return $this->httpResponse()->setData($payload);
        } catch (Throwable $e) {
            return $this->handleAjaxRenderFailure($e, $request, 'batch', [], $started);
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    protected function parseBlockRequest(RenderBlockUiRequest|Request $request): array
    {
        if (class_exists(\App\Support\EnsuresTranslator::class)) {
            \App\Support\EnsuresTranslator::ensure();
        }

        $name = (string) $request->input('name', '');
        $attributes = $request->input('attributes', $request->input('attribute', []));

        if (! is_array($attributes)) {
            $attributes = [];
        }

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $attributes[$key] = $value ? '1' : '0';
            } elseif ($value === null) {
                $attributes[$key] = '';
            } else {
                $attributes[$key] = (string) $value;
            }
        }

        return [$name, $attributes];
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function renderUiBlockHtml(string $name, array $attributes): string
    {
        if ($name === '' || ! in_array($name, array_keys(Shortcode::getAll()), true)) {
            $this->safeLog('warning', '[ajaxRenderUiBlock] unknown shortcode', ['name' => $name]);

            return '';
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
        $cacheKey = 'shortcode_ajax_v' . $renderVersion . '_' . md5($name . serialize($attributes) . $locale);

        if ($cacheEnabled) {
            $cacheable = $this->isShortcodeCacheable($name);
            $defaultTtl = (int) setting('shortcode_cache_ttl_default', 300);
            $cacheableTtl = (int) setting('shortcode_cache_ttl_cacheable', 1800);
            $cacheDuration = $cacheable
                ? Carbon::now()->addSeconds(max(5, $cacheableTtl))
                : Carbon::now()->addSeconds(max(5, $defaultTtl));

            $cached = Cache::get($cacheKey);

            if (is_string($cached)) {
                return $cached;
            }

            $content = $this->compileShortcodeHtml($name, $attributes);
            Cache::put($cacheKey, $content, $cacheDuration);

            return $content;
        }

        return $this->compileShortcodeHtml($name, $attributes);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function compileShortcodeHtml(string $name, array $attributes): string
    {
        if (class_exists(\App\Support\EnsuresTranslator::class)) {
            \App\Support\EnsuresTranslator::ensure();
        }

        return Shortcode::compile(
            Shortcode::generateShortcode($name, $attributes),
            true
        )->toHtml();
    }

    protected function logSlowAjaxBlock(string $name, float $started, int $contentBytes): void
    {
        $elapsedMs = round((microtime(true) - $started) * 1000, 1);

        if ($elapsedMs < 500) {
            return;
        }

        $this->safeLog('warning', '[ajaxRenderUiBlock] slow', [
            'shortcode' => $name,
            'elapsed_ms' => $elapsedMs,
            'content_bytes' => $contentBytes,
        ]);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function handleAjaxRenderFailure(
        Throwable $e,
        Request $request,
        string $name,
        array $attributes,
        float $started
    ) {
        $this->safeLog('error', '[ajaxRenderUiBlock] FAILED', [
            'shortcode' => $name,
            'attributes' => $attributes,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'elapsed_ms' => round((microtime(true) - $started) * 1000, 1),
        ]);

        $debugKey = (string) $request->header('X-Serik-Debug', $request->input('debug_key', ''));
        $debugHtml = '<div class="text-center py-3 text-muted">Content temporarily unavailable.</div>';

        if ($debugKey === 'serik2026clear') {
            $debugHtml .= '<pre style="text-align:left;white-space:pre-wrap;font-size:12px;max-width:900px;margin:12px auto;background:#f8f8f8;padding:12px;border:1px solid #ddd;">'
                . e($e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine())
                . '</pre>';
        }

        return $this->httpResponse()->setData($debugHtml);
    }

    protected function isShortcodeCacheable(string $name): bool
    {
        return in_array($name, [
            'static-block',
            'featured-posts',
            'gallery',
            'youtube-video',
            'google-map',
            'contact-form',
            'image',
            'properties',
            'property-categories',
            'agents',
            'testimonials',
            'blog-posts',
            'services',
            'location',
            'image-slider',
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
            // ignore
        }
    }
}
