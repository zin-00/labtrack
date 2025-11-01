<?php

use App\Http\Controllers\computers\ComputerController;
use App\Http\Controllers\laboratories\LabController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {
    // Laboratory routes
    Route::get('/laboratories', [LabController::class, 'index']);
    Route::post('/laboratories', [LabController::class, 'store']);
    Route::put('/laboratories/{id}', [LabController::class, 'update']);
    Route::delete('/laboratories/{id}', [LabController::class, 'destroy']);
    Route::post('/assign-laboratories', [ComputerController::class, 'assignLaboratory']);
    Route::post('/unassign-laboratories', [ComputerController::class, 'unassignLaboratory']);
});
