<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\EvacuationRecord;
use App\Models\DisasterEvent;
use App\Models\Household;
use App\Services\EvacuationService;
use App\Http\Requests\StoreEvacuationRequest;
use App\Models\EvacuatedMember;
use App\Models\HouseholdMember;
use App\Models\HouseholdStatus;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class EvacuationController extends Controller
{
    private function recordRelations(): array
    {
        return [
            'household.address',
            'household.members',
            'household.members.gender',
            'household.members.relationship',
            'household.members.civilStatus',
            'household.members.vulnerableGroupDetails',
            'evacuatedMembers.member',
            'unitAllocation.unit.type',  
            'center',
            'event',
            'verifier',                   
        ];
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
        $user = Auth::user();

        $query = EvacuationRecord::with([
            'household.address',
            'evacuatedMembers.member',
            'unitAllocations.unit.type', 
            'center',
            'event',
            'verifier',                  
        ]);

        if ($request->filled('household_status_id')) {
            $query->where('household_status_id', $request->household_status_id);
        }

        if ($user->isSuperAdmin() || $user->isEvacAdmin()) {
            if ($request->filled('center_id')) {
                $query->where('center_id', $request->center_id);
            }

            return response()->json([
                'data' => $query->latest('verified_at')->get()
            ]);
        }

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json(['message' => 'No evacuation center assigned'], 403);
            }

            if ($request->filled('center_id') && $request->center_id !== $user->assigned_center_id) {
                return response()->json(['message' => 'You are not assigned to this evacuation center'], 403);
            }

            $query->where('center_id', $user->assigned_center_id);

            return response()->json([
                'data' => $query->latest('verified_at')->get()
            ]);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
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
        $user = Auth::user();

        $query = EvacuationRecord::with($this->recordRelations())
            ->where('evacuation_id', $id);

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json(['message' => 'No evacuation center assigned'], 403);
            }
            $query->where('center_id', $user->assigned_center_id);
        }

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
    public function scan(StoreEvacuationRequest $request, EvacuationService $service)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin() && !$user->isEvacPersonnel()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->assigned_center_id) {
            return response()->json(['message' => 'No evacuation center assigned'], 403);
        }

        $alreadyEvacuated = EvacuationRecord::where('household_id', $request->household_id)
            ->where('center_id', $user->assigned_center_id)
            ->where('household_status_id', 2)
            ->exists();

        if ($alreadyEvacuated) {
            return response()->json(['message' => 'Household already evacuated in this center'], 400);
        }

        try {
            $result = $service->handleScan(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id,
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
    public function verifyManual(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Household::where('household_id', $value)->exists()) {
                        $fail('The selected household is invalid.');
                    }
                }
            ],
            'event_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !DisasterEvent::where('event_id', $value)->exists()) {
                        $fail('The selected event is invalid.');
                    }
                }
            ],
            'member_ids'   => 'nullable|array',
            'member_ids.*' => 'exists:household_members,member_id',
        ]);

        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin() && !$user->isEvacPersonnel()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->assigned_center_id) {
            return response()->json(['message' => 'No evacuation center assigned'], 403);
        }

        try {
            $result = $service->handleManual(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id,
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
    public function admit(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Household::where('household_id', $value)->exists()) {
                        $fail('The selected household is invalid.');
                    }
                }
            ],
            'member_count' => 'required_without:member_ids|integer|min:1',
            'event_id'     => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !DisasterEvent::where('event_id', $value)->exists()) {
                        $fail('The selected event is invalid.');
                    }
                }
            ],
            'member_ids'   => 'nullable|array',
            'member_ids.*' => 'exists:household_members,member_id',
        ]);

        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin() && !$user->isEvacPersonnel()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->assigned_center_id) {
            return response()->json(['message' => 'No evacuation center assigned'], 403);
        }

        $alreadyEvacuated = EvacuationRecord::where('household_id', $request->household_id)
            ->where('center_id', $user->assigned_center_id)
            ->where('household_status_id', 2)
            ->exists();

        if ($alreadyEvacuated) {
            return response()->json(['message' => 'Household already evacuated'], 400);
        }

        try {
            if ($request->has('member_ids') && !empty($request->input('member_ids'))) {
                $result = $service->handleManual(
                    $request->household_id,
                    $user->assigned_center_id,
                    $user->user_id,
                    $request->event_id,
                    $request->input('member_ids')
                );
            } else {
                $result = $service->handleManualWithCount(
                    $request->household_id,
                    $user->assigned_center_id,
                    $user->user_id,
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
        $user = Auth::user();

        if (!$user->assigned_center_id) {
            return response()->json(['message' => 'No evacuation center assigned'], 403);
        }

        $evacuation = EvacuationRecord::with($this->recordRelations())
            ->where('center_id', $user->assigned_center_id)
            ->where('household_status_id', 2)
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
        $user = Auth::user();

        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $user) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit', 
                'evacuatedMembers'
            ])->where('evacuation_id', $evacuationId)->firstOrFail();

            if ($user->isEvacPersonnel() && $user->assigned_center_id !== $record->center_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

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
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isEvacAdmin() && !$user->isEvacPersonnel()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $user) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit'
            ])->where('evacuation_id', $evacuationId)
                ->where('household_status_id', 2)
                ->firstOrFail();

            if ($user->isEvacPersonnel() && $user->assigned_center_id !== $record->center_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $record->update([
                'household_status_id' => 6,
                'updated_at' => now(),
            ]);

            if ($record->unitAllocation) {
                $record->unitAllocation->delete();
            }

            return response()->json([
                'message' => 'Household checked out successfully.',
                'data' => $record->fresh($this->recordRelations())
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
            $statusId = 1; // Default to Active/Not Verified
            if ($statusStr === 'evacuated') {
                $statusId = 2;
            } elseif ($statusStr === 'not_verified' || $statusStr === 'not_evacuated') {
                $statusId = 1;
            }
            $request->merge(['household_status_id' => $statusId]);
        }

        $request->validate([
            'household_status_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!HouseholdStatus::where('status_id', $value)->exists()) {
                        $fail('The selected status is invalid.');
                    }
                }
            ],
        ]);

        $user = Auth::user();

        return DB::connection('mysql_v2')->transaction(function () use ($request, $evacuationId, $memberId, $user) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit', 
                'evacuatedMembers'
            ])->where('evacuation_id', $evacuationId)
                ->where('household_status_id', 2)
                ->firstOrFail();

            if ($user->isEvacPersonnel() && $user->assigned_center_id !== $record->center_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $member = HouseholdMember::where('member_id', $memberId)
                ->where('household_id', $record->household_id)
                ->firstOrFail();

            $oldCount = (int) $record->evacuated_count;

            if ($request->household_status_id == 2) {
                EvacuatedMember::firstOrCreate(
                    ['evacuation_id' => $record->evacuation_id, 'member_id' => $member->member_id],
                    ['verified_at' => now()]
                );
            } else {
                EvacuatedMember::where('evacuation_id', $record->evacuation_id)
                    ->where('member_id', $member->member_id)
                    ->delete();
            }

            $newCount = EvacuatedMember::where('evacuation_id', $record->evacuation_id)->count();
            $record->update(['evacuated_count' => $newCount]);

            $difference = $newCount - $oldCount;

            if ($difference !== 0 && $record->unitAllocation && $record->unitAllocation->unit) {
                $unit = $record->unitAllocation->unit;

                if ($difference > 0) {
                    $availableSlots = $unit->max_capacity - $unit->current_occupancy;
                    if ($difference > $availableSlots) {
                        throw new \Exception('Unit does not have enough available slots.');
                    }
                }
            }

            return response()->json([
                'message' => 'Member evacuation status updated successfully.',
                'data'    => $record->fresh($this->recordRelations()),
            ]);
        });
    }
}