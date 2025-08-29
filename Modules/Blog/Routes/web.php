<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('blog')->group(function () {
    Route::get('/', fn() => 'Blog module home');
});
