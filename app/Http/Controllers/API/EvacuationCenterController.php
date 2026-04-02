<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\Eval_;

class EvacuationCenterController extends Controller
{
    //
    public function index(){
        return EvacuationCenter::all();
    }
    public function store(Request $request){
        $validated = $request->validate([
            'evacuation_center_id' => 'required|string|max:9',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer',
        ]);

        $evacuationCenter = EvacuationCenter::create($validated);

        return response()->json($evacuationCenter, 201);
    }

    public function show($id){
        return EvacuationCenter::findOrFail($id);
    }

    public function update(Request $request, $id){
        $evacuationCenter = EvacuationCenter::findOrFail($id);
        $evacuationCenter->update(($request->all()));

        return response()->json($evacuationCenter);
    }

    public function destroy($id){
        EvacuationCenter::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Evacuation Center deleted successfully'
        ]);
    }

    public function capacity($id){
        $evacuationCenters = EvacuationCenter::findOrFail($id);

        return response()->json([
            'capacity' => $evacuationCenters->capacity,
            'current_occupancy' => $evacuationCenters->current_capacity,
            'available_space' => $evacuationCenters->capacity - $evacuationCenters->current_capacity
        ]);
    }
}
