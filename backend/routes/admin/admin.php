<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;




Route::middleware('auth:sanctum')->group(function () {

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'index']);
        Route::post('/users', [AdminController::class, 'store']);
        Route::put('/users/{id}', [AdminController::class, 'update']);
        Route::get('/users/{id}', [AdminController::class, 'edit']);
        Route::delete('/users/{id}', [AdminController::class, 'delete']);
        Route::patch('/unlock/{id}', [AdminController::class, 'unlockByAdmin']);
    });

    Route::get('auth/user', [AuthController::class, 'user'])->name('auth.user');
    Route::get('auth/user/login-history', [AuthController::class, 'getUserLoginHistory'])->name('auth.user.login-history');
    Route::delete('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/check-email', [AuthController::class, 'isEmailExist']);

    // Password Reset Routes
    Route::post('auth/forgot-password/send-otp', [AuthController::class, 'sendResetOtp']);
    Route::post('auth/forgot-password/verify-otp', [AuthController::class, 'verifyResetOtp']);
    Route::post('auth/forgot-password/reset', [AuthController::class, 'resetPassword']);
    Route::post('auth/forgot-password/resend-otp', [AuthController::class, 'resendOtp']);
