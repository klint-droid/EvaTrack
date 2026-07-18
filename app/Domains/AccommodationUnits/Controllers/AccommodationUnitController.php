<?php

namespace App\Domains\AccommodationUnits\Controllers;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use App\Domains\AccommodationUnits\Requests\StoreAccommodationUnitRequest;
use App\Domains\AccommodationUnits\Requests\UpdateAccommodationUnitRequest;
use App\Domains\AccommodationUnits\DTOs\AccommodationUnitDTO;
use App\Domains\AccommodationUnits\Actions\ListAccommodationUnitsAction;
use App\Domains\AccommodationUnits\Actions\ListAccommodationTypesAction;
use App\Domains\AccommodationUnits\Actions\CreateAccommodationUnitAction;
use App\Domains\AccommodationUnits\Actions\UpdateAccommodationUnitAction;
use App\Domains\AccommodationUnits\Actions\DeleteAccommodationUnitAction;
use OpenApi\Attributes as OA;

class AccommodationUnitController extends BaseApiController
{
    #[OA\Get(
        path: '/centers/{centerId}/units',
        summary: 'Get all accommodation units for a center',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Center not found')]
    public function index(Request $request, string $centerId, ListAccommodationUnitsAction $action)
    {
        $perPage = $request->query('limit', 15);
        $units = $action->execute($centerId, $perPage);
        return response()->json($units);
    }

    #[OA\Get(
        path: '/accommodation-types',
        summary: 'Get all accommodation types',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function types(ListAccommodationTypesAction $action)
    {
        return response()->json([
            'data' => $action->execute()
        ]);
    }

    #[OA\Post(
        path: '/centers/{centerId}/units',
        summary: 'Create accommodation unit for center',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'type_id', 'max_capacity'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'type_id', type: 'integer'),
                new OA\Property(property: 'max_capacity', type: 'integer'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Capacity limit exceeded')]
    public function store(StoreAccommodationUnitRequest $request, string $centerId, CreateAccommodationUnitAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $dto = AccommodationUnitDTO::fromRequest($request);

        try {
            $unit = $action->execute($centerId, $dto);
            return response()->json([
                'message' => 'Unit created successfully',
                'data'    => $unit->load('type')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Patch(
        path: '/centers/{centerId}/units/{unitId}',
        summary: 'Update accommodation unit details',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'unitId', in: 'path', description: 'Unit ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'type_id', type: 'integer'),
                new OA\Property(property: 'max_capacity', type: 'integer'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Unit or Center not found')]
    #[OA\Response(response: 422, description: 'Capacity limit exceeded')]
    public function update(UpdateAccommodationUnitRequest $request, string $centerId, int $unitId, UpdateAccommodationUnitAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $dto = AccommodationUnitDTO::fromRequest($request);

        try {
            $unit = $action->execute($unitId, $centerId, $dto);
            return response()->json([
                'message' => 'Unit updated successfully',
                'data'    => $unit->load('type')
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Delete(
        path: '/centers/{centerId}/units/{unitId}',
        summary: 'Delete accommodation unit',
        security: [['bearerAuth' => []]],
        tags: ['Accommodation Units']
    )]
    #[OA\Parameter(name: 'centerId', in: 'path', description: 'Center ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'unitId', in: 'path', description: 'Unit ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 400, description: 'Unit has current occupants')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Unit not found')]
    public function destroy(string $centerId, int $unitId, DeleteAccommodationUnitAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        try {
            $action->execute($unitId, $centerId);
            return response()->json(['message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
