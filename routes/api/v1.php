<?php

use Domain\Authentications\Http\Controllers\LoginController;
use Domain\Authentications\Http\Controllers\RegisterController;
use Domain\Projects\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', RegisterController::class)->name('register');
    Route::post('/login', LoginController::class)->middleware('throttle:login')->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('projects', ProjectController::class);
});
