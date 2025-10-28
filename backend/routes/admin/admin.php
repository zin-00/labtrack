<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'index']);
        Route::post('/users', [AdminController::class, 'store']);
        Route::put('/users/{id}', [AdminController::class, 'update']);
        Route::get('/users/{id}', [AdminController::class, 'edit']);
        Route::delete('/users/{id}', [AdminController::class, 'delete']);
        Route::patch('/unlock/{id}', [AdminController::class, 'unlockByAdmin']);
    });
});
