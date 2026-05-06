<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\AccommodationType;
use App\Models\EvacuationCenter;
use App\Models\UnitAllocation;
use App\Http\Requests\StoreAccommodationUnitRequest;
use App\Http\Requests\UpdateAccommodationUnitRequest;
use Illuminate\Support\Facades\Auth;

class AccommodationUnitController extends Controller
{
    // Get all units for a center
    public function index($centerId)
    {
        $units = AccommodationUnit::where('center_id', $centerId)
            ->get()
            ->map(function ($unit){
                $occupancy = UnitAllocation::where('unit_id', $unit->unit_id)
                    ->join('evacuation_records', 'unit_allocations.evacuation_id', '=', 'evacuation_records.evacuation_id')
                    ->sum('evacuation_records.evacuated_count');
                
                $unit->current_occupancy = $occupancy;

                return $unit;
            });

        return response()->json(['data' => $units]);
    }

    // Get all accommodation types
    public function types()
    {
        return response()->json([
            'data' => AccommodationType::all()
        ]);
    }

    // Create a unit for a center
    public function store(StoreAccommodationUnitRequest $request, $centerId)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        if ($request->max_capacity > $center->capacity) {
            return response()->json([
                'message' => "Unit capacity ({$request->max_capacity}) cannot exceed center capacity ({$center->capacity})."
            ], 422);
        }

        $existingTotal = AccommodationUnit::where('center_id', $centerId)
            ->whereNull('deleted_at')
            ->sum('max_capacity');
        $newTotal = $existingTotal + $request->max_capacity;

        if ($newTotal > $center->capacity) {
            return response()->json([
                'message' => "Total unit capacity would be {$newTotal}, exceeding center capacity of {$center->capacity}. Available remaining: " . ($center->capacity - $existingTotal) . "."
            ], 422);
        }

        $unit = AccommodationUnit::create([
            'center_id'    => $centerId,
            'name'         => $request->name,
            'type_id'      => $request->type_id,
            'max_capacity' => $request->max_capacity,
            'created_at'   => now(),
        ]);

        return response()->json([
            'message' => 'Unit created successfully',
            'data'    => $unit->load('type')
        ], 201);
    }

    public function update(UpdateAccommodationUnitRequest $request, $centerId, $unitId)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = AccommodationUnit::where('unit_id', $unitId)
            ->where('center_id', $centerId)
            ->firstOrFail();

        $center = EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

        if ($request->max_capacity > $center->capacity) {
            return response()->json([
                'message' => "Unit capacity ({$request->max_capacity}) cannot exceed center capacity ({$center->capacity})."
            ], 422);
        }

        $existingTotal = AccommodationUnit::where('center_id', $centerId)
            ->where('unit_id', '!=', $unitId)
            ->whereNull('deleted_at')
            ->sum('max_capacity');

        $newTotal = $existingTotal + $request->max_capacity;

        if ($newTotal > $center->capacity) {
            return response()->json([
                'message' => "Total unit capacity would be {$newTotal}, exceeding center capacity of {$center->capacity}. Available remaining: " . ($center->capacity - $existingTotal) . "."
            ], 422);
        }

        $unit->update($request->validated());

        return response()->json([
            'message' => 'Unit updated successfully',
            'data'    => $unit->load('type')
        ]);
    }

    public function destroy($centerId, $unitId)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = AccommodationUnit::where('unit_id', $unitId)
            ->where('center_id', $centerId)
            ->firstOrFail();

        if ($unit->current_occupancy > 0) {
            return response()->json([
                'message' => 'Cannot delete a unit with current occupants. Unassign all households first.'
            ], 400);
        }

        $unit->delete();

        return response()->json(['message' => 'Unit deleted successfully']);
    }
}