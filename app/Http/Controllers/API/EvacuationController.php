<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\EvacuationRecord;
use App\Services\EvacuationService;
use App\Http\Requests\StoreEvacuationRequest;
use App\Models\AccommodationUnit;
use App\Models\EvacuatedMember;
use App\Models\HouseholdMember;
use Illuminate\Support\Facades\DB;

class EvacuationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = EvacuationRecord::with([
            'household.address',
            'evacuatedMembers.member',
            'unitAllocation.unit.type',
            'center',
            'event',
            'verifier'
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
                return response()->json([
                    'message' => 'No evacuation center assigned'
                ], 403);
            }

            if (
                $request->filled('center_id') &&
                $request->center_id !== $user->assigned_center_id
            ) {
                return response()->json([
                    'message' => 'You are not assigned to this evacuation center'
                ], 403);
            }

            $query->where('center_id', $user->assigned_center_id);

            return response()->json([
                'data' => $query->latest('verified_at')->get()
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    public function show($id)
    {
        $user = Auth::user();

        $query = EvacuationRecord::with([
            'household.address',
            'household.members',
            'evacuatedMembers.member',
            'unitAllocation.unit.type',
            'center',
            'event',
            'verifier'
        ])->where('evacuation_id', $id);

        if ($user->isEvacPersonnel()) {
            if (!$user->assigned_center_id) {
                return response()->json([
                    'message' => 'No evacuation center assigned'
                ], 403);
            }

            $query->where('center_id', $user->assigned_center_id);
        }

        $evacuation = $query->first();

        if (!$evacuation) {
            return response()->json([
                'message' => 'Evacuation not found or unauthorized'
            ], 404);
        }

        return response()->json([
            'data' => $evacuation
        ]);
    }

    public function scan(StoreEvacuationRequest $request, EvacuationService $service)
    {
        $user = Auth::user();

        if (
            !$user->isSuperAdmin() &&
            !$user->isEvacAdmin() &&
            !$user->isEvacPersonnel()
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $alreadyEvacuated = EvacuationRecord::where('household_id', $request->household_id)
            ->where('center_id', $user->assigned_center_id)
            ->where('household_status_id', 2)
            ->exists();

        if ($alreadyEvacuated) {
            return response()->json([
                'message' => 'Household already evacuated in this center'
            ], 400);
        }

        try {
            $result = $service->handleScan(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id,
                'qr',
                $request->event_id
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function verifyManual(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_id' => 'required|exists:households,household_id',
            'event_id'     => 'nullable|exists:evacuation_events,event_id',
        ]);

        $user = Auth::user();

        if (
            !$user->isSuperAdmin() &&
            !$user->isEvacAdmin() &&
            !$user->isEvacPersonnel()
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        try {
            $result = $service->handleManual(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id,
                $request->event_id
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function admit(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_id' => 'required|exists:households,household_id',
            'member_count' => 'required|integer|min:1',
            'event_id'     => 'nullable|exists:evacuation_events,event_id',
        ]);

        $user = Auth::user();

        if (
            !$user->isSuperAdmin() &&
            !$user->isEvacAdmin() &&
            !$user->isEvacPersonnel()
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $alreadyEvacuated = EvacuationRecord::where('household_id', $request->household_id)
            ->where('center_id', $user->assigned_center_id)
            ->where('household_status_id', 2)
            ->exists();

        if ($alreadyEvacuated) {
            return response()->json([
                'message' => 'Household already evacuated'
            ], 400);
        }

        try {
            $result = $service->handleManualWithCount(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id,
                $request->member_count,
                $request->event_id
            );

            return response()->json([
                'message' => 'Admission complete',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function active()
    {
        $user = Auth::user();

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $evacuation = EvacuationRecord::with([
            'household.address',
            'household.members',
            'evacuatedMembers.member',
            'unitAllocation.unit.type',
            'center',
            'event',
            'verifiedBy'
        ])
            ->where('center_id', $user->assigned_center_id)
            ->where('household_status_id', 2)
            ->latest('verified_at')
            ->first();

        if (!$evacuation) {
            return response()->json([
                'message' => 'No active evacuation found'
            ], 404);
        }

        return response()->json([
            'data' => $evacuation
        ]);
    }

    public function deleteRecord($evacuationId)
    {
        $user = Auth::user();

        return DB::connection('mysql_v2')->transaction(function () use ($evacuationId, $user) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit',
                'evacuatedMembers'
            ])
                ->where('evacuation_id', $evacuationId)
                ->firstOrFail();

            if (
                $user->isEvacPersonnel() &&
                $user->assigned_center_id !== $record->center_id
            ) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($record->unitAllocation && $record->unitAllocation->unit) {
                $unit = $record->unitAllocation->unit;

                $unit->update([
                    'current_occupancy' => max(
                        0,
                        $unit->current_occupancy - $record->evacuated_count
                    )
                ]);

                $record->unitAllocation->delete();
            }

            $record->evacuatedMembers()->delete();
            $record->delete();

            return response()->json([
                'message' => 'Evacuation record deleted successfully.'
            ]);
        });
    }

    public function updateMemberStatus(Request $request, $evacuationId, $memberId)
    {
        $validated = $request->validate([
            'household_status_id' => 'required|exists:household_statuses,id',
        ]);

        $user = Auth::user();

        return DB::connection('mysql_v2')->transaction(function () use ($validated, $evacuationId, $memberId, $user) {
            $record = EvacuationRecord::with([
                'unitAllocation.unit',
                'evacuatedMembers'
            ])
                ->where('evacuation_id', $evacuationId)
                ->where('household_status_id', 2)
                ->firstOrFail();

            if (
                $user->isEvacPersonnel() &&
                $user->assigned_center_id !== $record->center_id
            ) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $member = HouseholdMember::where('member_id', $memberId)
                ->where('household_id', $record->household_id)
                ->firstOrFail();

            $oldCount = (int) $record->evacuated_count;

            if ($validated['household_status_id'] === 2) {
                EvacuatedMember::firstOrCreate(
                    [
                        'evacuation_id' => $record->evacuation_id,
                        'member_id'     => $member->member_id,
                    ],
                    [
                        'verified_at' => now(),
                    ]
                );
            }

            if ($validated['household_status_id'] !== 2) {
                EvacuatedMember::where('evacuation_id', $record->evacuation_id)
                    ->where('member_id', $member->member_id)
                    ->delete();
            }

            $newCount = EvacuatedMember::where('evacuation_id', $record->evacuation_id)->count();

            $record->update([
                'evacuated_count' => $newCount,
            ]);

            $difference = $newCount - $oldCount;

            if ($difference !== 0 && $record->unitAllocation && $record->unitAllocation->unit) {
                $unit = $record->unitAllocation->unit;

                if ($difference > 0) {
                    $availableSlots = $unit->max_capacity - $unit->current_occupancy;

                    if ($difference > $availableSlots) {
                        throw new \Exception('Unit does not have enough available slots.');
                    }
                }

                $unit->update([
                    'current_occupancy' => max(
                        0,
                        $unit->current_occupancy + $difference
                    ),
                ]);
            }

            return response()->json([
                'message' => 'Member evacuation status updated successfully.',
                'data'    => $record->fresh([
                    'household.address',
                    'household.members',
                    'evacuatedMembers.member',
                    'unitAllocation.unit.type',
                    'center',
                    'event',
                    'verifiedBy',
                ]),
            ]);
        });
    }
}