<?php

use App\Http\Controllers\notification\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/recent', [NotificationController::class, 'recent']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/', [NotificationController::class, 'store']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});
