<?php

use App\Events\TestEvent;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\BroadcastAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\computers\ComputerController;
use App\Http\Controllers\computers\ComputerLogController;
use App\Http\Controllers\computers\ComputerStatusDistribution;
use App\Http\Controllers\laboratories\LabController;
use App\Http\Controllers\program\ProgramController;
use App\Http\Controllers\RequestAccess\RequestAccessController;
use App\Http\Controllers\students\StudentController;
use App\Models\BrowserActivity;
use App\Models\Computer;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;







Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('auth/check-email', [AuthController::class, 'isEmailExist']);

Route::post('/computer-unlock', [ComputerController::class, 'unlockAssignedComputer']);
Route::post('/unlock-computers-by-lab/{labId}/{rfid_uid}', [ComputerController::class, 'unlockComputersByLab']);
Route::get('/computers', [ComputerController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/user', [AuthController::class, 'user'])->name('auth.user');
    Route::delete('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');


    // Computer routes
    Route::get('/computers', [ComputerController::class, 'index']);
    Route::post('/computers', [ComputerController::class, 'store']);
    Route::put('/computers/update/{id}', [ComputerController::class, 'update']);
    Route::delete('/computers/{id}', [ComputerController::class, 'destroy']);
    Route::get('/computers/null-lab', [ComputerController::class, 'showAllComputerWithNullLabId']);

     // Single assignment
    Route::post('/computer/assign', [ComputerController::class, 'assignStudent']);

    // Bulk assignment
    Route::post('/computer/bulk-assign', [ComputerController::class, 'bulkAssign']);

    // Unassign students
    Route::post('/computer/unassign', [ComputerController::class, 'unassignStudent']);

    // Get unassigned students with filters
    Route::get('/students/unassigned', [ComputerController::class, 'getUnassignedStudents']);

    // Get computer assignments
    Route::get('/computer/{id}/assignments', [ComputerController::class, 'getComputerAssignments']);


    // Program routes
    Route::get('/programs', [ProgramController::class, 'index']);

    // Computer logs
    Route::get('/logs', [ComputerLogController::class, 'index']);

    // Route::post('/pc-online/{ip}', [ComputerController::class, 'isOnline']);
    Route::put('/computer/state/{id}', [ComputerController::class, 'unlock']);







    // Students assign computers routes
    Route::get('/students/unassigned', [StudentController::class, 'getUnassignedStudents']);
    Route::post('/computer/assign', [ComputerController::class, 'assignStudent']);

    // Computer status distribution
    Route::get('/status-distribution', [ComputerStatusDistribution::class, 'index']);
    Route::get('/data-distribution', [ComputerStatusDistribution::class, 'getDataDistribution']);

    // Export
    Route::get('/logs/export', [ComputerLogController::class, 'export']);

    require __DIR__.'/computer/computer.php';

});

    Route::get('/computer/status/{ip}', [ComputerController::class, 'getStatus']);
    Route::post('/pc-offline/{ip}', [ComputerController::class, 'isOffline']);
    Route::post('/computer/register', [ComputerController::class, 'register_computer']);
    Route::post('/pc-online/{ip}', [ComputerController::class, 'isOnline']);

    // Request access
    Route::post('/request-access', [RequestAccessController::class, 'store']);
    Route::post('/heartbeat/{ip}', [ComputerController::class, 'heartbeat']);


Route::get('/data-distribution-test', function() {
    return response()->json(['test' => 'ok']);
});
Route::get('/test-unlock/{ip}', function($ip) {
    $computer = \App\Models\Computer::where('ip_address', $ip)->first();
    if ($computer) {
        \App\Events\ComputerUnlockRequested::dispatch($computer);
        return response()->json(['message' => 'Event dispatched']);
    }
    return response()->json(['message' => 'Computer not found'], 404);
});
    Route::get('/fire-test', function () {
    broadcast(new TestEvent("Hello from Laravel Reverb!"));
    return "Event fired!";
    });
    Route::get('/test-browser-activity', function () {
    $computer = Computer::first();

    if (!$computer) {
        return response()->json(['error' => 'No computers found'], 404);
    }

    $activity = BrowserActivity::create([
        'ip_address' => $computer->ip_address,
        'computer_id' => $computer->id,
        'browser_name' => 'Test Browser',
        'title' => 'Test Title',
        'url' => 'https://example.com',
        'duration' => '00:05:00',
    ]);

    return response()->json([
        'message' => 'Test activity created',
        'activity' => $activity
    ]);
});

require __DIR__.'/section/section.php';
require __DIR__.'/program/program.php';
require __DIR__.'/yearlevel/yearlvl.php';
require __DIR__.'/activity/activity.php';
require __DIR__.'/request/request.php';
require __DIR__.'/laboratory/laboratory.php';
require __DIR__.'/student/student.php';
require __DIR__.'/admin/admin.php';
require __DIR__ .'/admin/profile/profile.php';
require __DIR__ .'/audit/audit.php';
require __DIR__ .'/student/configuration/configuration.php';
