<?php

use App\Http\Controllers\RequestAccess\RequestAccessController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
           // Request Access
    Route::get('/request-access', [RequestAccessController::class, 'index']);
    Route::patch('/request-access/{id}/approve', [RequestAccessController::class, 'approve']);
    Route::patch('/request-access/{id}/reject', [RequestAccessController::class, 'reject']);
});

    // Request access
    Route::post('/request-access', [RequestAccessController::class, 'store']);
