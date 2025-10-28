<?php

use App\Http\Controllers\Activity\ActivityController;
use Illuminate\Support\Facades\Route;



Route::get('/computer-activity', [ActivityController::class, 'index']);
Route::post('/browser-activity', [ActivityController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/browser-activities', [ActivityController::class, 'getBrowserActivities']);
});
