<?php

use App\Http\Controllers\students\configuration\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('configurations')->group(function () {
        Route::get('/', [ConfigurationController::class, 'index']);
        Route::delete('/{id}', [ConfigurationController::class, 'destroy']);
    });
});


