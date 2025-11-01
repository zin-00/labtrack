<?php

use App\Events\TestEvent;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\BroadcastAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\computers\ComputerController;
use App\Http\Controllers\computers\ComputerLogController;
use App\Http\Controllers\computers\ComputerStatusDistribution;
use App\Http\Controllers\laboratories\LabController;
use App\Http\Controllers\mapping\MappingController;
use App\Http\Controllers\program\ProgramController;
use App\Http\Controllers\report\ReportController;
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




require __DIR__.'/section/section.php';
require __DIR__.'/program/program.php';
require __DIR__.'/yearlevel/yearlvl.php';
require __DIR__.'/activity/activity.php';
require __DIR__.'/request/request.php';
require __DIR__.'/laboratory/laboratory.php';
require __DIR__.'/student/student.php';
require __DIR__.'/admin/admin.php';
require __DIR__ .'/admin/profile/profile.php';
require __DIR__ .'/student/configuration/configuration.php';
require __DIR__ .'/mapping/mapping.php';
require __DIR__ .'/report/report.php';
require __DIR__.'/computer/computer.php';
require __DIR__.'/logs/logs.php';
require __DIR__.'/logs/audit/audit.php';
require __DIR__.'/dashboard/dashboard.php';

