<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;

Route::get('/auth/login', function () {
    return "Login Page";
});

Route::post('/login', [AuthController::class, 'apiLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'apiLogout']);
    Route::get('/user', [AuthController::class, 'currentUser']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return "Admin Dashboard Only";
    });
    Route::post('/admin/create-user', [UserController::class, 'createUser']);
});

Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    Route::post('/superadmin/create-admin', [UserController::class, 'createAdmin']);
    Route::get('/superadmin/user-list', function () {
        return "User list";
    });

    Route::post('/superadmin/create-user', [UserController::class, 'superAdminCreateUser']);
});

Route::middleware(['auth:sanctum', 'role:super_admin,admin'])->group(function () {
    Route::post('/users', [UserController::class, 'store']);

    Route::get('/admin/dashboard', function () {
        return "Admin & Super Admin";
    });
});