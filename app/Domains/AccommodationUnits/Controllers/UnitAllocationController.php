<?php

namespace App\Domains\AccommodationUnits\Controllers;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\AccommodationUnits\Requests\AssignUnitAllocationRequest;
use App\Domains\AccommodationUnits\DTOs\UnitAllocationDTO;
use App\Domains\AccommodationUnits\Actions\ListUnitAllocationsAction;
use App\Domains\AccommodationUnits\Actions\AssignUnitAllocationAction;
use App\Domains\AccommodationUnits\Actions\UnassignUnitAllocationAction;
use App\Domains\AccommodationUnits\Actions\GetUnassignedEvacuationsAction;
use OpenApi\Attributes as OA;

class UnitAllocationController extends BaseApiController
{
    #[OA\Get(
        path: '/units/{unitId}/allocations',
        summary: 'Get all allocations for a unit',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'unitId', in: 'path', description: 'Unit ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Unit not found')]
    public function index($unitId, ListUnitAllocationsAction $action)
    {
        return response()->json(['data' => $action->execute($unitId)]);
    }

    #[OA\Post(
        path: '/units/{unitId}/allocations',
        summary: 'Assign a household to a unit',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'unitId', in: 'path', description: 'Unit ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['evacuation_id'],
            properties: [
                new OA\Property(property: 'evacuation_id', type: 'integer'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Assigned successfully')]
    #[OA\Response(response: 400, description: 'Invalid request, unit full, or already assigned')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Unit or Evacuation record not found')]
    public function assign(AssignUnitAllocationRequest $request, $unitId, AssignUnitAllocationAction $action)
    {
        $user = Auth::user();
        $dto = UnitAllocationDTO::fromRequest($request);

        try {
            $allocation = $action->execute($unitId, $dto, $user->user_id);
            return response()->json([
                'message' => 'Household assigned successfully.',
                'data'    => $allocation->load('evacuationRecord.household')
            ], 201);
        } catch (\Exception $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    #[OA\Delete(
        path: '/units/{unitId}/allocations/{allocationId}',
        summary: 'Unassign a household from a unit',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'unitId', in: 'path', description: 'Unit ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'allocationId', in: 'path', description: 'Allocation ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Allocation or Unit not found')]
    public function unassign($unitId, $allocationId, UnassignUnitAllocationAction $action)
    {
        try {
            $action->execute($unitId, $allocationId);
            return response()->json(['message' => 'Household unassigned successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Not found'], 404);
        }
    }

    #[OA\Get(
        path: '/centers/{centerId}/unassigned',
        summary: 'Get unassigned evacuations for a center',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function unassigned($centerId, GetUnassignedEvacuationsAction $action)
    {
        return response()->json(['data' => $action->execute($centerId)]);
    }
}
