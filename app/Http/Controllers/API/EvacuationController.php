<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\EvacuationService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreEvacuationRequest;
use App\Models\EvacuationRecord;

class EvacuationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->assigned_evacuation_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        return response()->json([
            'data' => EvacuationRecord::with('evacuationCenter')
                ->where('evacuation_center_id', $user->assigned_evacuation_center_id)
                ->get()
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

        $evacuation = EvacuationRecord::with('evacuationCenter')
            ->where('evacuation_id', $id)
            ->where('evacuation_center_id', $user->assigned_evacuation_center_id)
            ->first();

        if (!$evacuation) {
            return response()->json([
                'message' => 'Evacuation not found or unauthorized'
            ], 404);
        }

        return response()->json($evacuation);
    }

    public function scan(StoreEvacuationRequest $request, EvacuationService $service)
    {
        $user = Auth::user();

        if (!$user->assigned_evacuation_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        try {
            $evacuation = $service->handleScan(
                $request->household_id,
                $user->assigned_evacuation_center_id, 
                $user->user_id
            );

            return response()->json([
                'message' => 'Household evacuated successfully',
                'data' => $evacuation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function active()
    {
        $user = Auth::user();

        if (!$user->assigned_evacuation_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $evacuation = EvacuationRecord::where('evacuation_center_id', $user->assigned_evacuation_center_id)
            ->where('status', 'evacuated')
            ->first();

        if (!$evacuation) {
            return response()->json([
                'message' => 'No active evacuation found'
            ], 404);
        }

        return response()->json($evacuation);
    }

    public function search(Request $request){
        $query = $request->input('query');

        $households = Household::where('household_id', $query)
            ->orWhere('household_name', 'like', "%$query%")
            ->get();

        return response()->json([
            'data' => $households
        ]);
    }

    public function verify(Request $request, EvacuationService $service){
        $request->validate([
            'household_id' => 'required|exists:households,household_id'
        ]);

        $user = Auth::user();

        if(!$user->assigned_evacuation_center_id){
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        try{
            $evacuation = $service->handleManual(
                $request->household_id,
                $user->assigned_evacuation_center_id,
                $user->user_id
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data' => $evacuation
            ]);
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function createAndVerify(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_name' => 'required|string'
        ]);

        $user = Auth::user();

        if (!$user->assigned_evacuation_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        try {
            $record = $service->handleNewHousehold(
                $request->household_name,
                $user->assigned_evacuation_center_id,
                $user->user_id
            );

            return response()->json([
                'message' => 'Household created and verified',
                'data' => $record
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}