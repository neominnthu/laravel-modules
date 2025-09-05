<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('shop')->group(function () {
    Route::get('/', fn() => 'Shop module home');
});
