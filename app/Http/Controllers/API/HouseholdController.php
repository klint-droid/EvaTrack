<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Household;
use App\Models\Evacuation;

class HouseholdController extends Controller
{
    public function index(Request $request){
        return response()->json(Household::paginate(10));
    }

    public function show($id){
        $household = Household::findOrFail($id);

        if(!$household){
            return response()->json(['message' => 'Household not found'], 404);
        }

        return response()->json($household);
    }

    public function verify(Request $request){

        $request->validate([
            'household_id' => 'required|exists:households,household_id',
            'evacuation_id' => 'required|exists:evacuations,evacuation_id',
        ]);

        $household = Household::where('household_id', $request->household_id)->first();

        if(!$household){
            return response()->json(['message' => 'QR code not found'], 404);
        }

        $record = Evacuation::where('household_id', $request->household_id)
            ->where('evacuation_center_id', $request->evacuation_center_id)
            ->first();

        if($record){
            return response()->json([
                'message' => 'Already scanned in this evacuation center',
                'data' => [
                    'household' => $household,
                    'record' => $record
                ]
            ]);
        }

        if($record->is_verified){
            return response()->json([
                'message' => 'Household already verified',
            ], 400);
        }

        $record->update([
            'is_verified' => true,
            'evacuated_at' => now(),
        ]);
        
        
        return response()->json([
            'message' => 'Household verified successfully',
            'data' => [
                'household' => $household,
                'evacuation_record' => $record,
            ]
        ]);
    }

    public function search(Request $request){
        $query = $request->input('q');

        if(!$query){
            return response()->json(['message' => 'Query parameter is required'], 400);
        }

        $households = Household::where(function ($q) use ($query) {
            $q->where('household_name', 'LIKE', "%{$query}%")
            ->orWhere('household_id', 'LIKE', "%{$query}%");
        })->paginate(10);

        return response()->json($households);
    }
}
