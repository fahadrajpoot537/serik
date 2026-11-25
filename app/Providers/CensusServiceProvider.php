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
        // Register after every provider has booted. On Octane, add_filter() can
        // still be missing while this provider boots, which dropped the census block.
        $this->app->booted(function () {
            if (! function_exists('add_filter')) {
                return;
            }

            $this->registerPropertyCensusSection();
        });
    }

    private function registerPropertyCensusSection(): void
    {
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
