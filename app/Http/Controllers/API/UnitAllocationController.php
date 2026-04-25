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
            'assignedBy'
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

            // Check evacuation record belongs to the same center as the unit
            $evacuation = EvacuationRecord::where('evacuation_id', $request->evacuation_id)
                ->where('center_id', $unit->center_id)
                ->where('status', 'evacuated')
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

            // Check if household is already assigned to any unit in this center
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

            // Check unit capacity
            $available = $unit->max_capacity - $unit->current_occupancy;

            if ($available <= 0) {
                return response()->json([
                    'message' => 'This unit is already full.'
                ], 400);
            }

            if ($evacuation->evacuated_count > $available) {
                return response()->json([
                    'message' => "Not enough space. This household has {$evacuation->evacuated_count} members but only {$available} slots are available."
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

            // Decrease unit occupancy but never go below 0
            $newOccupancy = max(0, $unit->current_occupancy - $evacuation->evacuated_count);
            $unit->update(['current_occupancy' => $newOccupancy]);

            $allocation->delete();

            return response()->json([
                'message' => 'Household unassigned successfully.'
            ]);
        });
    }

    // Get unassigned evacuations for a center
    public function unassigned($centerId)
    {
        $assignedIds = UnitAllocation::whereHas('unit', function ($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->pluck('evacuation_id')->toArray();

        $unassigned = EvacuationRecord::with('household')
            ->where('center_id', $centerId)
            ->where('status', 'evacuated')
            ->when(!empty($assignedIds), function ($q) use ($assignedIds) {
                $q->whereNotIn('evacuation_id', $assignedIds);
            })
            ->get();

        return response()->json(['data' => $unassigned]);
    }
}