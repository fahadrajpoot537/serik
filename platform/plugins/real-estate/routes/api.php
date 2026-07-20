<?php

use Botble\RealEstate\Http\Controllers\Fronts\RegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;





Route::get('install-nocaptcha', function () {

    // Optional simple protection
    abort_unless(
        request('key') === 'my-secret-key',
        403
    );

    $output = shell_exec(
        'cd ' . base_path() . ' && composer require anhskohbo/no-captcha 2>&1'
    );

    return "<pre>$output</pre>";
});




Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1',
    'namespace' => 'Botble\RealEstate\Http\Controllers\API',
], function (): void {

    // Public endpoints (no authentication required)


    Route::get('clear-property-image-cache', function () {
        Cache::forget('property_image_last_id');
        Cache::forget('import-property-images-lock'); // optional (lock reset)

        return response()->json([
            'status' => 'cache cleared'
        ]);
    });


    /*Route::get('/check-nocaptcha', function () {

        return class_exists(\Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class)
            ? 'Package Installed'
            : 'Package Missing';
    });*/

    Route::get('check-nocaptcha', function () {

        /*   $cmd = '
                export COMPOSER_HOME=/tmp &&
                cd ' . base_path() . ' &&
                composer require ryangjchandler/laravel-cloudflare-turnstile 2>&1
            ';

            return shell_exec($cmd);


             Artisan::call('vendor:publish', [
                '--tag' => 'turnstile-config',
                '--force' => true,
            ]);

            return [
                'output' => Artisan::output()
            ];*/

        Artisan::call('vendor:publish');
        return Artisan::output();
    });




    // Properties
    Route::get('properties', 'PropertyController@index');
    Route::post('book-appointment', 'PropertyController@bookAppointment');
    Route::get('addproperties', 'PropertyController@addproperties');
    Route::get('addpropertiescron', 'PropertyController@addpropertiescron');
    Route::get('sync-amp-listing-dates', 'PropertyController@syncAmpListingDates');
    Route::get('sync-recent-sold', 'PropertyController@syncRecentSoldListings');
    Route::get('addpropertiesall', 'PropertyController@addpropertiesall');

    Route::get('addAllOntarioProperties', 'PropertyController@geocode');

    Route::middleware(['web'])->group(function (): void {
        Route::get('auth/session-status', function () {
            return response()->json([
                'logged_in' => auth('account')->check() || auth()->check(),
            ]);
        });

        Route::get('map-properties', 'PropertyController@fetchMapProperties');
        Route::get('map-thumbnails', 'PropertyController@getMapThumbnails');
        Route::get('map-property-bundle/{listingKey}', 'PropertyController@getMapPropertyBundle');
        Route::get('getPropertyDetails/{listingKey}', 'PropertyController@getPropertyDetails');
        Route::get('listing-history/{listingKey}', 'PropertyController@getListingHistory');
        Route::get('price-changes/{listingKey}', 'PropertyController@getPriceChanges');
        Route::get('property-rooms/{listingKey}', 'PropertyController@getPropertyRooms');
        Route::get('getPropertyImages/{listingKey}', 'PropertyController@getPropertyImages');
        Route::get('getPropertyBasicDetails/{listingKey}', 'PropertyController@getPropertyBasicDetails');
        Route::get('smart-search', 'PropertyController@smartSearch');
    });

    Route::get('propertiesName', 'PropertyController@fetchProperties');
    Route::get('testApi', 'PropertyController@testapi');
    Route::get('syncMissingDescriptions', 'PropertyController@syncMissingDescriptions');
    Route::get('sync-status', 'PropertyController@syncStatus');
    /**
     * Postman / ops: count active listings listed or imported in the last N hours.
     * GET /api/v1/listings-count?hours=10
     */
    Route::get('listings-count', function (\Illuminate\Http\Request $request) {
        $hours = max(1, min(168, (int) $request->input('hours', 10)));
        $since = now()->subHours($hours);
        $activeStatuses = ['New', 'Price Change', 'Extension', 'Ext', 'Previous Status', 'Active'];

        $base = \Illuminate\Support\Facades\DB::table('re_properties')
            ->whereIn('MlsStatus', $activeStatuses)
            ->whereIn('TransactionType', ['For Sale', 'For Lease']);

        $byContract = (clone $base)
            ->where('listing_contract_date', '>=', $since)
            ->count();

        $byCreated = (clone $base)
            ->where('created_at', '>=', $since)
            ->count();

        $sample = (clone $base)
            ->where(function ($q) use ($since) {
                $q->where('listing_contract_date', '>=', $since)
                    ->orWhere('created_at', '>=', $since);
            })
            ->orderByDesc('listing_contract_date')
            ->limit(10)
            ->get(['external_id', 'MlsStatus', 'TransactionType', 'listing_contract_date', 'created_at', 'name']);

        return response()->json([
            'hours' => $hours,
            'since' => $since->toDateTimeString(),
            'now' => now()->toDateTimeString(),
            'active_by_listing_contract_date' => $byContract,
            'active_by_db_created_at' => $byCreated,
            'note' => 'listing_contract_date = MLS "Listed On"; created_at = when row was inserted into our DB (cron import).',
            'sample' => $sample,
        ]);
    });
    Route::get('clear-sync-lock', function () {
        Cache::forget('sync_running');
        return 'Lock cleared';
    });
    Route::get('clear-amp-lock', function () {
        cache()->forget('amp_lock');
        cache()->forget('amp_skip');

        return 'AMP lock cleared';
    });

    Route::get('getMediaUrl/{listingKey}', 'PropertyController@getMediaUrl');

    Route::get('getPropertyStatusDetails', 'PropertyController@getPropertyStatusDetails');
    Route::get('get-property-status-details', 'PropertyController@getPropertyStatusDetails1');
    Route::get('treb-raw-data', 'PropertyController@trebRawData');



    Route::get('add-single-property/{listingKey}', 'PropertyController@addSingleProperty');
    Route::get('property-image/{listingKey}', 'PropertyController@getPropertyImage');


    Route::get('addpropertiesimages', 'PropertyController@importAllPropertyImages');
    Route::get('mediaapi/{listingKey}', 'PropertyController@mediaapi');
    Route::post('check-email', [RegisterController::class, 'checkEmail']);
    Route::get('properties/search', 'PropertyController@getSearch');
    Route::get('properties/filters', 'PropertyController@getFilters');
    Route::get('properties/{slug}', 'PropertyController@findBySlug');
    Route::get('properties/id/{id}', 'PropertyController@show')->where('id', '[0-9]+');

    // Projects
    Route::get('projects', 'ProjectController@index');
    Route::get('projects/search', 'ProjectController@getSearch');
    Route::get('projects/filters', 'ProjectController@getFilters');
    Route::get('projects/{slug}', 'ProjectController@findBySlug');
    Route::get('projects/id/{id}', 'ProjectController@show')->where('id', '[0-9]+');
    Route::get('projects/id/{id}/properties', 'ProjectController@getProperties')->where('id', '[0-9]+');

    // Categories
    Route::get('categories', 'CategoryController@index');
    Route::get('categories/filters', 'CategoryController@getFilters');
    Route::get('categories/{slug}', 'CategoryController@findBySlug');
    Route::get('categories/id/{id}', 'CategoryController@show')->where('id', '[0-9]+');
    Route::get('categories/id/{id}/properties', 'CategoryController@getProperties')->where('id', '[0-9]+');

    // Features
    Route::get('features', 'FeatureController@index');
    Route::get('features/all', 'FeatureController@all');
    Route::get('features/{id}', 'FeatureController@show')->where('id', '[0-9]+');

    // Facilities
    Route::get('facilities', 'FacilityController@index');
    Route::get('facilities/all', 'FacilityController@all');
    Route::get('facilities/{id}', 'FacilityController@show')->where('id', '[0-9]+');

    // Agents/Accounts
    Route::get('agents', 'AccountController@index');
    Route::get('agents/{id}', 'AccountController@show')->where('id', '[0-9]+');
    Route::get('agents/{id}/properties', 'AccountController@getProperties')->where('id', '[0-9]+');
    Route::get('agents/{id}/projects', 'AccountController@getProjects')->where('id', '[0-9]+');

    // Reviews (public read)
    Route::get('properties/{property_id}/reviews', 'ReviewController@index')->where('property_id', '[0-9]+');
    Route::get('reviews/{id}', 'ReviewController@show')->where('id', '[0-9]+');

    // Consultation
    Route::post('consults', 'ConsultController@store');
    Route::get('consults/custom-fields', 'ConsultController@getCustomFields');

    // Authenticated endpoints (require auth:sanctum middleware)
    Route::group(['middleware' => ['auth:sanctum']], function (): void {

        // Account profile
        Route::get('account/profile', 'AccountController@profile');

        // Reviews (authenticated actions)
        Route::post('properties/{property_id}/reviews', 'ReviewController@store')->where('property_id', '[0-9]+');
        Route::put('reviews/{id}', 'ReviewController@update')->where('id', '[0-9]+');
        Route::delete('reviews/{id}', 'ReviewController@destroy')->where('id', '[0-9]+');

    });
});
