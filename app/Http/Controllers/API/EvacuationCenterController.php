<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;
use PhpParser\Node\Expr\Eval_;

class EvacuationCenterController extends Controller
{
    //
    public function index(){
        $centers = EvacuationCenter::all();

        return $centers->map(function ($c) {
            return [
                'evacuation_center_id' => $c->evacuation_center_id,
                'name' => $c->name,
                'location' => $c->location,
                'capacity' => $c->capacity,
                'current_capacity' => $c->households()->count(),
            ];
        });
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
        $evacuationCenters = EvacuationCenter::findOrFail($id);
        $currentOccupancy = $evacuationCenters->households()->count();
        return response()->json([
            'capacity' => $evacuationCenters->capacity,
            'current_occupancy' => $currentOccupancy,
            'available_space' => $evacuationCenters->capacity - $currentOccupancy
        ]);
    }
}
