<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

Route::post('/secrets', [SecretsController::class, 'store'])
    ->middleware(['throttle:daily', 'throttle.pow:3,1']);

Route::middleware(['throttle:20,1', 'no.cache'])->group(function () {
    Route::get('/secrets/{token}', [SecretsController::class, 'fetch']);
    Route::post('/secrets/{token}/read', [SecretsController::class, 'confirmRead']);
});

Route::post('/secrets/{adminToken}/revoke', [SecretsController::class, 'revoke'])
    ->middleware('throttle:10,1'); // 10 per minute
