<?php

use Botble\Base\Http\Middleware\RequiresJsonRequestMiddleware;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;
use Theme\homzen\Http\Controllers\homzenController;
use Illuminate\Http\Request;




Route::middleware(['web', 'core'])
    ->controller(homzenController::class)
    ->group(function (): void {
        Route::group(apply_filters(BASE_FILTER_GROUP_PUBLIC_ROUTE, []), function (): void {
            Route::get('wishlist', 'getWishlist')->name('public.wishlist');

            Route::prefix('ajax')->name('public.ajax.')->middleware(RequiresJsonRequestMiddleware::class)->group(function (): void {
                Route::get('properties', 'ajaxGetProperties')->name('properties');
                Route::get('properties/map', 'ajaxGetPropertiesForMap')->name('properties.map');
                Route::get('projects', 'ajaxGetProjects')->name('projects');
                Route::get('projects/map', 'ajaxGetProjectsForMap')->name('projects.map');
                Route::get('projects/search', 'ajaxSearchProjects')->name('projects.search');
                Route::get('cities', 'ajaxGetCities')->name('cities');
            });
        });
    });
    
    
Route::middleware(['web', 'core'])->group(function (): void {
    Route::get(
        '/on/{filters}/map/{slug}',
        function (PublicController $controller, string $filters, string $slug) {
            return $controller->getViewWithPrefix('properties', $slug);
        }
    )->where('filters', '.*');

    Route::get('/on/{seo}/map', function (Request $request, $seo) {

        $request->merge([
            'seo' => $seo,
            'key' => 'map',
        ]);

        // rewrite request to existing route
        $request->server->set(
            'PATH_INFO',
            '/map'
        );

        $request->server->set(
            'REQUEST_URI',
            '/map'
        );

        return app()->handle($request);

    });
});

Theme::routes();
