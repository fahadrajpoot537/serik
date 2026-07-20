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
        // Entire method is fail-soft: homepage lazy blocks must never HTTP 500.
        try {
            $name = (string) $request->input('name', '');
            $attributes = $request->input('attributes', []);
            if (! is_array($attributes)) {
                $attributes = [];
            }

            // Normalize scalars → strings (style: 5 from JSON, etc.)
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

            $this->safeLog('info', '[ajaxRenderUiBlock] start', [
                'shortcode' => $name,
                'attributes' => $attributes,
            ]);

            if ($name === '' || ! in_array($name, array_keys(Shortcode::getAll()), true)) {
                return $this->httpResponse()->setData('');
            }

            $locale = app()->getLocale();
            $cacheEnabled = false;
            try {
                $cacheEnabled = (bool) setting('shortcode_cache_enabled', false);
            } catch (Throwable) {
                $cacheEnabled = false;
            }

            $cacheKey = 'shortcode_' . md5($name . serialize($attributes) . $locale);
            $started = microtime(true);

            if ($cacheEnabled) {
                $cacheable = $this->isShortcodeCacheable($name);
                $defaultTtl = (int) setting('shortcode_cache_ttl_default', 5);
                $cacheableTtl = (int) setting('shortcode_cache_ttl_cacheable', 1800);
                $cacheDuration = $cacheable
                    ? Carbon::now()->addSeconds(max(5, $cacheableTtl))
                    : Carbon::now()->addSeconds(max(5, $defaultTtl));

                $content = Cache::remember($cacheKey, $cacheDuration, function () use ($name, $attributes) {
                    return Shortcode::compile(
                        Shortcode::generateShortcode($name, $attributes),
                        true
                    )->toHtml();
                });
            } else {
                $content = Shortcode::compile(
                    Shortcode::generateShortcode($name, $attributes),
                    true
                )->toHtml();
            }

            $this->safeLog('info', '[ajaxRenderUiBlock] done', [
                'shortcode' => $name,
                'elapsed_ms' => round((microtime(true) - $started) * 1000, 1),
                'content_bytes' => strlen((string) $content),
            ]);

            return $this->httpResponse()->setData($content ?: '');
        } catch (Throwable $e) {
            $this->safeLog('error', '[ajaxRenderUiBlock] FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->httpResponse()->setData(
                '<div class="text-center py-3 text-muted">Content temporarily unavailable.</div>'
            );
        }
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
            // Never let logging break the homepage AJAX endpoint.
        }
    }
}
