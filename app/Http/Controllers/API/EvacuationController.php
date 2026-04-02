<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Evacuation;
use App\Models\EvacuationCenter;
use App\Services\EvacuationService;
use Illuminate\Support\Facades\Auth;

class EvacuationController extends Controller
{
    //
    public function index(){
        return Evacuation::with('evacuationCenter')->get();
    }

    public function show($id){
        return Evacuation::with('evacuationCenter')->findOrFail($id);
    }

    public function store(Request $request, EvacuationService $evacuationService){
        $request->validate([
            'household_id' => 'required|exists:households,household_id',
            'evacuation_center_id' => 'required|exists:evacuation_centers,evacuation_center_id',
        ]);

        try{
            $evacuation = $evacuationService->handleScan(
                $request->household_id,
                $request->evacuation_center_id,
                Auth::user()->id
            );

            return response()->json([
                'message' => 'Household evacuated successfully',
                'data' => $evacuation
            ], 200);

        } catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
