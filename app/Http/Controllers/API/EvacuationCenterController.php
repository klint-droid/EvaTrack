<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\EvacuationRecord; // ✅ ADD THIS
use Illuminate\Http\Request;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;

class EvacuationCenterController extends Controller
{
    public function index(){
        $centers = EvacuationCenter::all();

        return response()->json(
            $centers->map(function ($c) {
                return [
                    'evacuation_center_id' => $c->evacuation_center_id,
                    'name' => $c->name,
                    'location' => $c->location,
                    'capacity' => $c->capacity,
                    'current_capacity' => $c->evacuationRecords()
                        ->where('status', 'evacuated')
                        ->count(),
                ];
            })
        );
    }

    public function store(StoreEvacuationCenterRequest $request){
        $data = $request->validated();

        $data['evacuation_center_id'] = uniqid('EC-');

        $evacuationCenter = EvacuationCenter::create($data);

        return response()->json($evacuationCenter, 201);
    }

    public function show($id){
        return EvacuationCenter::findOrFail($id);
    }

    public function update(UpdateEvacuationCenterRequest $request, $id){
        $evacuationCenter = EvacuationCenter::findOrFail($id);
        $evacuationCenter->update($request->validated());

        return response()->json($evacuationCenter);
    }

    public function destroy($id){
        EvacuationCenter::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Evacuation Center deleted successfully'
        ]);
    }

    public function capacity($id){
        $center = EvacuationCenter::findOrFail($id);

        $currentOccupancy = EvacuationRecord::where('evacuation_center_id', $id)
            ->where('status', 'evacuated')
            ->count();

        return response()->json([
            'capacity' => $center->capacity,
            'current_occupancy' => $currentOccupancy,
            'available_space' => $center->capacity - $currentOccupancy
        ]);
    }
}