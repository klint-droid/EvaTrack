<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Evacuation;
use App\Services\EvacuationService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreEvacuationRequest;

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
            'data' => Evacuation::with('evacuationCenter')
                ->where('evacuation_center_id', $user->assigned_evacuation_center_id)
                ->get()
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

        $evacuation = Evacuation::with('evacuationCenter')
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

    public function scan(StoreEvacuationRequest $request, EvacuationService $evacuationService)
    {
        $user = Auth::user();
        $data = $request->validated();

        if (!$user->assigned_evacuation_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        if ($user->assigned_evacuation_center_id !== $data['evacuation_center_id']) {
            return response()->json([
                'message' => 'You are not assigned to this evacuation center'
            ], 403);
        }

        try {
            $evacuation = $evacuationService->handleScan(
                $data['household_id'],
                $data['evacuation_center_id'],
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

        $evacuation = Evacuation::where('evacuation_center_id', $user->assigned_evacuation_center_id)
            ->where('status', 'active') 
            ->latest()
            ->first();

        if (!$evacuation) {
            return response()->json([
                'message' => 'No active evacuation found'
            ], 404);
        }

        return response()->json($evacuation);
    }
}