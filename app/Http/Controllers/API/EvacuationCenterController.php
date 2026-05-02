<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\EvacuationRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;

class EvacuationCenterController extends Controller
{
    public function index()
    {
        // status_id = 2 → "evacuated"
        $centers = EvacuationCenter::selectRaw("
                evacuation_centers.*,
                (
                    SELECT COUNT(*)
                    FROM evacuation_records
                    WHERE evacuation_records.center_id = evacuation_centers.evacuation_center_id
                      AND evacuation_records.status_id = 2
                ) as household_count,
                (
                    SELECT COALESCE(SUM(evacuation_records.evacuated_count), 0)
                    FROM evacuation_records
                    WHERE evacuation_records.center_id = evacuation_centers.evacuation_center_id
                      AND evacuation_records.status_id = 2
                ) as current_occupancy
            ")
            ->get();

        return response()->json($centers);
    }

    public function store(StoreEvacuationCenterRequest $request)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        $center = EvacuationCenter::create([
            'name'        => $data['name'],
            'osm_address' => $data['osm_address'] ?? null,
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'capacity'    => $data['capacity'],
        ]);

        return response()->json($center, 201);
    }

    public function show(EvacuationCenter $evacuation_center)
    {
        return response()->json($evacuation_center);
    }

    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $evacuation_center)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        $evacuation_center->update([
            'name'        => $data['name']        ?? $evacuation_center->name,
            'osm_address' => $data['osm_address']  ?? $evacuation_center->osm_address,
            'latitude'    => $data['latitude']     ?? $evacuation_center->latitude,
            'longitude'   => $data['longitude']    ?? $evacuation_center->longitude,
            'capacity'    => $data['capacity']     ?? $evacuation_center->capacity,
        ]);

        return response()->json($evacuation_center->fresh());
    }

    public function destroy(EvacuationCenter $evacuation_center)
    {
        $user = Auth::user();

        if (!$user->isEvacAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evacuation_center->delete();

        return response()->json(['message' => 'Evacuation Center deleted successfully']);
    }

    public function capacity(EvacuationCenter $evacuation_center)
    {
        $current = EvacuationRecord::where('center_id', $evacuation_center->evacuation_center_id)
            ->where('status_id', 2)
            ->count();

        return response()->json([
            'capacity'          => $evacuation_center->capacity,
            'current_occupancy' => $current,
            'available_space'   => max(0, $evacuation_center->capacity - $current),
        ]);
    }
}