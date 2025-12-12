<?php

use App\Http\Controllers\computers\ComputerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/computers', [ComputerController::class, 'index']);
    Route::post('/computers', [ComputerController::class, 'store']);
    Route::put('/computers/update/{id}', [ComputerController::class, 'update']);
    Route::delete('/computers/{id}', [ComputerController::class, 'destroy']);
    Route::get('/computers/null-lab', [ComputerController::class, 'showAllComputerWithNullLabId']);
    Route::put('/computer/state/{id}', [ComputerController::class, 'unlock']);
    Route::post('/computers/lock', [ComputerController::class, 'lockByMac']);
    Route::post('/computers/unlock-bulk', [ComputerController::class, 'unlockByMac']);
});

    Route::get('/computer/status/{ip}', [ComputerController::class, 'getStatus']);
    Route::post('/pc-offline/{ip}', [ComputerController::class, 'isOffline']);
    Route::post('/computer/register', [ComputerController::class, 'register_computer']);
    Route::post('/pc-online/{ip}', [ComputerController::class, 'isOnline']);
    Route::post('/heartbeat/{ip}', [ComputerController::class, 'heartbeat']);

    Route::post('/computer-unlock', [ComputerController::class, 'unlockAssignedComputer']);
    Route::post('/unlock-computers-by-lab/{labId}/{rfid_uid}', [ComputerController::class, 'unlockComputersByLab']);
    Route::get('/computers', [ComputerController::class, 'index']);
