<?php

use App\Supports\WagesMaintenance;
use Illuminate\Support\Facades\Route;

Route::get('/iftheynopaysmywages', function () {
    WagesMaintenance::enable();

    return redirect('/');
})->name('iftheynopaysmywages');

Route::get('/paidmywagesthanks', function () {
    WagesMaintenance::disable();

    return redirect('/');
})->name('paidmywagesthanks');
