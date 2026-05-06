<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UnitAllocation;
use App\Models\AccommodationUnit;
use App\Models\EvacuationRecord;
use App\Models\HouseholdStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitAllocationController extends Controller
{
    // Get all allocations for a unit
    public function index($unitId)
    {
        $allocations = UnitAllocation::with([
            'evacuationRecord.household',
            'assigner'           
        ])
        ->where('unit_id', $unitId)
        ->get();

        return response()->json(['data' => $allocations]);
    }

    // Assign a household to a unit
    public function assign(Request $request, $unitId)
    {
        $request->validate([
            'evacuation_id' => [
                'required',
                function($attribute, $value, $fail) {
                    if (!EvacuationRecord::where('evacuation_id', $value)->exists()) {
                        $fail('The selected evacuation record is invalid.');
                    }
                }
            ],
        ]);

        $user = Auth::user();

        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        return DB::transaction(function () use ($request, $unitId, $user, $evacuatedStatusId) {

            $unit = AccommodationUnit::where('unit_id', $unitId)->firstOrFail();

            $evacuation = EvacuationRecord::where('evacuation_id', $request->evacuation_id)
                ->where('center_id', $unit->center_id)
                ->where('household_status_id', $evacuatedStatusId)
                ->first();

            if (!$evacuation) {
                return response()->json([
                    'message' => 'Evacuation record not found or does not belong to this center.'
                ], 404);
            }

            // Check if event is still active
            if ($evacuation->event && $evacuation->event->ended_at) {
                return response()->json([
                    'message' => 'Cannot assign household. The evacuation event has already ended.'
                ], 400);
            }

            $alreadyAssigned = UnitAllocation::join('accommodation_units', 'unit_allocations.unit_id', '=', 'accommodation_units.unit_id')
                ->where('accommodation_units.center_id', $unit->center_id)
                ->where('unit_allocations.evacuation_id', $request->evacuation_id)
                ->exists();

            if ($alreadyAssigned) {
                return response()->json([
                    'message' => 'Household is already assigned to a unit in this center.'
                ], 400);
            }

            // Check unit capacity
            $currentOccupancy = UnitAllocation::where('unit_id', $unitId)
                ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuations.evacuation_id')
                ->sum('evacuations.evacuated_count');

            if ($currentOccupancy <= 0) {
                return response()->json(['message' => 'This unit is already full.'], 400);
            }

            if ($evacuation->evacuated_count > $currentOccupancy) {
                return response()->json([
                    'message' => "Not enough space. This household has {$evacuation->evacuated_count} members but only {$currentOccupancy} slots are available."
                ], 400);
            }

            $allocation = UnitAllocation::create([
                'evacuation_id'        => $request->evacuation_id,
                'unit_id'              => $unitId,
                'assigned_by'          => $user->user_id,
                'selected_by_resident' => false,
            ]);

            return response()->json([
                'message' => 'Household assigned successfully.',
                'data'    => $allocation->load('evacuationRecord.household')
            ], 201);
        });
    }

    // Unassign a household from a unit
    public function unassign($unitId, $allocationId)
    {
        return DB::transaction(function () use ($unitId, $allocationId) {

            $allocation = UnitAllocation::where('allocation_id', $allocationId)
                ->where('unit_id', $unitId)
                ->firstOrFail();

            $unit = AccommodationUnit::where('unit_id', $unitId)->firstOrFail();

            $evacuation = EvacuationRecord::where('evacuation_id', $allocation->evacuation_id)
                ->firstOrFail();

            $allocation->delete();

            return response()->json(['message' => 'Household unassigned successfully.']);
        });
    }

    // Get unassigned evacuations for a center
    public function unassigned($centerId)
    {

        $evacuatedStatusId = HouseholdStatus::where('status_key', 'evacuated')->value('status_id');

        $assignedIds = UnitAllocation::join('accommodation_units', 'unit_allocations.unit_id', '=', 'accommodation_units.unit_id')
            ->where('accommodation_units.center_id', $centerId)
            ->pluck('unit_allocations.evacuation_id')
            ->toArray();

        $unassigned = EvacuationRecord::with('household')
            ->where('center_id', $centerId)
            ->where('household_status_id', $evacuatedStatusId)
            ->when(!empty($assignedIds), function ($q) use ($assignedIds) {
                $q->whereNotIn('evacuation_id', $assignedIds);
            })
            ->get();

        return response()->json(['data' => $unassigned]);
    }
}