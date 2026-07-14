<?php

namespace App\Domains\EvacuationCenters\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Domains\EvacuationCenters\Models\EvacuationCenter;
use App\Domains\EvacuationCenters\Requests\StoreEvacuationCenterRequest;
use App\Domains\EvacuationCenters\Requests\UpdateEvacuationCenterRequest;
use App\Domains\EvacuationCenters\DTOs\EvacuationCenterDTO;
use App\Domains\EvacuationCenters\Actions\ListEvacuationCentersAction;
use App\Domains\EvacuationCenters\Actions\CreateEvacuationCenterAction;
use App\Domains\EvacuationCenters\Actions\UpdateEvacuationCenterAction;
use App\Domains\EvacuationCenters\Actions\DeleteEvacuationCenterAction;
use App\Domains\EvacuationCenters\Actions\GetCenterCapacityAction;
use App\Domains\EvacuationCenters\Actions\GetPublicCenterStatsAction;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class EvacuationCenterController extends BaseApiController
{
    #[OA\Get(
        path: '/evacuation-centers',
        summary: 'List evacuation centers',
        security: [['bearerAuth' => []]],
        tags: ['Evacuation Centers']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(ListEvacuationCentersAction $action)
    {
        // Personnel can view all centers system-wide (same as admin) to allow global search and filtering
        return response()->json($action->execute());
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
                new OA\Property(property: 'osm_address', type: 'string', nullable: true),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function store(StoreEvacuationCenterRequest $request, CreateEvacuationCenterAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $dto = EvacuationCenterDTO::fromRequest($request);
        $center = $action->execute($dto);

        return response()->json([
            'message' => 'Evacuation center created successfully',
            'data'    => $center
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
                new OA\Property(property: 'osm_address', type: 'string', nullable: true),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $center, UpdateEvacuationCenterAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $dto = EvacuationCenterDTO::fromRequest($request);
        $updatedCenter = $action->execute($center, $dto);

        return response()->json([
            'message' => 'Evacuation center updated successfully',
            'data'    => $updatedCenter
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
    public function destroy(EvacuationCenter $center, DeleteEvacuationCenterAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $action->execute($center);

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
    public function capacity(EvacuationCenter $center, GetCenterCapacityAction $action)
    {
        return response()->json(
            $action->execute($center)
        );
    }

    #[OA\Get(
        path: '/public/evacuation-centers',
        summary: 'Public endpoint to retrieve all centers and stats',
        description: 'No authentication required.',
        tags: ['Evacuation Centers']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function publicIndex(GetPublicCenterStatsAction $action)
    {
        return response()->json($action->execute());
    }
}
