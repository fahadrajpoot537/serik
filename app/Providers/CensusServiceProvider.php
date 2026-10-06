<?php

namespace App\Providers;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CensusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Census\CanadaCensusService::class);
    }

    public function boot(): void
    {
        if (! function_exists('add_filter')) {
            return;
        }

        add_filter('after_single_content_detail', function ($html, $model = null) {
            if (! $model instanceof Property) {
                return $html;
            }

            try {
                $viewName = \Theme::getThemeNamespace(
                    'views.real-estate.single-layouts.partials.neighbourhood-demographics'
                );
                $section = View::make($viewName, ['model' => $model])->render();
            } catch (\Throwable $e) {
                report($e);

                return $html;
            }

            return ($html ?? '') . $section;
        }, 20, 2);
    }
}
