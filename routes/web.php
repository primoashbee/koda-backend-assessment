<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'api' => url('/api/v1'),
        'documentation' => url('/docs/api'),
    ]);
})->name('home');
