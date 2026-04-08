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
use App\Http\Controllers\API\SmsWebhookController;
use App\Http\Controllers\API\Admin\UserAssignmentController;

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

        Route::post('/users/{user}/assign-center', [UserController::class, 'assignCenter']);
    });
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

    Route::apiResource('evacuations', EvacuationController::class)
    ->only(['index', 'show']);
    
    Route::get('evacuations/active', [EvacuationController::class, 'active']);

    Route::post('evacuations/process-scan', [EvacuationController::class, 'scan']);

    Route::get('evacuations/search-household', [EvacuationController::class, 'search']);

    Route::post('evacuations/verify-manual', [EvacuationController::class, 'verifyManual']);

    Route::post('evacuations/create-household', [EvacuationController::class, 'createAndVerify']);

});

Route::prefix('evacuation-centers')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        Route::get('/', [EvacuationCenterController::class, 'index']);
        Route::get('/{evacuation_center}', [EvacuationCenterController::class, 'show']);
        Route::get('/{evacuation_center}/capacity', [EvacuationCenterController::class, 'capacity']);

        Route::get('/{evacuation_center}/rooms', [RoomController::class, 'byCenter']);

        Route::middleware('role:admin,super_admin')->group(function () {
            Route::post('/', [EvacuationCenterController::class, 'store']);
            Route::put('/{evacuation_center}', [EvacuationCenterController::class, 'update']);
            Route::delete('/{evacuation_center}', [EvacuationCenterController::class, 'destroy']);
        });
    });

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('rooms', [RoomController::class, 'index']);
    Route::get('rooms/{room}', [RoomController::class, 'show']);

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::post('rooms', [RoomController::class, 'store']);
        Route::put('rooms/{room}', [RoomController::class, 'update']);
        Route::delete('rooms/{room}', [RoomController::class, 'destroy']);
    });

    Route::post('rooms/{room}/assignments', [RoomAssignmentController::class, 'assign']);
    Route::delete('rooms/{room}/assignments/{household}', [RoomAssignmentController::class, 'remove']);
});

Route::get('/test-device', function () {
    $sms = new \App\Services\SmsGateService();
    return $sms->getDevices();
});

Route::get('/test-sms', function () {
    $sms = new \App\Services\SmsGateService();

    return $sms->sendSMS('09922260825', 'Milven gwapo');
});

Route::get('/check-sms/{id}', function ($id) {
    $sms = new \App\Services\SmsGateService();
    return $sms->getMessageStatus($id);
});

Route::get('/register-webhook', function () {
    $sms = new \App\Services\SmsGateService();
    return $sms->registerWebhook();
});

Route::get('/list-webhooks', function () {
    $sms = new \App\Services\SmsGateService();
    return $sms->listWebhooks();
});

Route::post('/sms/webhook', [SmsWebhookController::class, 'handle']);