<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\AccommodationType;
use App\Models\EvacuationCenter;
use App\Http\Requests\StoreAccommodationUnitRequest;
use App\Http\Requests\UpdateAccommodationUnitRequest;
use Illuminate\Support\Facades\Auth;

class AccommodationUnitController extends Controller
{
    // Get all units for a center
    public function index($centerId)
    {
        $units = AccommodationUnit::with('type')
            ->where('center_id', $centerId)
            ->whereNull('deleted_at')
            ->get();

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

        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        EvacuationCenter::where('evacuation_center_id', $centerId)->firstOrFail();

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

    // Update a unit
    public function update(UpdateAccommodationUnitRequest $request, $centerId, $unitId)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = AccommodationUnit::where('unit_id', $unitId)
            ->where('center_id', $centerId)
            ->firstOrFail();

        $unit->update($request->validated());

        return response()->json([
            'message' => 'Unit updated successfully',
            'data'    => $unit->load('type')
        ]);
    }

    // Soft delete a unit
    public function destroy($centerId, $unitId)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = AccommodationUnit::where('unit_id', $unitId)
            ->where('center_id', $centerId)
            ->firstOrFail();

        $unit->update(['deleted_at' => now()]);

        return response()->json(['message' => 'Unit deleted successfully']);
    }
}