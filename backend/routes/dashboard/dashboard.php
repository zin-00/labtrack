<?php

use App\Http\Controllers\computers\ComputerStatusDistribution;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/status-distribution', [ComputerStatusDistribution::class, 'index']);
    Route::get('/data-distribution', [ComputerStatusDistribution::class, 'getDataDistribution']);
});
