<?php

use App\Http\Controllers\report\ReportController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::delete('/reports/{id}', [ReportController::class, 'destroy']);
});
    Route::post('/reports', [ReportController::class, 'store']);
