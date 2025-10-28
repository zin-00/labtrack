<?php

use App\Http\Controllers\sections\SectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('sections')->group(function () {
        Route::get('/', [SectionController::class, 'index']);
        Route::post('/', [SectionController::class, 'store']);
        Route::get('/{id}', [SectionController::class, 'show']);
        Route::patch('/{id}', [SectionController::class, 'update']);
        Route::delete('/{id}', [SectionController::class, 'destroy']);
    });
});
