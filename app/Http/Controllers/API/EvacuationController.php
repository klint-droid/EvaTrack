<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Household;
use App\Models\EvacuationRecord;
use App\Services\EvacuationService;
use App\Http\Requests\StoreEvacuationRequest;

class EvacuationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = EvacuationRecord::with([
            'household',
            'evacuatedMembers.member',
            'roomAllocation.room',
            'center',
            'verifiedBy'
        ])->latest();

        if ($user->isSuperAdmin()) {
            return response()->json(['data' => $query->get()]);
        }

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $query->where('center_id', $user->assigned_center_id);

        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $user = Auth::user();

        $query = EvacuationRecord::with([
            'household',
            'evacuatedMembers.member',
            'roomAllocation.room',
            'center',
            'verifiedBy'
        ])->where('evacuation_id', $id);

        if (!$user->isSuperAdmin()) {
            $query->where('center_id', $user->assigned_center_id);
        }

        $evacuation = $query->first();

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

        if (!$user->isAdmin() && !$user->isSuperAdmin() && $user->role !== 'personnel') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isSuperAdmin() && !$user->assigned_center_id) {
            return response()->json(['message' => 'No evacuation center assigned'], 403);
        }

        if (
            EvacuationRecord::where('household_id', $request->household_id)
                ->where('center_id', $user->assigned_center_id)
                ->where('status', 'evacuated')
                ->exists()
        ) {
            return response()->json([
                'message' => 'Household already evacuated in this center'
            ], 400);
        }

        try {
            $result = $service->handleScan(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function verifyManual(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_id' => 'required|exists:households,household_id',
        ]);

        $user = Auth::user();

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        try {
            $result = $service->handleManual(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id
            );

            return response()->json([
                'message' => 'Household verified successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function admit(Request $request, EvacuationService $service)
    {
        $request->validate([
            'household_id' => 'required|exists:households,household_id',
            'member_count' => 'required|integer|min:1'
        ]);

        $user = Auth::user();

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        if (
            EvacuationRecord::where('household_id', $request->household_id)
                ->where('center_id', $user->assigned_center_id)
                ->where('status', 'evacuated')
                ->exists()
        ) {
            return response()->json([
                'message' => 'Household already evacuated'
            ], 400);
        }

        try {
            $result = $service->handleManualWithCount(
                $request->household_id,
                $user->assigned_center_id,
                $user->user_id,
                $request->member_count
            );

            return response()->json([
                'message' => 'Admission complete',
                'data' => $result
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

        if (!$user->assigned_center_id) {
            return response()->json([
                'message' => 'No evacuation center assigned'
            ], 403);
        }

        $evacuation = EvacuationRecord::where('center_id', $user->assigned_center_id)
            ->where('status', 'evacuated')
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