<?php

use Botble\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\Page\Http\Controllers'], function (): void {
    AdminHelper::registerRoutes(function (): void {
        Route::group(['prefix' => 'pages', 'as' => 'pages.'], function (): void {
            Route::resource('', 'PageController')->parameters(['' => 'page']);

            // Visual builder routes
            Route::get('{page}/visual-builder', [
                'as' => 'visual-builder',
                'uses' => 'PageController@visualBuilder',
                'permission' => 'pages.edit',
            ]);

            Route::match(['get', 'post'], '{page}/preview', [
                'as' => 'preview',
                'uses' => 'PageController@preview',
                'permission' => 'pages.edit',
            ]);

            Route::post('{page}/visual-builder/save', [
                'as' => 'visual-builder.save',
                'uses' => 'PageController@saveVisualBuilder',
                'permission' => 'pages.edit',
            ]);

            Route::post('visual-builder/render-items', [
                'as' => 'visual-builder.render-items',
                'uses' => 'PageController@renderShortcodeItems',
                'permission' => 'pages.edit',
            ]);

            Route::post('visual-builder/render-types', [
                'as' => 'visual-builder.render-types',
                'uses' => 'PageController@renderShortcodeTypes',
                'permission' => 'pages.edit',
            ]);
        });
    });
});
