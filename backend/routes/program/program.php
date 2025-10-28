<?php

use App\Http\Controllers\program\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('programs')->group(function () {
        Route::get('/', [ProgramController::class, 'index']);
        Route::post('/', [ProgramController::class, 'store']);
        Route::put('/{id}', [ProgramController::class, 'update']);
        Route::delete('/{id}', [ProgramController::class, 'destroy']);
    });
});
