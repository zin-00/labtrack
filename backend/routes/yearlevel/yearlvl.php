<?php

use App\Http\Controllers\yearlevel\YearLevelController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('yearlvls')->group(function () {
        Route::get('/', [YearLevelController::class, 'index']);
        Route::post('/', [YearLevelController::class, 'store']);
        Route::put('/{id}', [YearLevelController::class, 'update']);
        Route::delete('/{id}', [YearLevelController::class, 'destroy']);
    });
});
