<?php

use App\Services\Seo\CityNavigationService;
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

        $data = app(CityNavigationService::class)->build($context);

        return Theme::partial('seo.city-navigation', $data);
    });
});
