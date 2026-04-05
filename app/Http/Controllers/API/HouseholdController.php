<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Household;
use App\Models\Evacuation;
use App\Http\Requests\VerifyHouseholdRequest;
use Illuminate\Support\Facades\Auth;

class HouseholdController extends Controller
{
    public function index(Request $request){
        return response()->json(Household::paginate(10));
    }

    public function show($id){
        $household = Household::findOrFail($id);

        return response()->json($household);
    }

    public function verify(Request $request){

        $data = $request->validated();
        $user = Auth::user();

        $household = Household::where('household_id', $data['household_id'])->first();

        if(!$household){
            return response()->json(['message' => 'QR code not found'], 404);
        }

        $record = Evacuation::where('household_id', $request->household_id)
            ->where('evacuation_center_id', $request->evacuation_center_id)
            ->first();

        if(!$record){
            $record = Evacuation::create([
                'household_id' => $request->household_id,
                'evacuation_center_id' => $request->evacuation_center_id,
                'is_verified' => true,
                'verified_by' => $user->id,
                'evacuated_at' => now(),
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

        return response()->json(
            Household::where(function($q) use ($query){
                $q->where('household_name', 'LIKE', "%$query%")
                    ->orWhere('household_id', 'LIKE', "%$query%");
            })->paginate(10)
        );
    }
}
