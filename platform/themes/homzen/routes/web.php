<?php

use App\Http\Controllers\AjaxShortcodeBatchController;
use App\Support\PropertyUrl;
use Botble\Base\Http\Middleware\RequiresJsonRequestMiddleware;
use Botble\Page\Models\Page;
use Botble\Shortcode\Http\Middleware\ShortcodePerformanceMiddleware;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Theme\homzen\Http\Controllers\homzenController;

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
                Route::get('blog-posts', 'ajaxGetBlogPosts')->name('blog-posts');
            });
        });
    });

Route::middleware(['web', 'core'])->group(function (): void {
    Route::redirect('/evaluation', '/free-home-evaluation', 301);
    Route::redirect('/frequently-asked-questions', '/faqs', 301);
    Route::redirect('/blog', '/blogs', 301);
    Route::redirect('/agents/sadaqat', '/agents', 301);

    Route::get('/blogs', function () {
        $blogPageId = (int) theme_option('blog_page_id', setting('blog_page_id'));

        if (! $blogPageId) {
            abort(404);
        }

        $slug = SlugHelper::getSlug(null, '', Page::class, $blogPageId);

        if (! $slug) {
            abort(404);
        }

        return app(PublicController::class)->getView($slug->key, $slug->prefix ?? '');
    });

    Route::get(
        '/on/{filters}/map/{slug}',
        function (string $filters, string $slug) {
            return redirect()->to(PropertyUrl::forSlug($slug), 301);
        }
    )->where(['filters' => '.*', 'slug' => '.*']);

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

Route::middleware(['web', 'core', RequiresJsonRequestMiddleware::class, ShortcodePerformanceMiddleware::class])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->post('ajax/render-ui-blocks-batch', AjaxShortcodeBatchController::class)
    ->name('public.ajax.render-ui-blocks-batch');

Theme::routes();
