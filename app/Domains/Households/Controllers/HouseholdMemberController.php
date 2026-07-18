<?php

namespace App\Domains\Households\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Domains\Households\Actions\AddMemberAction;
use App\Domains\Households\Actions\UpdateMemberAction;
use App\Domains\Households\Actions\DeleteMemberAction;
use App\Domains\Households\DTOs\MemberDTO;
use App\Domains\Households\Requests\StoreHouseholdMemberRequest;
use App\Domains\Households\Requests\UpdateHouseholdMemberRequest;
use OpenApi\Attributes as OA;

class HouseholdMemberController extends BaseApiController
{
    #[OA\Get(
        path: '/households/{householdId}/members',
        summary: 'List household members',
        security: [['bearerAuth' => []]],
        tags: ['Household Members']
    )]
    #[OA\Parameter(name: 'householdId', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(string $householdId, \App\Domains\Households\Repositories\HouseholdRepositoryInterface $repository)
    {
        $household = $repository->findWithRelations($householdId);
        return response()->json(['data' => $household->members]);
    }

    #[OA\Post(
        path: '/households/{householdId}/members',
        summary: 'Add member to household',
        security: [['bearerAuth' => []]],
        tags: ['Household Members']
    )]
    #[OA\Parameter(name: 'householdId', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'birth_date'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'middle_name', type: 'string', nullable: true),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
                new OA\Property(property: 'gender_id', type: 'integer', nullable: true),
                new OA\Property(property: 'relationship_id', type: 'integer', nullable: true),
                new OA\Property(property: 'civil_status_id', type: 'integer', nullable: true),
                new OA\Property(property: 'vulnerable_group_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function store(StoreHouseholdMemberRequest $request, string $householdId, AddMemberAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $dto = MemberDTO::fromRequest($request);
        $member = $action->execute($householdId, $dto);

        return response()->json([
            'message' => 'Household member added successfully',
            'data'    => $member->load('gender', 'relationship', 'civilStatus', 'vulnerableGroupDetails'),
        ], 201);
    }

    #[OA\Put(
        path: '/members/{memberId}',
        summary: 'Update member',
        security: [['bearerAuth' => []]],
        tags: ['Household Members']
    )]
    #[OA\Parameter(name: 'memberId', in: 'path', description: 'Member ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'middle_name', type: 'string', nullable: true),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
                new OA\Property(property: 'gender_id', type: 'integer', nullable: true),
                new OA\Property(property: 'relationship_id', type: 'integer', nullable: true),
                new OA\Property(property: 'civil_status_id', type: 'integer', nullable: true),
                new OA\Property(property: 'vulnerable_group_ids', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Member not found')]
    public function update(UpdateHouseholdMemberRequest $request, string $memberId, UpdateMemberAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin', 'evac_personnel');

        $dto = MemberDTO::fromRequest($request);
        $member = $action->execute($memberId, $dto);

        return response()->json([
            'message' => 'Household member updated successfully',
            'data'    => $member->load('gender', 'relationship', 'civilStatus', 'vulnerableGroupDetails'),
        ]);
    }

    #[OA\Delete(
        path: '/members/{memberId}',
        summary: 'Delete member',
        security: [['bearerAuth' => []]],
        tags: ['Household Members']
    )]
    #[OA\Parameter(name: 'memberId', in: 'path', description: 'Member ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Member not found')]
    public function destroy(string $memberId, DeleteMemberAction $action)
    {
        $this->authorizeRole('super_admin', 'evac_admin');

        $action->execute($memberId);

        return response()->json([
            'message' => 'Household member deleted successfully'
        ]);
    }
}