<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/auth/login', function () {
    return "Login";
});
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/current-user', [AuthController::class, 'currentUser']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return "Admin Dashboard Only";
    });
});