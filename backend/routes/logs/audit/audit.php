<?php

use App\Http\Controllers\audit\AuditController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/audit-logs', [AuditController::class, 'index']);
});
