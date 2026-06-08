<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Services\EvacuationCenterService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreEvacuationCenterRequest;
use App\Http\Requests\UpdateEvacuationCenterRequest;
use OpenApi\Attributes as OA;

class EvacuationCenterController extends Controller
{
    public function __construct(private readonly EvacuationCenterService $evacuationCenterService){}

    #[OA\Get(
        path: '/evacuation-centers',
        summary: 'List evacuation centers',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(){
        // Personnel can view all centers system-wide (same as admin) to allow global search and filtering
        return response()->json(
            $this->evacuationCenterService->getAllCentersWithOccuppancy()
        );
    }

    #[OA\Post(
        path: '/evacuation-centers',
        summary: 'Create evacuation center',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'capacity'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'capacity', type: 'integer'),
                new OA\Property(property: 'barangay', type: 'string', nullable: true),
                new OA\Property(property: 'city', type: 'string', nullable: true),
                new OA\Property(property: 'province', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    #[OA\Get(
        path: '/evacuation-centers/{center}',
        summary: 'Get evacuation center details',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Parameter(name: 'center', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function show(EvacuationCenter $center)
    {
        return response()->json([
            'data' => $center->load('currentEvent')
        ]);
    }

    #[OA\Put(
        path: '/evacuation-centers/{center}',
        summary: 'Update evacuation center details',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Parameter(name: 'center', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'capacity', type: 'integer'),
                new OA\Property(property: 'barangay', type: 'string', nullable: true),
                new OA\Property(property: 'city', type: 'string', nullable: true),
                new OA\Property(property: 'province', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Center not found')]
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

    #[OA\Delete(
        path: '/evacuation-centers/{center}',
        summary: 'Delete evacuation center',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Parameter(name: 'center', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function destroy(EvacuationCenter $center){
        $user = Auth::user();

        if(!$this->isAuthorized()){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->evacuationCenterService->delete($center);

        return response()->json(['message' => 'Evacuation center deleted successfully']);
    }

    #[OA\Get(
        path: '/evacuation-centers/{center}/capacity',
        summary: 'Get capacity status and utilization statistics',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Parameter(name: 'center', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function capacity(EvacuationCenter $center){
        return response()->json(
            $this->evacuationCenterService->getCapacityInfo($center)
        );
    }

    #[OA\Get(
        path: '/public/evacuation-centers',
        summary: 'Public endpoint to retrieve all centers and stats',
        description: 'No authentication required.',
        tags: ['Evacuation Centers']
    )]
    #[OA\Response(response: 200, description: 'Success')]
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