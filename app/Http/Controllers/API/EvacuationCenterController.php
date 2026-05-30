<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Services\EvacuationCenterService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;

class EvacuationCenterController extends Controller
{
    public function __construct(private readonly EvacuationCenterService $evacuationCenterService){}

    public function index(){
        // Personnel can view all centers system-wide (same as admin) to allow global search and filtering
        return response()->json(
            $this->evacuationCenterService->getAllCentersWithOccuppancy()
        );
    }

    public function store(StoreEvacuationCenterRequest $request)
    {
        $user = Auth::user();

        if(!$this->isAuthorized()){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $center = $this->evacuationCenterService->create($request->validated());

        return response()->json([
            'message' => 'Evacuation center created successfully',
            'data' => $center
        ], 201);
    }

    public function show(EvacuationCenter $center)
    {
        return response()->json([
            'data' => $center
        ]);
    }

    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $center){
        $user = Auth::user();

        if(!$this->isAuthorized()){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $updatedCenter = $this->evacuationCenterService->update($center, $request->validated());

        return response()->json([
            'message' => 'Evacuation center updated successfully',
            'data' => $updatedCenter
        ]);
    }

    public function destroy(EvacuationCenter $center){
        $user = Auth::user();

        if(!$this->isAuthorized()){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->evacuationCenterService->delete($center);

        return response()->json(['message' => 'Evacuation center deleted successfully']);
    }

    public function capacity(EvacuationCenter $center){
        return response()->json(
            $this->evacuationCenterService->getCapacityInfo($center)
        );
    }

    public function publicIndex()
    {
        $centers = $this->evacuationCenterService->getAllCentersWithOccuppancy();

        $totalCenters = $centers->count();
        $totalEvacuees = (int) $centers->sum('current_occupancy');
        $totalCapacity = (int) $centers->sum('capacity');
        $avgCapacity = $totalCapacity > 0 ? (int) round(($totalEvacuees / $totalCapacity) * 100) : 0;

        $fullCenters = $centers->filter(function ($center) {
            return $center->capacity > 0 && $center->current_occupancy >= $center->capacity;
        })->count();

        return response()->json([
            'centers' => $centers,
            'stats' => [
                'total_centers' => $totalCenters,
                'total_evacuees' => $totalEvacuees,
                'avg_capacity' => $avgCapacity,
                'full_centers' => $fullCenters,
            ]
        ]);
    }

    private function isAuthorized(): bool
    {
        $user = Auth::user();
        return $user && $user->isEvacAdmin();
    }
}