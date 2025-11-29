<?php

use App\Http\Controllers\computers\ComputerLogController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/student-sessions', [ComputerLogController::class, 'index']);
    Route::get('/logs/export', [ComputerLogController::class, 'export']);
});
    Route::get('/pub/student-sessions', [ComputerLogController::class, 'index']);
