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
use App\Http\Controllers\API\NotificationController;

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

Route::middleware(['auth:sanctum', 'role:super_admin,evac_admin'])->group(function () {

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'createUser']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);

    Route::post('/users/{user}/assign-center', [UserController::class, 'assignCenter']);
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
        Route::get('/{id}', [HouseholdController::class, 'show']);

        Route::post('/', [HouseholdController::class, 'store']);

        Route::get('/sync-households', [SyncController::class, 'syncHouseholds']);
});


/*
|--------------------------------------------------------------------------
| EVACUATION ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('evacuations/search-household', [HouseholdController::class, 'search']);
    Route::get('evacuations/active', [EvacuationController::class, 'active']);

    Route::post('evacuations/process-scan', [EvacuationController::class, 'scan']);
    Route::post('evacuations/verify-manual', [EvacuationController::class, 'verifyManual']);
    Route::post('evacuations/admit', [EvacuationController::class, 'admit']);

    Route::apiResource('evacuations', EvacuationController::class)
        ->only(['index', 'show']);
});


/*
|--------------------------------------------------------------------------
| EVACUATION CENTER ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('evacuation-centers')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        Route::get('/', [EvacuationCenterController::class, 'index']);
        Route::get('/{evacuation_center}', [EvacuationCenterController::class, 'show']);
        Route::get('/{evacuation_center}/capacity', [EvacuationCenterController::class, 'capacity']);

        Route::get('/{evacuation_center}/rooms', [RoomController::class, 'byCenter']);

        //  ADMIN ONLY
        Route::middleware('role:super_admin,evac_admin')->group(function () {
            Route::post('/', [EvacuationCenterController::class, 'store']);
            Route::put('/{evacuation_center}', [EvacuationCenterController::class, 'update']);
            Route::delete('/{evacuation_center}', [EvacuationCenterController::class, 'destroy']);
        });
});


/*
|--------------------------------------------------------------------------
| ROOM + ALLOCATION ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('rooms', [RoomController::class, 'index']);
    Route::get('rooms/suggest', [RoomController::class, 'suggest']);
    Route::get('rooms/{room}', [RoomController::class, 'show']);

    // ADMIN ONLY
    Route::middleware('role:super_admin,evac_admin')->group(function () {
        Route::post('rooms', [RoomController::class, 'store']);
        Route::put('rooms/{room}', [RoomController::class, 'update']);
        Route::delete('rooms/{room}', [RoomController::class, 'destroy']);
    });

    // personnel can assign/remove
    Route::post('rooms/{room}/assignments', [RoomAssignmentController::class, 'assign']);
    Route::delete('rooms/{room}/assignments/{household}', [RoomAssignmentController::class, 'remove']);
});


/*
|--------------------------------------------------------------------------
| SMS TESTING (DEV ONLY)
|--------------------------------------------------------------------------
*/

Route::get('/test-device', function () {
    return (new \App\Services\SmsGateService())->getDevices();
});

Route::get('/test-sms', function () {
    return (new \App\Services\SmsGateService())->sendSMS('09922260825', 'Milven gwapo');
});

Route::get('/check-sms/{id}', function ($id) {
    return (new \App\Services\SmsGateService())->getMessageStatus($id);
});

Route::get('/register-webhook', function () {
    return (new \App\Services\SmsGateService())->registerWebhook();
});

Route::get('/list-webhooks', function () {
    return (new \App\Services\SmsGateService())->listWebhooks();
});


/*
|--------------------------------------------------------------------------
| SMS WEBHOOK
|--------------------------------------------------------------------------
*/

Route::post('/sms/webhook', [SmsWebhookController::class, 'handle']);

Route::post('/notifications/send', [NotificationController::class, 'send']);

Route::post('/device-token', [DeviceTokenController::class, 'store']);