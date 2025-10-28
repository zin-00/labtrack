<?php

use App\Http\Controllers\computers\ComputerController;
use Illuminate\Support\Facades\Route;

Route::delete('/bulk-unassign', [ComputerController::class, 'bulkUnassignStudents']);
