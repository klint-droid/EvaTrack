<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\EvacuationRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;
use App\Models\Room;

class EvacuationCenterController extends Controller
{
    public function index()
    {
        $centers = EvacuationCenter::withCount([
            'evacuationRecords as current_capacity' => function ($query) {
                $query->where('status', 'evacuated');
            }
        ])->get();

        return response()->json($centers);
    }

    public function store(StoreEvacuationCenterRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {

            $data['evacuation_center_id'] = 'EC-' . Str::uuid();

            $evacuationCenter = EvacuationCenter::create($data);

            if (!empty($data['has_rooms']) && !empty($data['rooms'])) {

                $totalRoomCapacity = collect($data['rooms'])->sum('max_capacity');

                if ($totalRoomCapacity > $data['capacity']) {
                    return response()->json([
                        'message' => 'Total room capacity exceeds evacuation center capacity'
                    ], 422);
                }
            }

            return response()->json($evacuationCenter, 201);
        });
    }

    public function show(EvacuationCenter $evacuation_center)
    {
        return response()->json(
            $evacuation_center->load('rooms')
        );
    }

    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $evacuation_center)
    {
        $evacuation_center->update($request->validated());

        return response()->json($evacuation_center);
    }

    public function destroy(EvacuationCenter $evacuation_center)
    {
        $evacuation_center->delete();

        return response()->json([
            'message' => 'Evacuation Center deleted successfully'
        ]);
    }

    public function capacity(EvacuationCenter $evacuation_center)
    {
        $currentOccupancy = EvacuationRecord::where('evacuation_center_id', $evacuation_center->evacuation_center_id)
            ->where('status', 'evacuated')
            ->count();

        return response()->json([
            'capacity' => $evacuation_center->capacity,
            'current_occupancy' => $currentOccupancy,
            'available_space' => max(0, $evacuation_center->capacity - $currentOccupancy)
        ]);
    }
}