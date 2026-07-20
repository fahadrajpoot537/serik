<?php

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Http\Controllers\ConsultCustomFieldController;
use Botble\RealEstate\Http\Controllers\CouponController;
use Botble\RealEstate\Http\Controllers\CustomFieldController;
use Botble\RealEstate\Http\Controllers\DuplicatePropertyController;
use Botble\RealEstate\Http\Controllers\ImportProjectController;
use Botble\RealEstate\Http\Controllers\ImportPropertyController;
use Botble\RealEstate\Http\Controllers\InvoiceController;
use Botble\RealEstate\Http\Controllers\PropertyController;
use Botble\RealEstate\Http\Controllers\UnverifiedAccountController;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\RealEstate\Http\Controllers', 'middleware' => ['web', 'core']], function (): void {
    Route::group([
        'prefix' => BaseHelper::getAdminPrefix() . '/real-estate',
        'middleware' => 'auth',
    ], function (): void {
        Route::group(['prefix' => 'settings', 'as' => 'real-estate.settings.'], function (): void {
            Route::get('general', [
                'as' => 'general',
                'uses' => 'Settings\GeneralSettingController@edit',
            ]);

            Route::put('general', [
                'as' => 'general.update',
                'uses' => 'Settings\GeneralSettingController@update',
                'permission' => 'real-estate.settings.general',
            ]);

            Route::get('currencies', [
                'as' => 'currencies',
                'uses' => 'Settings\CurrencySettingController@edit',
            ]);

            Route::put('currencies', [
                'as' => 'currencies.update',
                'uses' => 'Settings\CurrencySettingController@update',
                'permission' => 'real-estate.settings.currencies',
            ]);

            Route::get('accounts', [
                'as' => 'accounts',
                'uses' => 'Settings\AccountSettingController@edit',
            ]);

            Route::put('accounts', [
                'as' => 'accounts.update',
                'uses' => 'Settings\AccountSettingController@update',
                'permission' => 'real-estate.settings.accounts',
            ]);

            Route::get('invoices', [
                'as' => 'invoices',
                'uses' => 'Settings\InvoiceSettingController@edit',
            ]);

            Route::put('invoices', [
                'as' => 'invoices.update',
                'uses' => 'Settings\InvoiceSettingController@update',
                'permission' => 'real-estate.settings.invoices',
            ]);

            Route::get('invoice-template', [
                'as' => 'invoice-template',
                'uses' => 'Settings\InvoiceTemplateSettingController@edit',
            ]);

            Route::put('invoice-template', [
                'as' => 'invoice-template.update',
                'uses' => 'Settings\InvoiceTemplateSettingController@update',
                'permission' => 'real-estate.settings.invoice-template',
            ]);

            Route::prefix('invoice-template')->name('invoice-template.')->group(function (): void {
                Route::post('reset', [
                    'as' => 'reset',
                    'uses' => 'Settings\InvoiceTemplateSettingController@reset',
                    'permission' => 'invoice.index',
                ]);

                Route::get('preview', [
                    'as' => 'preview',
                    'uses' => 'Settings\InvoiceTemplateSettingController@preview',
                    'permission' => 'invoice.index',
                ]);
            });
        });

        Route::group(['prefix' => 'properties', 'as' => 'property.'], function (): void {
            Route::resource('', 'PropertyController')->parameters(['' => 'property']);

            Route::group(['permission' => 'property.edit'], function (): void {
                Route::post('{property}/approve', [PropertyController::class, 'approve'])->name('approve')
                    ->wherePrimaryKey();
                Route::post('{property}/reject', [PropertyController::class, 'reject'])->name('reject')
                    ->wherePrimaryKey();
                Route::post('{property}/duplicate', [DuplicatePropertyController::class, '__invoke'])
                    ->name('duplicate-property')
                    ->wherePrimaryKey();
            });
        });

        Route::group(['prefix' => 'projects', 'as' => 'project.'], function (): void {
            Route::resource('', 'ProjectController')
                ->parameters(['' => 'project']);
        });

        Route::group(['prefix' => 'property-features', 'as' => 'property_feature.'], function (): void {
            Route::resource('', 'FeatureController')
                ->parameters(['' => 'property_feature']);
        });

        Route::group(['prefix' => 'investors', 'as' => 'investor.'], function (): void {
            Route::resource('', 'InvestorController')
                ->parameters(['' => 'investor']);
        });

        Route::group(['prefix' => 'consults', 'as' => 'consult.'], function (): void {
            Route::resource('', 'ConsultController')
                ->parameters(['' => 'consult'])
                ->except(['create', 'store']);

            Route::group(['prefix' => 'custom-fields', 'as' => 'custom-fields.', 'permission' => 'consult.edit'], function (): void {
                Route::resource('', ConsultCustomFieldController::class)->parameters(['' => 'custom-field']);
            });
        });

        Route::group(['prefix' => 'categories', 'as' => 'property_category.'], function (): void {
            Route::resource('', 'CategoryController')
                ->parameters(['' => 'category']);

            Route::put('update-tree', [
                'as' => 'update-tree',
                'uses' => 'CategoryController@updateTree',
                'permission' => 'property_category.edit',
            ]);

            Route::get('search', [
                'as' => 'search',
                'uses' => 'CategoryController@getSearch',
                'permission' => 'property_category.index',
            ]);
        });

        Route::group(['prefix' => 'facilities', 'as' => 'facility.'], function (): void {
            Route::resource('', 'FacilityController')
                ->parameters(['' => 'facility']);
        });

        Route::group(['prefix' => 'accounts', 'as' => 'account.'], function (): void {
            Route::resource('', 'AccountController')
                ->parameters(['' => 'account']);

            Route::get('list', [
                'as' => 'list',
                'uses' => 'AccountController@getList',
                'permission' => 'account.index',
            ]);

            Route::get('view/{account}', [
                'as' => 'show',
                'uses' => 'AccountController@show',
                'permission' => 'account.edit',
            ])->wherePrimaryKey();

            Route::post('credits/{id}', [
                'as' => 'credits.add',
                'uses' => 'TransactionController@postCreate',
                'permission' => 'account.edit',
            ])->wherePrimaryKey();

            Route::post('verify-email/{id}', [
                'as' => 'verify-email',
                'uses' => 'AccountController@verifyEmail',
                'permission' => 'account.edit',
            ])->wherePrimaryKey();

            Route::put('{account}/verify', [
                'as' => 'verify',
                'uses' => 'AccountController@verify',
                'permission' => 'account.edit',
            ])->wherePrimaryKey();

            Route::put('{account}/unverify', [
                'as' => 'unverify',
                'uses' => 'AccountController@unverify',
                'permission' => 'account.edit',
            ])->wherePrimaryKey();
        });

        Route::prefix('unverified-accounts')->name('unverified-accounts.')->group(function (): void {
            Route::group(['permission' => 'unverified-accounts.index'], function (): void {
                Route::match(['POST', 'GET'], '/', [UnverifiedAccountController::class, 'index'])->name('index');
                Route::get('{id}', [UnverifiedAccountController::class, 'show'])->name('show')->wherePrimaryKey();
                Route::post('{id}/approve', [UnverifiedAccountController::class, 'approve'])->name('approve')->wherePrimaryKey();
                Route::post('{id}/reject', [UnverifiedAccountController::class, 'reject'])->name('reject')->wherePrimaryKey();
            });
        });

        Route::group(['prefix' => 'packages', 'as' => 'package.'], function (): void {
            Route::resource('', 'PackageController')
                ->parameters(['' => 'package']);
        });

        Route::group(['prefix' => 'reviews', 'as' => 'review.'], function (): void {
            Route::resource('', 'ReviewController')->parameters(['' => 'review'])->only(['index', 'destroy']);
        });

        Route::group(['prefix' => 'property-visits', 'as' => 'property-visit.'], function (): void {
            Route::match(['GET', 'POST'], '/', [
                'as' => 'index',
                'uses' => 'PropertyVisitController@index',
                'permission' => 'property-visit.index',
            ]);
            Route::delete('{visit}', [
                'as' => 'destroy',
                'uses' => 'PropertyVisitController@destroy',
                'permission' => 'property-visit.destroy',
            ])->wherePrimaryKey('visit');
            Route::post('{visit}/approve-delete', [
                'as' => 'approve-delete',
                'uses' => 'PropertyVisitController@approveDelete',
                'permission' => 'property-visit.edit',
            ])->wherePrimaryKey('visit');
        });

        Route::prefix('custom-fields')->name('real-estate.custom-fields.')->group(function (): void {
            Route::resource('', CustomFieldController::class)->parameters(['' => 'custom-field']);

            Route::get('info', [
                'as' => 'get-info',
                'uses' => 'CustomFieldController@getInfo',
                'permission' => false,
            ]);
        });

        Route::group(['prefix' => 'invoices', 'as' => 'invoices.'], function (): void {
            Route::resource('', 'InvoiceController')->parameters(['' => 'invoice'])->except(['edit', 'update']);
            Route::get('{id}', [InvoiceController::class, 'show'])
                ->name('show')
                ->wherePrimaryKey();
            Route::get('{id}/generate', [InvoiceController::class, 'generate'])
                ->name('generate')
                ->wherePrimaryKey();
        });

        Route::prefix('tools/data-synchronize')->name('tools.data-synchronize.')->group(function (): void {
            Route::prefix('export')->name('export.')->group(function (): void {
                Route::prefix('properties')->name('properties.')->group(function (): void {
                    Route::get('/', [
                        'as' => 'index',
                        'uses' => 'ExportPropertyController@index',
                        'permission' => 'property.export',
                    ]);

                    Route::post('/', [
                        'as' => 'store',
                        'uses' => 'ExportPropertyController@store',
                        'permission' => 'property.export',
                    ]);
                });

                Route::prefix('projects')->name('projects.')->group(function (): void {
                    Route::get('/', [
                        'as' => 'index',
                        'uses' => 'ExportProjectController@index',
                        'permission' => 'project.export',
                    ]);

                    Route::post('/', [
                        'as' => 'store',
                        'uses' => 'ExportProjectController@store',
                        'permission' => 'project.export',
                    ]);
                });
            });

            Route::prefix('import')->name('import.')->group(function (): void {
                Route::group(['prefix' => 'properties', 'as' => 'properties.', 'permission' => 'property.import'], function (): void {
                    Route::get('/', [ImportPropertyController::class, 'index'])->name('index');
                    Route::post('/', [ImportPropertyController::class, 'import'])->name('store');
                    Route::post('validate', [ImportPropertyController::class, 'validateData'])->name('validate');
                    Route::post('download-example', [ImportPropertyController::class, 'downloadExample'])->name('download-example');
                });

                Route::group(['prefix' => 'projects', 'as' => 'projects.', 'permission' => 'project.import'], function (): void {
                    Route::get('/', [ImportProjectController::class, 'index'])->name('index');
                    Route::post('/', [ImportProjectController::class, 'import'])->name('store');
                    Route::post('validate', [ImportProjectController::class, 'validateData'])->name('validate');
                    Route::post('download-example', [ImportProjectController::class, 'downloadExample'])->name('download-example');
                });
            });
        });

        Route::group(['prefix' => 'coupons', 'as' => 'coupons.'], function (): void {
            Route::resource('', CouponController::class)
                ->parameters(['' => 'coupon']);

            Route::post('generate-coupon', [
                'as' => 'generate-coupon',
                'uses' => 'CouponController@generateCouponCode',
                'permission' => 'coupons.index',
            ]);

            Route::delete('deletes', [
                'as' => 'deletes',
                'uses' => 'CouponController@deletes',
                'permission' => 'coupons.destroy',
            ]);
        });

        Route::group(['prefix' => 'reports', 'as' => 'reports.'], function (): void {
            Route::get('/', [
                'as' => 'index',
                'uses' => 'ReportController@index',
                'permission' => 'reports.index',
            ]);
        });
    });
});
