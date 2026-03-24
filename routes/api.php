<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\API\HouseholdController;
use App\Models\Household;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'apiLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'apiLogout']);
    Route::get('/user', [AuthController::class, 'currentUser']);
});

/*
|--------------------------------------------------------------------------
| USER MANAGEMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // GET USERS (Admin + Super Admin)
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('role:admin,super_admin');

    // CREATE USER (Admin + Super Admin)
    Route::post('/users', [UserController::class, 'createUser'])
        ->middleware('role:admin,super_admin');

    // CREATE ADMIN (Super Admin only)
    Route::post('/users/admin', [UserController::class, 'createAdmin'])
        ->middleware('role:super_admin');

    // UPDATE USER
    Route::put('/users/{id}', [UserController::class, 'updateUser'])
        ->middleware('role:admin,super_admin');

    // DELETE USER
    Route::delete('/users/{id}', [UserController::class, 'deleteUser'])
        ->middleware('role:admin,super_admin');
});

/*
|--------------------------------------------------------------------------
| HOUSEHOLD MANAGEMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/households', [HouseholdController::class, 'index']);
    Route::get('/households/search', [HouseholdController::class, 'search']);
    Route::get('/households/qr/{qr_code}', [HouseholdController::class, 'findQR']);
    Route::get('/households/{id}', [HouseholdController::class, 'show']);
});

Route::get('/sync-households', [SyncController::class, 'syncHouseholds']);