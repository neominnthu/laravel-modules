<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/blog')->group(function () {
    Route::get('ping', fn() => response()->json(['pong' => true]));
});
