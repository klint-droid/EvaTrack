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