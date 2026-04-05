<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\API\HouseholdController;
use App\Http\Controllers\API\EvacuationCenterController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\RoomAssignmentController;
use App\Http\Controllers\API\EvacuationController;

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

    Route::middleware('role:admin,super_admin')->group(function () {

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'createUser']);
        Route::put('/users/{id}', [UserController::class, 'updateUser']);
        Route::delete('/users/{id}', [UserController::class, 'deleteUser']);

    });

    Route::post('users/{user}/assign-center', [UserController::class, 'assignCenter']);
});

/*
|--------------------------------------------------------------------------
| HOUSEHOLD MANAGEMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('households')
    ->middleware(['auth:sanctum'])
    ->group(function () {
    Route::get('/', [HouseholdController::class, 'index']);
    Route::get('/search', [HouseholdController::class, 'search']);
    Route::post('/verify-household', [HouseholdController::class, 'verify']);
    Route::get('/{id}', [HouseholdController::class, 'show']);
    Route::get('/sync-households', [SyncController::class, 'syncHouseholds']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('evacuations', EvacuationController::class);
    
    Route::get('evacuations/active', [EvacuationController::class, 'active']);

    Route::post('evacuations/process-scan', [EvacuationController::class, 'scan']);

});

Route::prefix('evacuation-centers')
    ->middleware(['auth:sanctum'])
    ->group(function (){
        Route::get('/', [EvacuationCenterController::class, 'index']);
        Route::get('/{id}', [EvacuationCenterController::class, 'show']);
        Route::get('/{id}/capacity', [EvacuationCenterController::class, 'capacity']);
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::post('/', [EvacuationCenterController::class, 'store']);
            Route::put('/{id}', [EvacuationCenterController::class, 'update']);
            Route::delete('/{id}', [EvacuationCenterController::class, 'destroy']);
        });
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('rooms', RoomController::class);

    Route::post('rooms/{room}/assign', [RoomAssignmentController::class, 'assign']);
    Route::delete('rooms/{room}/remove/{household}', [RoomAssignmentController::class, 'remove']);
});
