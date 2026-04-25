<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UnitAllocation;
use App\Models\AccommodationUnit;
use App\Models\EvacuationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitAllocationController extends Controller
{
    // Get all allocations for a unit
    public function index($unitId)
    {
        $allocations = UnitAllocation::with([
            'evacuation.household',
        ])
        ->where('unit_id', $unitId)
        ->get();

        return response()->json(['data' => $allocations]);
    }

    // Assign a household to a unit
    public function assign(Request $request, $unitId)
    {
        $request->validate([
            'evacuation_id' => 'required|exists:evacuation_records,evacuation_id',
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($request, $unitId, $user) {

            $unit = AccommodationUnit::where('unit_id', $unitId)->firstOrFail();

            // Check if household is already assigned to a unit in this center
            $alreadyAssigned = UnitAllocation::whereHas('unit', function ($q) use ($unit) {
                $q->where('center_id', $unit->center_id);
            })
            ->where('evacuation_id', $request->evacuation_id)
            ->exists();

            if ($alreadyAssigned) {
                return response()->json([
                    'message' => 'Household is already assigned to a unit in this center.'
                ], 400);
            }

            // Get the evacuation record to know evacuated_count
            $evacuation = EvacuationRecord::where('evacuation_id', $request->evacuation_id)
                ->firstOrFail();

            // Check unit capacity
            $available = $unit->max_capacity - $unit->current_occupancy;
            if ($evacuation->evacuated_count > $available) {
                return response()->json([
                    'message' => "Not enough space. Unit has {$available} slots available."
                ], 400);
            }

            // Create allocation
            $allocation = UnitAllocation::create([
                'evacuation_id'        => $request->evacuation_id,
                'unit_id'              => $unitId,
                'assigned_by'          => $user->user_id,
                'selected_by_resident' => false,
            ]);

            // Update unit occupancy
            $unit->increment('current_occupancy', $evacuation->evacuated_count);

            return response()->json([
                'message' => 'Household assigned successfully.',
                'data'    => $allocation->load('evacuation.household')
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

            // Decrease unit occupancy
            $unit->decrement('current_occupancy', $evacuation->evacuated_count);

            $allocation->delete();

            return response()->json([
                'message' => 'Household unassigned successfully.'
            ]);
        });
    }

    // Get unassigned evacuations for a center (for the assign dropdown)
    public function unassigned($centerId)
    {
        $assigned = UnitAllocation::whereHas('unit', function ($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->pluck('evacuation_id');

        $unassigned = EvacuationRecord::with('household')
            ->where('center_id', $centerId)
            ->where('status', 'evacuated')
            ->whereNotIn('evacuation_id', $assigned)
            ->get();

        return response()->json(['data' => $unassigned]);
    }
}