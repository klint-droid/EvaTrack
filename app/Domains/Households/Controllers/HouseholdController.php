<?php

namespace App\Domains\Households\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Domains\Households\Actions\ListHouseholdsAction;
use App\Domains\Households\Actions\CreateHouseholdAction;
use App\Domains\Households\Actions\UpdateHouseholdAction;
use App\Domains\Households\Actions\DeleteHouseholdAction;
use App\Domains\Households\DTOs\HouseholdDTO;
use App\Domains\Households\DTOs\HouseholdFilterDTO;
use App\Domains\Households\Requests\StoreHouseholdRequest;
use App\Domains\Households\Requests\UpdateHouseholdRequest;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class HouseholdController extends BaseApiController
{
    #[OA\Get(
        path: '/households',
        summary: 'List households',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status (evacuated, not_evacuated)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'center_id', in: 'query', description: 'Filter by evacuation center ID', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function index(Request $request, ListHouseholdsAction $action)
    {
        $filters = HouseholdFilterDTO::fromRequest($request);
        return response()->json($action->execute($filters));
    }

    #[OA\Get(
        path: '/households/search',
        summary: 'Search households',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Search query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function search(Request $request, \App\Domains\Households\Actions\SearchHouseholdsAction $action)
    {
        $query = $request->query('q', '');
        if (empty($query)) {
            return response()->json(['data' => []]);
        }
        return response()->json(['data' => $action->execute($query)]);
    }

    #[OA\Get(
        path: '/households/{id}',
        summary: 'Get household details',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function show(string $id, HouseholdRepositoryInterface $repository)
    {
        return response()->json(['data' => $repository->findWithRelations($id)]);
    }

    #[OA\Post(
        path: '/households',
        summary: 'Create household',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['household_name'],
            properties: [
                new OA\Property(property: 'household_name', type: 'string'),
                new OA\Property(property: 'contact_number', type: 'string', nullable: true),
                new OA\Property(property: 'address_id', type: 'integer', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    public function store(StoreHouseholdRequest $request, CreateHouseholdAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');
        
        $dto = HouseholdDTO::fromRequest($request);
        $household = $action->execute($dto);

        return response()->json([
            'message' => 'Household created successfully',
            'data'    => $household,
        ], 201);
    }

    #[OA\Patch(
        path: '/households/{id}',
        summary: 'Update household and address',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'household_name', type: 'string'),
                new OA\Property(property: 'contact_number', type: 'string', nullable: true),
                new OA\Property(property: 'barangay', type: 'string', nullable: true),
                new OA\Property(property: 'street', type: 'string', nullable: true),
                new OA\Property(property: 'purok', type: 'string', nullable: true),
                new OA\Property(property: 'city', type: 'string', nullable: true),
                new OA\Property(property: 'province', type: 'string', nullable: true),
                new OA\Property(property: 'full_address', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function update(UpdateHouseholdRequest $request, string $id, UpdateHouseholdAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $dto = HouseholdDTO::fromRequest($request);
        $household = $action->execute($id, $dto);

        return response()->json([
            'message' => 'Household updated successfully',
            'data'    => $household,
        ]);
    }

    #[OA\Delete(
        path: '/households/{id}',
        summary: 'Delete household',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function destroy(string $id, DeleteHouseholdAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $action->execute($id);

        return response()->json([
            'message' => 'Household and all members deleted successfully'
        ]);
    }
}