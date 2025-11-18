<?php

use App\Http\Controllers\students\StudentController;
use App\Http\Controllers\students\attendance\AttendanceController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {

    // Students
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
    Route::post('/students/import', [StudentController::class, 'importStudents']);

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/export', [AttendanceController::class, 'export']);
});
