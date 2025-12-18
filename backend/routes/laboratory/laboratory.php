<?php

use App\Http\Controllers\computers\ComputerController;
use App\Http\Controllers\laboratories\LabController;
use App\Http\Controllers\laboratories\LaboratoryReportController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {
    // Laboratory routes
    Route::get('/laboratories', [LabController::class, 'index']);
    Route::post('/laboratories', [LabController::class, 'store']);
    Route::put('/laboratories/{id}', [LabController::class, 'update']);
    Route::delete('/laboratories/{id}', [LabController::class, 'destroy']);
    Route::post('/assign-laboratories', [ComputerController::class, 'assignLaboratory']);
    Route::post('/unassign-laboratories', [ComputerController::class, 'unassignLaboratory']);

    // Laboratory Usage Report routes
    Route::get('/laboratory-reports/usage', [LaboratoryReportController::class, 'getUsageReport']);
    Route::get('/laboratory-reports/laboratories', [LaboratoryReportController::class, 'getLaboratories']);
});
    Route::get('/pub/laboratories', [LabController::class, 'index']);
