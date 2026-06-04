<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\EvacuatedMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class HouseholdMemberController extends Controller
{
    private function statusIds(string $table, string $column): array
    {
        return DB::connection('mysql_v2')
            ->table($table)
            ->pluck($column)
            ->toArray();
    }

    #[OA\Get(
        path: '/households/{householdId}/members',
        summary: 'List household members',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'householdId', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Household not found')]
    public function index($householdId)
    {
        $household = Household::with('members')
            ->where('household_id', $householdId)
            ->firstOrFail();

        return response()->json([
            'data' => $household->members
        ]);
    }

    #[OA\Post(
        path: '/households/{householdId}/members',
        summary: 'Add member to household',
        security: [['bearerAuth' => []]],
        tags: ['Households']
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
    public function store(Request $request, $householdId)
    {
        $request->validate([
            'first_name'           => 'required|string|max:100',
            'middle_name'          => 'nullable|string|max:100',
            'last_name'            => 'required|string|max:100',
            'birth_date'           => 'required|date',
            'gender_id'            => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('genders', 'gender_id'))],
            'relationship_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('relationships', 'relationship_id'))],
            'civil_status_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('civil_statuses', 'status_id'))],
            'vulnerable_group_ids' => 'nullable|array',
            'vulnerable_group_ids.*' => 'integer',
        ]);

        Household::where('household_id', $householdId)->firstOrFail();

        return DB::connection('mysql_v2')->transaction(function () use ($request, $householdId) {

            $member = HouseholdMember::create([
                'household_id'    => $householdId,
                'first_name'      => $request->first_name,
                'middle_name'     => $request->middle_name,
                'last_name'       => $request->last_name,
                'birth_date'      => $request->birth_date,
                'gender_id'       => $request->gender_id,
                'relationship_id' => $request->relationship_id,
                'civil_status_id' => $request->civil_status_id,
            ]);

            if ($request->has('vulnerable_group_ids')) {
                $member->vulnerableGroupDetails()->sync($request->vulnerable_group_ids);
            }

            return response()->json([
                'message' => 'Member added successfully.',
                'data'    => $member->fresh(['gender', 'relationship', 'civilStatus', 'vulnerableGroupDetails']),
            ], 201);
        });
    }

    #[OA\Patch(
        path: '/households/{householdId}/members/{memberId}',
        summary: 'Update household member',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'householdId', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'memberId', in: 'path', description: 'Member ID', required: true, schema: new OA\Schema(type: 'integer'))]
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
    #[OA\Response(response: 200, description: 'Updated successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Member or Household not found')]
    public function update(Request $request, $householdId, $memberId)
    {
        $request->validate([
            'first_name'           => 'required|string|max:100',
            'middle_name'          => 'nullable|string|max:100',
            'last_name'            => 'required|string|max:100',
            'birth_date'           => 'required|date',
            'gender_id'            => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('genders', 'gender_id'))],
            'relationship_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('relationships', 'relationship_id'))],
            'civil_status_id'      => ['nullable', 'integer', 'in:' . implode(',', $this->statusIds('civil_statuses', 'status_id'))],
            'vulnerable_group_ids' => 'nullable|array',
            'vulnerable_group_ids.*' => 'integer',
        ]);

        $member = HouseholdMember::where('member_id', $memberId)
            ->where('household_id', $householdId)
            ->firstOrFail();

        return DB::connection('mysql_v2')->transaction(function () use ($request, $member) {
            $member->update($request->only([
                'first_name',
                'middle_name',
                'last_name',
                'birth_date',
                'gender_id',
                'relationship_id',
                'civil_status_id',
            ]));

            if ($request->has('vulnerable_group_ids')) {
                $member->vulnerableGroupDetails()->sync($request->vulnerable_group_ids);
            }

            return response()->json([
                'message' => 'Member updated successfully.',
                'data'    => $member->fresh(['gender', 'relationship', 'civilStatus', 'vulnerableGroupDetails']),
            ]);
        });
    }

    #[OA\Delete(
        path: '/households/{householdId}/members/{memberId}',
        summary: 'Delete household member',
        security: [['bearerAuth' => []]],
        tags: ['Households']
    )]
    #[OA\Parameter(name: 'householdId', in: 'path', description: 'Household ID', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'memberId', in: 'path', description: 'Member ID', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 400, description: 'Member is currently evacuated')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Member or Household not found')]
    public function destroy($householdId, $memberId)
    {
        $user = Auth::user();

        if ($user->isEvacPersonnel()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return DB::connection('mysql_v2')->transaction(function () use ($householdId, $memberId) {

            $member = HouseholdMember::where('member_id', $memberId)
                ->where('household_id', $householdId)
                ->firstOrFail();

            $isLinkedToEvacuation = EvacuatedMember::where('member_id', $memberId)->exists();

            if ($isLinkedToEvacuation) {
                return response()->json([
                    'message' => 'Cannot delete this member because they are linked to an evacuation record. Mark them as Not Verified first.'
                ], 400);
            }

            $member->delete();

            return response()->json([
                'message' => 'Member removed successfully.'
            ]);
        });
    }
}