<?php

namespace App\Http\Controllers\API;

use App\Models\EvacuationRecord;
use App\Models\Household;
use App\Models\EvacuationCenter;
use App\Models\HouseholdStatus;
use App\Services\EvacuationService;
use App\Http\Requests\StoreEvacuationRequest;
use App\Http\Requests\VerifyManualEvacuationRequest;
use App\Exceptions\HouseholdAlreadyEvacuatedException;
use App\Exceptions\MembersAlreadyEvacuatedException;
use App\Exceptions\NoCenterAssignedException;
use App\Exceptions\NoAvailableSlotsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class EvacuationController extends BaseApiController
{
    protected $evacuationService;

    public function __construct(EvacuationService $evacuationService)
    {
        $this->evacuationService = $evacuationService;
    }

    #[OA\Get(
        path: '/evacuations',
        summary: 'List evacuation records',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'household_status_id', in: 'query', description: 'Filter by household status ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $query = EvacuationRecord::with($this->evacuationService->recordRelations());

        if ($request->filled('household_status_id')) {
            $query->where('household_status_id', $request->household_status_id);
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        $query = $this->applyCenterFilter($query, $request);

        return response()->json([
            'data' => $query->latest('verified_at')->get()
        ]);
    }

    #[OA\Get(
        path: '/evacuations/{id}',
        summary: 'Get evacuation record details',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Evacuation record ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Evacuation not found')]
    public function show($id)
    {
        $query = EvacuationRecord::with($this->evacuationService->recordRelations())
            ->where('evacuation_id', $id);

        $query = $this->applyCenterFilter($query);

        $evacuation = $query->first();

        if (!$evacuation) {
            return response()->json(['message' => 'Evacuation not found or unauthorized'], 404);
        }

        return response()->json(['data' => $evacuation]);
    }

    #[OA\Post(
        path: '/evacuations/process-scan',
        summary: 'Verify admission using QR code scan',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
                new OA\Property(property: 'event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'member_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Household verified successfully')]
    #[OA\Response(response: 400, description: 'Invalid request or already evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function scan(StoreEvacuationRequest $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $centerId = $this->resolveUserCenterId($request);

        $alreadyEvacuated = EvacuationRecord::where('household_id', $request->household_id)
            ->where('center_id', $centerId)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->exists();

        if ($alreadyEvacuated) {
            throw new HouseholdAlreadyEvacuatedException();
        }

        try {
            $result = $this->evacuationService->handleScan(
                $request->household_id,
                $centerId,
                Auth::id(),
                'qr',
                $request->event_id,
                $request->input('member_ids', [])
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: '/evacuations/verify-manual',
        summary: 'Manually verify household for admission',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
                new OA\Property(property: 'event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'member_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Household verified successfully')]
    #[OA\Response(response: 400, description: 'Invalid request or already evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function verifyManual(VerifyManualEvacuationRequest $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $centerId = $this->resolveUserCenterId($request);

        try {
            $result = $this->evacuationService->handleManual(
                $request->household_id,
                $centerId,
                Auth::id(),
                $request->event_id,
                $request->input('member_ids', [])
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: '/evacuations/admit',
        summary: 'Admit household to center',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_id'],
            properties: [
                new OA\Property(property: 'household_id', type: 'string'),
                new OA\Property(property: 'member_count', type: 'integer', minimum: 1),
                new OA\Property(property: 'event_id', type: 'integer', nullable: true),
                new OA\Property(property: 'member_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Admission complete')]
    #[OA\Response(response: 400, description: 'Invalid request or already evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function admit(Request $request)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $centerId = $this->resolveUserCenterId($request);

        $alreadyEvacuated = EvacuationRecord::where('household_id', $request->household_id)
            ->where('center_id', $centerId)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->exists();

        if ($alreadyEvacuated) {
            throw new HouseholdAlreadyEvacuatedException();
        }

        try {
            if ($request->has('member_ids') && !empty($request->input('member_ids'))) {
                $result = $this->evacuationService->handleManual(
                    $request->household_id,
                    $centerId,
                    Auth::id(),
                    $request->event_id,
                    $request->input('member_ids')
                );
            } else {
                $result = $this->evacuationService->handleManualWithCount(
                    $request->household_id,
                    $centerId,
                    Auth::id(),
                    $request->member_count,
                    $request->event_id
                );
            }

            return response()->json([
                'message' => 'Admission complete',
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Get(
        path: '/evacuations/active',
        summary: 'Get active evacuation center record',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'No active evacuation found')]
    public function active()
    {
        $centerId = $this->resolveUserCenterId();

        $evacuation = EvacuationRecord::with($this->evacuationService->recordRelations())
            ->where('center_id', $centerId)
            ->where('household_status_id', HouseholdStatus::EVACUATED)
            ->latest('verified_at')
            ->first();

        if (!$evacuation) {
            return response()->json(['message' => 'No active evacuation found'], 404);
        }

        return response()->json(['data' => $evacuation]);
    }

    #[OA\Delete(
        path: '/evacuations/{evacuationId}',
        summary: 'Delete evacuation record',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'evacuationId', in: 'path', description: 'Evacuation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Record not found')]
    public function deleteRecord($evacuationId)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit', 
                'evacuatedMembers'
            ])->where('evacuation_id', $evacuationId)->firstOrFail();

            $this->checkCenterOwnership($record->center_id);

            if ($record->unitAllocation && $record->unitAllocation->unit) {
                $record->unitAllocation->delete();
            }

            $record->evacuatedMembers()->delete();
            $record->delete();

            return response()->json(['message' => 'Evacuation record deleted successfully.']);
        });
    }

    #[OA\Post(
        path: '/evacuations/{evacuationId}/checkout',
        summary: 'Checkout household from evacuation center',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'evacuationId', in: 'path', description: 'Evacuation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Checked out successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Record not found')]
    public function checkout($evacuationId)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit'
            ])->where('evacuation_id', $evacuationId)
                ->where('household_status_id', HouseholdStatus::EVACUATED)
                ->firstOrFail();

            $this->checkCenterOwnership($record->center_id);

            $record->update([
                'household_status_id' => HouseholdStatus::CHECKED_OUT,
                'updated_at' => now(),
            ]);

            if ($record->unitAllocation) {
                $record->unitAllocation->delete();
            }

            return response()->json([
                'message' => 'Household checked out successfully.',
                'data' => $record->fresh($this->evacuationService->recordRelations())
            ]);
        });
    }

    #[OA\Patch(
        path: '/evacuations/{evacuationId}/members/{memberId}/status',
        summary: 'Update status of individual household member',
        security: [['bearerAuth' => []]],
        tags: ['Evacuations']
    )]
    #[OA\Parameter(name: 'evacuationId', in: 'path', description: 'Evacuation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'memberId', in: 'path', description: 'Member ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'household_status_id', type: 'integer'),
                new OA\Property(property: 'status', type: 'string', enum: ['evacuated', 'not_verified', 'not_evacuated']),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 400, description: 'Invalid request or unit full')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Record or member not found')]
    public function updateMemberStatus(Request $request, $evacuationId, $memberId)
    {
        if ($request->has('status') && !$request->has('household_status_id')) {
            $statusStr = $request->input('status');
            $statusId = HouseholdStatus::NOT_VERIFIED;
            if ($statusStr === 'evacuated') {
                $statusId = HouseholdStatus::EVACUATED;
            } elseif ($statusStr === 'not_verified' || $statusStr === 'not_evacuated') {
                $statusId = HouseholdStatus::NOT_VERIFIED;
            }
            $request->merge(['household_status_id' => $statusId]);
        }

        $request->validate([
            'household_status_id' => 'required|exists:household_statuses,status_id',
        ]);

        return DB::connection('mysql_v2')->transaction(function () use ($request, $evacuationId, $memberId) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit', 
                'evacuatedMembers'
            ])->where('evacuation_id', $evacuationId)
                ->where('household_status_id', HouseholdStatus::EVACUATED)
                ->firstOrFail();

            $this->checkCenterOwnership($record->center_id);

            $member = \App\Models\HouseholdMember::where('member_id', $memberId)
                ->where('household_id', $record->household_id)
                ->firstOrFail();

            $oldCount = (int) $record->evacuated_count;

            if ($request->household_status_id == HouseholdStatus::EVACUATED) {
                \App\Models\EvacuatedMember::firstOrCreate(
                    ['evacuation_id' => $record->evacuation_id, 'member_id' => $member->member_id],
                    ['verified_at' => now()]
                );
            } else {
                \App\Models\EvacuatedMember::where('evacuation_id', $record->evacuation_id)
                    ->where('member_id', $member->member_id)
                    ->delete();
            }

            $newCount = \App\Models\EvacuatedMember::where('evacuation_id', $record->evacuation_id)->count();
            $record->update(['evacuated_count' => $newCount]);

            $difference = $newCount - $oldCount;

            if ($difference > 0 && $record->unitAllocation && $record->unitAllocation->unit) {
                $unit = $record->unitAllocation->unit;
                $availableSlots = $unit->max_capacity - $unit->current_occupancy;
                if ($difference > $availableSlots) {
                    throw new NoAvailableSlotsException();
                }
            }

            return response()->json([
                'message' => 'Member evacuation status updated successfully.',
                'data'    => $record->fresh($this->evacuationService->recordRelations()),
            ]);
        });
    }
}