<?php

use App\Http\Controllers\mapping\MappingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/students/unassigned', [MappingController::class, 'getUnassignedStudents']);
    Route::post('/computer/bulk-assign', [MappingController::class, 'bulkAssign']);
    Route::post('/computer/bulk-assign-auto', [MappingController::class, 'bulkAssignAuto']);
    Route::delete('/bulk-unassign', [MappingController::class, 'bulkUnassignStudents']);
    Route::get('/students/available-for-assignment', [MappingController::class, 'getAvailableStudents']);
    Route::get('/computers/available-for-assignment', [MappingController::class, 'getAvailableComputers']);
});
