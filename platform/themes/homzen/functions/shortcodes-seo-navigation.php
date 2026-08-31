<?php

use Botble\Shortcode\Compilers\Shortcode as ShortcodeCompiler;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Theme\Facades\Theme;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;

Event::listen(RouteMatched::class, function (): void {
    Shortcode::register('seo-city-navigation', __('SEO City Navigation'), __('Dynamic Ontario city navigation for SEO'), function (ShortcodeCompiler $shortcode) {
        $context = in_array($shortcode->context, ['home', 'properties'], true)
            ? $shortcode->context
            : 'home';

        // Never build this on the request TTFB — neighborhood JSON scans are 10–30s cold.
        $ajaxUrl = route('public.ajax.seo-city-navigation', ['context' => $context]);

        return Theme::partial('seo.shortcode-nav-mount', compact('ajaxUrl', 'context'));
    });
});
