<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\API\HouseholdController;
use App\Http\Controllers\API\EvacuationCenterController;
use App\Http\Controllers\API\EvacuationController;
use App\Http\Controllers\API\SmsWebhookController;
use App\Http\Controllers\API\Admin\UserAssignmentController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\EvacuationEventController;
use App\Http\Controllers\API\AccommodationUnitController;
use App\Http\Controllers\API\UnitAllocationController;
use App\Http\Controllers\API\HouseholdMemberController;
use App\Http\Controllers\API\ResourceRequestController;
use App\Http\Controllers\API\CenterIssueReportController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\DisasterTypeController;
use App\Http\Controllers\API\SeverityLevelController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/households', [HouseholdController::class, 'index']);
    Route::get('/households/search', [HouseholdController::class, 'search']);
    Route::get('/households/{id}', [HouseholdController::class, 'show']);
    Route::post('/households', [HouseholdController::class, 'store']);
    Route::patch('/households/{id}', [HouseholdController::class, 'update']);
    Route::delete('/households/{id}', [HouseholdController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/households/{householdId}/members', [HouseholdMemberController::class, 'index']);
    Route::post('/households/{householdId}/members', [HouseholdMemberController::class, 'store']);
    Route::patch('/households/{householdId}/members/{memberId}', [HouseholdMemberController::class, 'update']);
    Route::delete('/households/{householdId}/members/{memberId}', [HouseholdMemberController::class, 'destroy']);
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
    Route::patch(
        'evacuations/{evacuationId}/members/{memberId}/status',
        [EvacuationController::class, 'updateMemberStatus']
    );
    Route::delete('/evacuations/{evacuationId}', [EvacuationController::class, 'deleteRecord']);
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
        Route::get('/{center}', [EvacuationCenterController::class, 'show']);
        Route::get('/{center}/capacity', [EvacuationCenterController::class, 'capacity']);

        Route::middleware('role:super_admin,evac_admin')->group(function () {
            Route::post('/', [EvacuationCenterController::class, 'store']);
            Route::put('/{center}', [EvacuationCenterController::class, 'update']);
            Route::delete('/{center}', [EvacuationCenterController::class, 'destroy']);
        });
    });
/*
|--------------------------------------------------------------------------
| EVACUATION EVENT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('events', [EvacuationEventController::class, 'index']);
    Route::get('events/active', [EvacuationEventController::class, 'active']);
    Route::post('events', [EvacuationEventController::class, 'store']);
    Route::patch('events/{id}/end', [EvacuationEventController::class, 'end']);
    Route::patch('/events/{id}/assign-centers', [EvacuationEventController::class, 'assignCenters']);
    Route::patch('/centers/{centerId}/unassign', [EvacuationEventController::class, 'unassignCenter']);
});

/*
|--------------------------------------------------------------------------
| ACCOMMODATION UNIT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/accommodation-types', [AccommodationUnitController::class, 'types']);
    Route::get('/centers/{centerId}/units', [AccommodationUnitController::class, 'index']);
    Route::post('/centers/{centerId}/units', [AccommodationUnitController::class, 'store']);
    Route::patch('/centers/{centerId}/units/{unitId}', [AccommodationUnitController::class, 'update']);
    Route::delete('/centers/{centerId}/units/{unitId}', [AccommodationUnitController::class, 'destroy']);
});

/* 
|--------------------------------------------------------------------------
| UNIT ALLOCATION ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/centers/{centerId}/unassigned', [UnitAllocationController::class, 'unassigned']);
    Route::get('/units/{unitId}/allocations', [UnitAllocationController::class, 'index']);
    Route::post('/units/{unitId}/allocations', [UnitAllocationController::class, 'assign']);
    Route::delete('/units/{unitId}/allocations/{allocationId}', [UnitAllocationController::class, 'unassign']);
});

/*
|--------------------------------------------------------------------------
| RESOURCE REQUEST ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/urgency-levels', [ResourceRequestController::class, 'urgencyLevels']);
    Route::get('/resource-requests', [ResourceRequestController::class, 'index']);
    Route::post('/resource-requests', [ResourceRequestController::class, 'store']);
    Route::get('/resource-requests/{id}', [ResourceRequestController::class, 'show']);
    Route::patch('/resource-requests/{id}/status', [ResourceRequestController::class, 'updateStatus']);
    Route::delete('/resource-requests/{id}', [ResourceRequestController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| CENTER ISSUE REPORT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/center-issue-reports', [CenterIssueReportController::class, 'index']);
    Route::post('/center-issue-reports', [CenterIssueReportController::class, 'store']);
    Route::get('/center-issue-reports/{id}', [CenterIssueReportController::class, 'show']);
    Route::patch('/center-issue-reports/{id}', [CenterIssueReportController::class, 'update']);
    Route::patch('/center-issue-reports/{id}/status', [CenterIssueReportController::class, 'updateStatus']);
    Route::delete('/center-issue-reports/{id}', [CenterIssueReportController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| NOTIFICATION ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('notifications')
    ->middleware('auth:sanctum')
    ->group(function () {
        // Preview & send
        Route::get('/preview', [NotificationController::class, 'preview']);
        Route::post('/', [NotificationController::class, 'send']);
        
        // List all
        Route::get('/', [NotificationController::class, 'index']);
        
        // Urgency levels lookup
        Route::get('/urgency-levels', [NotificationController::class, 'urgencyLevels']);
        
        // Single notification actions
        Route::get('/{notification}', [NotificationController::class, 'show']);
        Route::get('/{notification}/logs', [NotificationController::class, 'logs']);
        Route::post('/{notification}/acknowledge', [NotificationController::class, 'acknowledge']);
        Route::delete('/{notification}', [NotificationController::class, 'cancel']);
    });

Route::get('/barangays', [AddressController::class, 'barangays']);
Route::get('/barangays/{id}/sitios', [AddressController::class, 'sitios']);
Route::get('/sitios/{id}/puroks', [AddressController::class, 'puroks']);

Route::get('/disaster-types', [DisasterTypeController::class, 'index']);
Route::get('/severity-levels', [SeverityLevelController::class, 'index']);